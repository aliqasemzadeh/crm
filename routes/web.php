<?php

use Illuminate\Support\Facades\Route;


Route::middleware(['guest'])->group( function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/register', 'pages::auth.register')->name('register');
});

Route::livewire('/forget-password', 'pages::auth.forget-password')->name('forget-password');
Route::livewire('/change-password/{token}', 'pages::auth.change-password')->name('change-password');

Route::middleware(['auth'])->group( function () {
    Route::livewire('/panels/crm/dashboard/index', 'pages::panels.crm.dashboard.index')->name('panels.crm.dashboard.index');
    Route::livewire('/panels/crm/follow-up/index', 'pages::panels.crm.follow-up.index')->name('panels.crm.follow-up.index');
    Route::livewire('/panels/crm/cash-back-rule/index', 'pages::panels.crm.cash-back-rule.index')->name('panels.crm.cash-back-rule.index');

    Route::livewire('/', 'pages::panels.user.dashboard.index')->name('home');
    Route::livewire('/panels/user/dashboard/index', 'pages::panels.user.dashboard.index')->name('panels.user.dashboard.index');
    Route::livewire('/panels/user/setting/change-password', 'pages::panels.user.setting.change-password')->name('panels.user.setting.change-password');
    Route::livewire('/panels/user/setting/change-email', 'pages::panels.user.setting.change-email')->name('panels.user.setting.change-email');
    Route::livewire('/panels/user/setting/change-mobile', 'pages::panels.user.setting.change-mobile')->name('panels.user.setting.change-mobile');


    Route::livewire('/panels/administrator/dashboard/index', 'pages::panels.administrator.dashboard.index')->name('panels.administrator.dashboard.index');
    Route::livewire('/panels/administrator/user-management/user/index', 'pages::panels.administrator.user-management.user.index')->name('panels.administrator.user-management.user.index');
    Route::livewire('/panels/administrator/user-management/role/index', 'pages::panels.administrator.user-management.role.index')->name('panels.administrator.user-management.role.index');
    Route::livewire('/panels/administrator/user-management/permission/index', 'pages::panels.administrator.user-management.permission.index')->name('panels.administrator.user-management.permission.index');
    Route::livewire('/panels/administrator/setting-management/function/index', \App\Livewire\Panels\Administrator\SettingManagement\Function\Index::class)->name('panels.administrator.setting-management.function.index');
    Route::livewire('/panels/administrator/setting-management/option/index', \App\Livewire\Panels\Administrator\SettingManagement\Option\Index::class)->name('panels.administrator.setting-management.option.index');
    Route::livewire('/panels/administrator/announcement/index', \App\Livewire\Panels\Administrator\Announcement\Index::class)->name('panels.administrator.announcement.index');

    Route::livewire('/panels/service-center/dashboard/index', 'pages::panels.service-center.dashboard.index')->name('panels.service-center.dashboard.index');
    Route::livewire('/panels/service-center/assembly/index', \App\Livewire\Panels\ServiceCenter\Assembly\Index::class)->name('panels.service-center.assembly.index');
    Route::livewire('/panels/service-center/repair/index', 'pages::panels.service-center.repair.index')->name('panels.service-center.repair.index');

    Route::livewire('/panels/warehouse/dashboard/index', 'pages::panels.warehouse.dashboard.index')->name('panels.warehouse.dashboard.index');
    Route::livewire('/panels/warehouse/item/index', 'pages::panels.warehouse.item.index')->name('panels.warehouse.item.index');
    Route::livewire('/panels/warehouse/history/index', 'pages::panels.warehouse.history.index')->name('panels.warehouse.history.index');
    Route::livewire('/panels/warehouse/check/index', 'pages::panels.warehouse.check.index')->name('panels.warehouse.check.index');
    Route::livewire('/panels/warehouse/admin-check/index', 'pages::panels.warehouse.admin-check.index')->name('panels.warehouse.admin-check.index');

    Route::livewire('/panels/workspace/dashboard/index', \App\Livewire\Panels\Workspace\Dashboard\Index::class)->name('panels.workspace.dashboard.index');
    Route::livewire('/panels/workspace/task/index', \App\Livewire\Panels\Workspace\Task\Index::class)->name('panels.workspace.task.index');
    Route::livewire('/panels/workspace/purchase-request/index', \App\Livewire\Panels\Workspace\PurchaseRequest\Index::class)->name('panels.workspace.purchase-request.index');
    Route::livewire('/panels/workspace/review/index', \App\Livewire\Panels\Workspace\Review\Index::class)->name('panels.workspace.review.index');
    Route::livewire('/panels/workspace/task/create', \App\Livewire\Panels\Workspace\Task\Create::class)->name('panels.workspace.task.create');
    Route::livewire('/panels/workspace/task/{task}/edit', \App\Livewire\Panels\Workspace\Task\Edit::class)->name('panels.workspace.task.edit');
    Route::livewire('/panels/workspace/task/{task}/assigns', \App\Livewire\Panels\Workspace\Task\Assigns::class)->name('panels.workspace.task.assigns');
    Route::livewire('/panels/workspace/review/user/index', \App\Livewire\Panels\Workspace\Review\User\Index::class)->name('panels.workspace.review.user.index');
    Route::livewire('/panels/workspace/review/user/{user}/board', \App\Livewire\Panels\Workspace\Review\User\Board::class)->name('panels.workspace.review.user.board');

    Route::livewire('/panels/workspace/instruction/index', \App\Livewire\Panels\Workspace\Instruction\Index::class)->name('panels.workspace.instruction.index');

    Route::livewire('/panels/accounting/dashboard/index', \App\Livewire\Panels\Accounting\Dashboard\Index::class)->name('panels.accounting.dashboard.index');
    Route::livewire('/panels/accounting/bank/index', \App\Livewire\Panels\Accounting\Bank\Index::class)->name('panels.accounting.bank.index');
    Route::livewire('/panels/accounting/invoice/index', \App\Livewire\Panels\Accounting\Invoice\Index::class)->name('panels.accounting.invoice.index');
    Route::livewire('/panels/accounting/invoice/create', 'pages::panels.accounting.invoice.create')->name('panels.accounting.invoice.create');
    Route::livewire('/panels/accounting/invoice/edit/{invoice}', 'pages::panels.accounting.invoice.edit')->name('panels.accounting.invoice.edit');
    Route::livewire('/panels/accounting/invoice/view/{invoice}', 'pages::panels.accounting.invoice.view')->name('panels.accounting.invoice.view');
    Route::livewire('/panels/accounting/invoice/print/{invoice}', 'pages::panels.accounting.invoice.print')->name('panels.accounting.invoice.print');
    Route::livewire('/panels/accounting/inventory-receipt/index', \App\Livewire\Panels\Accounting\InventoryReceipt\Index::class)->name('panels.accounting.inventory-receipt.index');
    Route::livewire('/panels/accounting/item/index', \App\Livewire\Panels\Accounting\Item\Index::class)->name('panels.accounting.item.index');
    Route::livewire('/panels/accounting/item/{item}', 'pages::panels.accounting.item.show')->name('panels.accounting.item.show');
    Route::livewire('/panels/accounting/grouping/index/{groupingId?}', \App\Livewire\Panels\Accounting\Grouping\Index::class)->name('panels.accounting.grouping.index');
    Route::livewire('/panels/accounting/price-note/index/{groupingId?}', \App\Livewire\Panels\Accounting\PriceNote\Index::class)->name('panels.accounting.price-note.index');
    Route::livewire('/panels/accounting/party/index', \App\Livewire\Panels\Accounting\Party\Index::class)->name('panels.accounting.party.index');
    Route::livewire('/panels/accounting/payment-header/index', \App\Livewire\Panels\Accounting\PaymentHeader\Index::class)->name('panels.accounting.payment-header.index');
    Route::livewire('/panels/accounting/receipt-header/index', \App\Livewire\Panels\Accounting\ReceiptHeader\Index::class)->name('panels.accounting.receipt-header.index');
    Route::livewire('/panels/accounting/receipt-cheque/index', \App\Livewire\Panels\Accounting\ReceiptCheque\Index::class)->name('panels.accounting.receipt-cheque.index');
    Route::livewire('/panels/accounting/payment-cheque/index', \App\Livewire\Panels\Accounting\PaymentCheque\Index::class)->name('panels.accounting.payment-cheque.index');
    Route::livewire('/panels/accounting/account/index', \App\Livewire\Panels\Accounting\Account\Index::class)->name('panels.accounting.account.index');
    Route::livewire('/panels/accounting/full-report/index', \App\Livewire\Panels\Accounting\FullReport\Index::class)->name('panels.accounting.full-report.index');
    Route::livewire('/panels/accounting/full-report/party/{party}', \App\Livewire\Panels\Accounting\FullReport\Party::class)->name('panels.accounting.full-report.party.show');
    Route::livewire('/panels/accounting/tax/index', \App\Livewire\Panels\Accounting\Tax\Index::class)->name('panels.accounting.tax.index');
    Route::livewire('/panels/accounting/user/index', 'pages::panels.accounting.user.index')->name('panels.accounting.user.index');
    Route::livewire('/panels/accounting/user/{sepidarUser}/report', 'pages::panels.accounting.user.report')->name('panels.accounting.user.report');

    Route::livewire('/panels/sale/dashboard/index', 'pages::panels.sale.dashboard.index')->name('panels.sale.dashboard.index');
    Route::livewire('/panels/sale/invoice/index', 'pages::panels.sale.invoice.index')->name('panels.sale.invoice.index');
    Route::livewire('/panels/sale/invoice/create', 'pages::panels.sale.invoice.create')->name('panels.sale.invoice.create');
    Route::livewire('/panels/sale/invoice/edit/{invoice}', 'pages::panels.sale.invoice.edit')->name('panels.sale.invoice.edit');
    Route::livewire('/panels/sale/invoice/view/{invoice}', 'pages::panels.sale.invoice.view')->name('panels.sale.invoice.view');
    Route::livewire('/panels/sale/invoice/print/{invoice}', 'pages::panels.sale.invoice.print')->name('panels.sale.invoice.print');
    Route::livewire('/panels/sale/item/index', 'pages::panels.sale.item.index')->name('panels.sale.item.index');
    Route::livewire('/panels/sale/item/{item}', 'pages::panels.sale.item.view')->name('panels.sale.item.view');
    Route::livewire('/panels/sale/item-price/index/{groupingId?}', 'pages::panels.sale.item-price.index')->name('panels.sale.item-price.index');
    Route::livewire('/panels/sale/party/index', 'pages::panels.sale.party.index')->name('panels.sale.party.index');
    Route::livewire('/panels/sale/party/{party}', 'pages::panels.sale.party.view')->name('panels.sale.party.view');

    Route::get('/item-image/{itemId}', function ($itemId) {
        return \Illuminate\Support\Facades\Cache::remember("item_image_$itemId", now()->addDays(7), function () use ($itemId) {
            $image = \App\Models\Sepidar\INV\ItemImage::where('ItemRef', $itemId)->first();
            if (!$image || !$image->Image) {
                return abort(404);
            }
            $imageData = $image->Image;
            // Handle binary if it's hexadecimal string (common in SQL Server)
            if (is_string($imageData) && str_starts_with($imageData, '0x')) {
                $imageData = pack("H*", substr($imageData, 2));
            }
            return response($imageData)->header('Content-Type', 'image/jpeg');
        });
    })->name('item.image');

    Route::livewire('/logout', 'pages::auth.logout')->name('logout');

});

Route::get('/invoice/{invoiceId}/view', \App\Livewire\Panels\Customer\Invoice\View::class)
    ->name('panels.customer.invoice.view')
    ->middleware('signed');
