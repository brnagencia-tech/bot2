<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\WhatsAppWebController;
use App\Http\Controllers\WhatsAppCloudController;
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

    // WhatsApp Web (não-oficial) UI + proxy
    Route::get('/whatsapp', [WhatsAppWebController::class, 'index'])->name('whatsapp.index');
    Route::get('/whatsapp/status', [WhatsAppWebController::class, 'status'])->name('whatsapp.status');
    Route::get('/whatsapp/qr', [WhatsAppWebController::class, 'qr'])->name('whatsapp.qr');
    Route::post('/whatsapp/logout', [WhatsAppWebController::class, 'logout'])->name('whatsapp.logout');
    Route::post('/whatsapp/send', [WhatsAppWebController::class, 'send'])->name('whatsapp.send');
    Route::post('/whatsapp/reset', [WhatsAppWebController::class, 'reset'])->name('whatsapp.reset');

    // WhatsApp Cloud API (oficial)
    Route::get('/whatsapp-cloud', [WhatsAppCloudController::class, 'index'])->name('whatsapp.cloud.index');
    Route::post('/whatsapp-cloud/send', [WhatsAppCloudController::class, 'send'])->name('whatsapp.cloud.send');

    // Configurações
    Route::view('/settings', 'settings.index')->name('settings.index');
});

require __DIR__.'/auth.php';

// WhatsApp Cloud API Webhook (public, sem CSRF)
Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verify'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'receive'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
