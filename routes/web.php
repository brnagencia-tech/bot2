<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\WhatsAppWebController;
use App\Http\Controllers\WaWebInboundController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('/dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

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

    // Removed WhatsApp Cloud API routes

    // Configurações
    Route::view('/settings', 'settings.index')->name('settings.index');

    // Menus adicionais
    Route::view('/flows', 'flows.index')->name('flows.index');
    Route::view('/agent', 'agent.index')->name('agent.index');
    Route::get('/contacts', [\App\Http\Controllers\ContactController::class, 'index'])->name('contacts.index');
    Route::get('/chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [\App\Http\Controllers\ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/send', [\App\Http\Controllers\ChatController::class, 'send'])->name('chat.send');
});

require __DIR__.'/auth.php';

// WhatsApp Cloud API Webhook (public, sem CSRF)
// Removed Cloud API webhook; using WhatsApp Web gateway instead.

// Inbound from WhatsApp Web gateway
Route::post('/waweb/inbound', [WaWebInboundController::class, 'inbound'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// ManyChat-like module routes (if present)
if (file_exists(__DIR__.'/mc.php')) {
    require __DIR__.'/mc.php';
}
