<?php

use App\Http\Controllers\Api\BaileysWebhookController;
use App\Http\Controllers\Api\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('whatsapp.webhook')
    ->prefix('webhooks/baileys')
    ->group(function (): void {
        Route::post('messages', [BaileysWebhookController::class, 'incomingMessage'])->name('webhooks.baileys.messages');
        Route::post('devices/status', [BaileysWebhookController::class, 'deviceStatus'])->name('webhooks.baileys.devices');
        Route::post('messages/status', [BaileysWebhookController::class, 'deliveryStatus'])->name('webhooks.baileys.messages.status');
    });

Route::middleware('webhook.signature:telegram')
    ->prefix('webhooks/telegram')
    ->group(function (): void {
        Route::post('messages', [TelegramWebhookController::class, 'incomingMessage'])->name('webhooks.telegram.messages');
        Route::post('messages/status', [TelegramWebhookController::class, 'deliveryStatus'])->name('webhooks.telegram.messages.status');
    });
