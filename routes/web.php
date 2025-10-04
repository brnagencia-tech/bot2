<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // AI proxy endpoint
    Route::post('/ai/generate', [AIController::class, 'generate'])->name('ai.generate');
});

require __DIR__.'/auth.php';

// WhatsApp Cloud API Webhook (public, sem CSRF)
Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verify'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'receive'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
