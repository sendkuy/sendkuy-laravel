<?php

use Illuminate\Support\Facades\Route;
use SendKuy\Laravel\Http\WebhookController;

$path = config('sendkuy.webhook.path');

if (is_string($path) && trim($path) !== '') {
    Route::post($path, WebhookController::class)->name('sendkuy.webhook');
}
