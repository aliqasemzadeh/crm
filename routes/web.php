<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/login', \App\Livewire\Front\Auth\Login::class)->name('login');


Route::livewire('/crm/dashboard/index', \App\Livewire\Crm\Dashboard\Index::class)->name('crm.dashboard.index');
