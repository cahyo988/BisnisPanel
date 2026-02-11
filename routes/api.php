<?php

use App\Http\Controllers\Api\BaileysWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('whatsapp.webhook')
    ->prefix('webhooks/baileys')
    ->group(function (): void {
        Route::post('messages', [BaileysWebhookController::class, 'incomingMessage'])->name('webhooks.baileys.messages');
        Route::post('devices/status', [BaileysWebhookController::class, 'deviceStatus'])->name('webhooks.baileys.devices');
        Route::post('messages/status', [BaileysWebhookController::class, 'deliveryStatus'])->name('webhooks.baileys.messages.status');
    });
