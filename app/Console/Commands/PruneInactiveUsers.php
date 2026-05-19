<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneInactiveUsers extends Command
{
    protected $signature = 'users:prune-inactive {--force : Actually delete (default is a dry run that deletes nothing)}';

    protected $description = 'Delete abandoned guest accounts and never-used free accounts. Dry-run by default.';

    public function handle(): int
    {
        $guestCutoff = now()->subDays((int) config('pricing.guest_prune_days', 14));
        $freeCutoff  = now()->subDays((int) config('pricing.free_prune_days', 30));
        $force       = (bool) $this->option('force');

        $guests = $this->safeBase()
            ->where('is_guest', true)
            ->where(function (Builder $q) use ($guestCutoff) {
                // never sent a message and the account is old
                $q->where(function (Builder $a) use ($guestCutoff) {
                    $a->whereNotExists(fn ($m) => $this->messages($m))
                      ->where('created_at', '<', $guestCutoff);
                })
                // chatted, but no message since the cutoff (stale)
                ->orWhere(function (Builder $b) use ($guestCutoff) {
                    $b->whereExists(fn ($m) => $this->messages($m))
                      ->whereNotExists(fn ($m) => $this->messages($m, $guestCutoff));
                });
            });

        $free = $this->safeBase()
            ->where('is_guest', false)
            ->whereNotNull('email')
            ->whereNotExists(fn ($m) => $this->messages($m))   // never chatted
            ->where('created_at', '<', $freeCutoff);

        $guestCount = (clone $guests)->count();
        $freeCount  = (clone $free)->count();

        if (! $force) {
            $this->warn('DRY RUN — nothing was deleted. Pass --force to actually delete.');
            $this->line("Guests (no activity > ".config('pricing.guest_prune_days')."d): {$guestCount}");
            $this->line("Free, never chatted, > ".config('pricing.free_prune_days')."d old: {$freeCount}");
            $this->table(['Type', 'id', 'email', 'created_at'], collect()
                ->merge((clone $guests)->limit(10)->get(['id', 'email', 'created_at'])->map(fn ($u) => ['guest', $u->id, $u->email ?? '—', $u->created_at]))
                ->merge((clone $free)->limit(10)->get(['id', 'email', 'created_at'])->map(fn ($u) => ['free', $u->id, $u->email, $u->created_at]))
                ->all());
            return self::SUCCESS;
        }

        DB::transaction(function () use ($guests, $free) {
            (clone $guests)->delete();   // FK cascade removes conversations/messages/pending payments
            (clone $free)->delete();
        });

        $msg = "Pruned inactive users — guests: {$guestCount}, free(never-chatted): {$freeCount}";
        $this->info($msg);
        Log::info('[users:prune-inactive] ' . $msg);

        return self::SUCCESS;
    }

    /** Base query with the non-negotiable safety exclusions. */
    private function safeBase(): Builder
    {
        return User::query()
            // never delete admins
            ->where(fn (Builder $q) => $q->whereNull('role')->orWhere('role', '!=', 'admin'))
            // never delete anyone who has ever paid
            ->whereNotExists(function ($p) {
                $p->select(DB::raw(1))
                  ->from('payments')
                  ->whereColumn('payments.user_id', 'users.id')
                  ->where('payments.status', 'paid');
            })
            // never delete an active unlimited subscriber
            ->where(function (Builder $q) {
                $q->whereNull('unlimited_until')->orWhere('unlimited_until', '<=', now());
            });
    }

    /** Sub-query: messages belonging to the user (optionally only since $since). */
    private function messages($query, $since = null): void
    {
        $query->select(DB::raw(1))
            ->from('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->whereColumn('conversations.user_id', 'users.id');

        if ($since !== null) {
            $query->where('messages.created_at', '>=', $since);
        }
    }
}
