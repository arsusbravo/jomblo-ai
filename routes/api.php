<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\Admin\CharacterController as AdminCharacterController;
use App\Http\Controllers\Api\LangController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\User\CharacterController as UserCharacterController;
use App\Http\Controllers\Api\User\ConversationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/home', [PublicController::class, 'home']);
Route::get('/about', [PublicController::class, 'about']);
Route::get('/pricing', [PublicController::class, 'pricing']);
Route::get('/featured-characters', [PublicController::class, 'featuredCharacters']);
Route::get('/lang/{locale}', [LangController::class, 'show']);

// Stripe webhook — public, server-to-server (signature-verified inside the controller)
Route::post('/stripe/webhook', [PaymentController::class, 'webhook']);

// Authenticated user info — auth:sanctum ensures correct guard; exception handler returns null (not 401) for guests
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json($request->user());
});

// User routes (authenticated, any role)
Route::middleware(['auth:sanctum', 'auth.user'])->prefix('user')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::delete('/profile', [UserController::class, 'destroyAccount']);
    Route::get('/characters', [UserCharacterController::class, 'index']);
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{character}', [ConversationController::class, 'show']);
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'sendMessage']);
    Route::post('/checkout', [PaymentController::class, 'checkout']);
});

// Admin routes (authenticated, admin role only)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users', [AdminController::class, 'storeUser']);
    Route::put('/users/{user}', [AdminController::class, 'updateUser']);
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
    Route::post('/users/{user}/credits', [AdminController::class, 'grantCredits']);
    Route::get('/characters', [AdminCharacterController::class, 'index']);
    Route::post('/characters', [AdminCharacterController::class, 'store']);
    Route::post('/characters/{character}', [AdminCharacterController::class, 'update']);
    Route::delete('/characters/{character}', [AdminCharacterController::class, 'destroy']);
});
