<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer as StripeCustomer;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentController extends Controller
{
    /**
     * Create a Stripe Checkout Session for the given plan and return its URL.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate(['plan' => ['required', 'string']]);

        $plan = collect(config('pricing.plans'))->firstWhere('id', $request->plan);

        if (! $plan || empty($plan['stripe_price_id'])) {
            return response()->json(['message' => 'plan_unavailable'], 422);
        }

        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            return response()->json(['message' => 'payments_not_configured'], 503);
        }

        $user = $request->user();

        if ($user->isGuest()) {
            return response()->json(['message' => 'register_required'], 403);
        }

        Stripe::setApiKey($secret);

        $base = rtrim(config('app.url'), '/');

        // Reuse one Stripe customer per user; default the billing country to NL so
        // Checkout loads with the Netherlands pre-selected (user can still change it).
        if (! $user->stripe_customer_id) {
            $customer = StripeCustomer::create([
                'email'   => $user->email,
                'name'    => $user->name,
                'address' => ['country' => 'NL'],
                'metadata' => ['user_id' => (string) $user->id],
            ]);
            $user->stripe_customer_id = $customer->id;
            $user->save();
        }

        $session = StripeSession::create([
            'mode'                       => 'payment',
            'line_items'                 => [[
                'price'    => $plan['stripe_price_id'],
                'quantity' => 1,
            ]],
            'automatic_tax'              => ['enabled' => true],
            'billing_address_collection' => 'required',
            'customer'                   => $user->stripe_customer_id,
            'customer_update'            => ['address' => 'auto'],
            'client_reference_id'        => (string) $user->id,
            'metadata'                   => [
                'user_id' => (string) $user->id,
                'plan_id' => $plan['id'],
            ],
            'success_url'                => $base . '/dashboard?payment=success',
            'cancel_url'                 => $base . '/dashboard?payment=cancelled',
        ]);

        Payment::create([
            'user_id'           => $user->id,
            'plan_id'           => $plan['id'],
            'stripe_session_id' => $session->id,
            'amount'            => $plan['amount'],
            'currency'          => config('pricing.currency', 'EUR'),
            'status'            => 'pending',
        ]);

        return response()->json(['url' => $session->url]);
    }

    /**
     * Stripe webhook — the single source of truth for fulfilling a purchase.
     * No auth, no CSRF (Stripe calls this server-to-server).
     */
    public function webhook(Request $request): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $secret
            );
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['message' => 'invalid_signature'], 400);
        }

        if ($event->type !== 'checkout.session.completed') {
            return response()->json(['received' => true]);
        }

        $session = $event->data->object;

        $payment = Payment::where('stripe_session_id', $session->id)->first();

        // Idempotency: unknown session or already fulfilled → ack and stop.
        if (! $payment || $payment->status === 'paid') {
            return response()->json(['received' => true]);
        }

        $plan = collect(config('pricing.plans'))->firstWhere('id', $payment->plan_id);
        $user = $payment->user;

        if (! $plan || ! $user) {
            Log::error("Stripe webhook: missing plan/user for payment {$payment->id}");
            return response()->json(['received' => true]);
        }

        if (! empty($plan['credits'])) {
            $user->increment('message_credits', $plan['credits']);
            $payment->credits_granted = $plan['credits'];
        }

        if (! empty($plan['unlimited_days'])) {
            // Stack: if still on an active unlimited window, extend from its end.
            $from = ($user->unlimited_until && $user->unlimited_until->isFuture())
                ? $user->unlimited_until
                : Carbon::now();
            $user->unlimited_until = $from->copy()->addDays($plan['unlimited_days']);
            $user->save();
            $payment->unlimited_granted_until = $user->unlimited_until;
        }

        $payment->status = 'paid';
        $payment->save();

        return response()->json(['received' => true]);
    }
}
