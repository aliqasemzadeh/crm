<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/login', \App\Livewire\Front\Auth\Login::class)->name('login');
