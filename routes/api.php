<?php

use App\Http\Controllers\API\V1\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('contact/search/{number}', [ContactController::class, 'search']);
