<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

Route::livewire('/login', \App\Livewire\Auth\Login::class)->name('login');
Route::livewire('/register', \App\Livewire\Auth\Register::class)->name('register');
Route::livewire('/forget-password', \App\Livewire\Auth\ForgetPassword::class)->name('forget-password');
Route::livewire('/change-password/{token}', \App\Livewire\Auth\ChangePassword::class)->name('change-password');


Route::middleware(['auth', 'verified'])->group( function () {
    Route::livewire('/crm/dashboard/index', \App\Livewire\Crm\Dashboard\Index::class)->name('crm.dashboard.index');

    Route::livewire('/', \App\Livewire\User\Dashboard\Index::class)->name('user.dashboard.index');
    Route::livewire('/user/dashboard/index', \App\Livewire\User\Dashboard\Index::class)->name('user.dashboard.index');
});
