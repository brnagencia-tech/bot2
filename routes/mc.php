<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth','verified'])->prefix('mc')->name('mc.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Mc\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/inbox',     [\App\Http\Controllers\Mc\InboxController::class, 'index'])->name('inbox');
    Route::get('/clients',   [\App\Http\Controllers\Mc\ClientsController::class, 'index'])->name('clients');

    // WhatsApp status/qr proxied (frontend simples)
    Route::get('/whatsapp/status', [\App\Http\Controllers\Mc\WhatsappController::class, 'status'])->name('wa.status');
    Route::get('/whatsapp/qr',     [\App\Http\Controllers\Mc\WhatsappController::class, 'qr'])->name('wa.qr');
    Route::post('/message/send',   [\App\Http\Controllers\Mc\InboxController::class, 'send'])->name('message.send');

    // Tags/Segments
    Route::resource('tags',     \App\Http\Controllers\Mc\TagController::class)->only(['index','store','update','destroy']);
    Route::resource('segments', \App\Http\Controllers\Mc\SegmentController::class)->only(['index','store','update','destroy']);

    // Flows (builder simples)
    Route::resource('flows', \App\Http\Controllers\Mc\FlowController::class)->only(['index','store','show','update','destroy']);
    Route::post('flows/{flow}/run', [\App\Http\Controllers\Mc\FlowController::class, 'run'])->name('flows.run');

    // Broadcast (fila)
    Route::post('/broadcast', [\App\Http\Controllers\Mc\BroadcastController::class, 'createJob'])->name('broadcast.create');

    // Settings do workspace
    Route::get('/settings',  [\App\Http\Controllers\Mc\SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [\App\Http\Controllers\Mc\SettingsController::class, 'save']);
});

