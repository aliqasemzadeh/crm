<?php

use Illuminate\Support\Facades\Route;


Route::middleware(['guest'])->group( function () {
    Route::livewire('/login', \App\Livewire\Auth\Login::class)->name('login');
    Route::livewire('/register', \App\Livewire\Auth\Register::class)->name('register');
});

Route::livewire('/forget-password', \App\Livewire\Auth\ForgetPassword::class)->name('forget-password');
Route::livewire('/change-password/{token}', \App\Livewire\Auth\ChangePassword::class)->name('change-password');

Route::middleware(['auth'])->group( function () {
    Route::livewire('/panels/crm/dashboard/index', \App\Livewire\Panels\Crm\Dashboard\Index::class)->name('panels.crm.dashboard.index');

    Route::livewire('/', \App\Livewire\Panels\User\Dashboard\Index::class)->name('home');
    Route::livewire('/panels/user/dashboard/index', \App\Livewire\Panels\User\Dashboard\Index::class)->name('panels.user.dashboard.index');

    Route::livewire('/panels/administrator/dashboard/index', \App\Livewire\Panels\Administrator\Dashboard\Index::class)->name('panels.administrator.dashboard.index');
    Route::livewire('/panels/administrator/user-management/user/index', \App\Livewire\Panels\Administrator\UserManagement\User\Index::class)->name('panels.administrator.user-management.user.index');
    Route::livewire('/panels/administrator/user-management/role/index', \App\Livewire\Panels\Administrator\UserManagement\Role\Index::class)->name('panels.administrator.user-management.role.index');
    Route::livewire('/panels/administrator/user-management/permission/index', \App\Livewire\Panels\Administrator\UserManagement\Permission\Index::class)->name('panels.administrator.user-management.permission.index');
    Route::livewire('/panels/administrator/setting-management/function/index', \App\Livewire\Panels\Administrator\SettingManagement\Function\Index::class)->name('panels.administrator.setting-management.function.index');
    Route::livewire('/panels/administrator/setting-management/option/index', \App\Livewire\Panels\Administrator\SettingManagement\Option\Index::class)->name('panels.administrator.setting-management.option.index');

    Route::livewire('/panels/service-center/dashboard/index', \App\Livewire\Panels\ServiceCenter\Dashboard\Index::class)->name('panels.service-center.dashboard.index');
    Route::livewire('/panels/service-center/assembly/index', \App\Livewire\Panels\ServiceCenter\Assembly\Index::class)->name('panels.service-center.assembly.index');
    Route::livewire('/panels/service-center/repair/index', \App\Livewire\Panels\ServiceCenter\Repair\Index::class)->name('panels.service-center.repair.index');

    Route::livewire('/panels/workspace/dashboard/index', \App\Livewire\Panels\Workspace\Dashboard\Index::class)->name('panels.workspace.dashboard.index');

    Route::livewire('/logout', \App\Livewire\Auth\Logout::class)->name('logout');
    Route::livewire('/user/logout', \App\Livewire\User\ChangePassword::class)->name('user.change-password');
    Route::livewire('/user/email', \App\Livewire\User\ChangeEmail::class)->name('user.change-email');
});
