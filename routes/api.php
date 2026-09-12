<?php

use App\Http\Controllers\API\V1\BaleWebhookController;
use App\Http\Controllers\API\V1\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('contact/search/{number}', [ContactController::class, 'search']);
Route::any('contact/call', [ContactController::class, 'call']);
Route::post('bale/webhook/{secret}', BaleWebhookController::class)->name('api.bale.webhook');
