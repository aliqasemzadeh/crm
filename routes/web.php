<?php

use Illuminate\Support\Facades\Route;


Route::middleware(['guest'])->group( function () {
    Route::livewire('/login', \App\Livewire\Auth\Login::class)->name('login');
    Route::livewire('/register', \App\Livewire\Auth\Register::class)->name('register');
});

Route::livewire('/forget-password', \App\Livewire\Auth\ForgetPassword::class)->name('forget-password');
Route::livewire('/change-password/{token}', \App\Livewire\Auth\ChangePassword::class)->name('change-password');

Route::middleware(['auth'])->group( function () {
    Route::livewire('/panels/crm/dashboard/index', \App\Livewire\Panels\Crm\Dashboard\Index::class)->name('crm.dashboard.index');

    Route::livewire('/', \App\Livewire\Panels\User\Dashboard\Index::class)->name('home');
    Route::livewire('/panels/user/dashboard/index', \App\Livewire\Panels\User\Dashboard\Index::class)->name('user.dashboard.index');

    Route::get('/panels/service-center/dashboard/index', \App\Livewire\Panels\ServiceCenter\Dashboard\Index::class)->name('panel.service-center.dashboard.index');
    Route::get('/panels/service-center/assembly/index', \App\Livewire\Panels\ServiceCenter\Assembly\Index::class)->name('panel.service-center.assembly.index');
    Route::get('/panels/service-center/repair/index', \App\Livewire\Panels\ServiceCenter\Repair\Index::class)->name('panel.service-center.repair.index');


    Route::livewire('/panels/logout', \App\Livewire\Auth\Logout::class)->name('logout');
});
