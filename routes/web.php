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
    Route::livewire('/panels/user/setting/change-password', \App\Livewire\Panels\User\Setting\ChangePassword::class)->name('panels.user.setting.change-password');
    Route::livewire('/panels/user/setting/change-email', \App\Livewire\Panels\User\Setting\ChangeEmail::class)->name('panels.user.setting.change-email');
    Route::livewire('/panels/user/setting/change-mobile', \App\Livewire\Panels\User\Setting\ChangeMobile::class)->name('panels.user.setting.change-mobile');


    Route::livewire('/panels/administrator/dashboard/index', \App\Livewire\Panels\Administrator\Dashboard\Index::class)->name('panels.administrator.dashboard.index');
    Route::livewire('/panels/administrator/user-management/user/index', \App\Livewire\Panels\Administrator\UserManagement\User\Index::class)->name('panels.administrator.user-management.user.index');
    Route::livewire('/panels/administrator/user-management/role/index', \App\Livewire\Panels\Administrator\UserManagement\Role\Index::class)->name('panels.administrator.user-management.role.index');
    Route::livewire('/panels/administrator/user-management/permission/index', \App\Livewire\Panels\Administrator\UserManagement\Permission\Index::class)->name('panels.administrator.user-management.permission.index');
    Route::livewire('/panels/administrator/setting-management/function/index', \App\Livewire\Panels\Administrator\SettingManagement\Function\Index::class)->name('panels.administrator.setting-management.function.index');
    Route::livewire('/panels/administrator/setting-management/option/index', \App\Livewire\Panels\Administrator\SettingManagement\Option\Index::class)->name('panels.administrator.setting-management.option.index');
    Route::livewire('/panels/administrator/announcement/index', \App\Livewire\Panels\Administrator\Announcement\Index::class)->name('panels.administrator.announcement.index');

    Route::livewire('/panels/service-center/dashboard/index', \App\Livewire\Panels\ServiceCenter\Dashboard\Index::class)->name('panels.service-center.dashboard.index');
    Route::livewire('/panels/service-center/assembly/index', \App\Livewire\Panels\ServiceCenter\Assembly\Index::class)->name('panels.service-center.assembly.index');
    Route::livewire('/panels/service-center/repair/index', \App\Livewire\Panels\ServiceCenter\Repair\Index::class)->name('panels.service-center.repair.index');

    Route::livewire('/panels/workspace/dashboard/index', \App\Livewire\Panels\Workspace\Dashboard\Index::class)->name('panels.workspace.dashboard.index');
    Route::livewire('/panels/workspace/task/index', \App\Livewire\Panels\Workspace\Task\Index::class)->name('panels.workspace.task.index');
    Route::livewire('/panels/workspace/review/index', \App\Livewire\Panels\Workspace\Review\Index::class)->name('panels.workspace.review.index');
    Route::livewire('/panels/workspace/task/create', \App\Livewire\Panels\Workspace\Task\Create::class)->name('panels.workspace.task.create');
    Route::livewire('/panels/workspace/task/{task}/edit', \App\Livewire\Panels\Workspace\Task\Edit::class)->name('panels.workspace.task.edit');
    Route::livewire('/panels/workspace/task/{task}/assigns', \App\Livewire\Panels\Workspace\Task\Assigns::class)->name('panels.workspace.task.assigns');
    Route::livewire('/panels/workspace/review/user/index', \App\Livewire\Panels\Workspace\Review\User\Index::class)->name('panels.workspace.review.user.index');
    Route::livewire('/panels/workspace/review/user/{user}/board', \App\Livewire\Panels\Workspace\Review\User\Board::class)->name('panels.workspace.review.user.board');

    Route::livewire('/panels/accounting/dashboard/index', \App\Livewire\Panels\Accounting\Dashboard\Index::class)->name('panels.accounting.dashboard.index');
    Route::livewire('/panels/accounting/bank/index', \App\Livewire\Panels\Accounting\Bank\Index::class)->name('panels.accounting.bank.index');
    Route::livewire('/panels/accounting/invoice/index', \App\Livewire\Panels\Accounting\Invoice\Index::class)->name('panels.accounting.invoice.index');
    Route::livewire('/panels/accounting/inventory-receipt/index', \App\Livewire\Panels\Accounting\InventoryReceipt\Index::class)->name('panels.accounting.inventory-receipt.index');
    Route::livewire('/panels/accounting/item/index', \App\Livewire\Panels\Accounting\Item\Index::class)->name('panels.accounting.item.index');
    Route::livewire('/panels/accounting/grouping/index/{groupingId?}', \App\Livewire\Panels\Accounting\Grouping\Index::class)->name('panels.accounting.grouping.index');
    Route::livewire('/panels/accounting/party/index', \App\Livewire\Panels\Accounting\Party\Index::class)->name('panels.accounting.party.index');
    Route::livewire('/panels/accounting/payment-header/index', \App\Livewire\Panels\Accounting\PaymentHeader\Index::class)->name('panels.accounting.payment-header.index');
    Route::livewire('/panels/accounting/receipt-header/index', \App\Livewire\Panels\Accounting\ReceiptHeader\Index::class)->name('panels.accounting.receipt-header.index');
    Route::livewire('/panels/accounting/receipt-cheque/index', \App\Livewire\Panels\Accounting\ReceiptCheque\Index::class)->name('panels.accounting.receipt-cheque.index');
    Route::livewire('/panels/accounting/payment-cheque/index', \App\Livewire\Panels\Accounting\PaymentCheque\Index::class)->name('panels.accounting.payment-cheque.index');

    Route::livewire('/logout', \App\Livewire\Auth\Logout::class)->name('logout');

});

Route::get('/invoice/{invoiceId}/view', \App\Livewire\Panels\Customer\Invoice\View::class)
    ->name('invoice.view')
    ->middleware('signed');
