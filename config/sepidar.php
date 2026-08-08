<?php

return [
    'FiscalYearRef' => env('FISCAL_YEAR_REF', 5),
    'BaseRate' => 1280000,
    'Creator' => env('SEPIDAR_CREATOR', 1),
    'CurrencyRef' => env('SEPIDAR_CURRENCY_REF', 1),
    'InvoiceSLRef' => env('SEPIDAR_INVOICE_SL_REF'),
    'InvoiceState' => env('SEPIDAR_INVOICE_STATE', 1),
    'SettlementType' => env('SEPIDAR_SETTLEMENT_TYPE', 1),
    'DefaultStockRef' => env('SEPIDAR_DEFAULT_STOCK_REF'),
];
