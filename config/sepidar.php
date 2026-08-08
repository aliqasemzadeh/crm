<?php

return [
    'FiscalYearRef' => env('FISCAL_YEAR_REF', 3),
    'BaseRate' => 1280000,
    'Creator' => env('SEPIDAR_CREATOR', 1),
    'CurrencyRef' => env('SEPIDAR_CURRENCY_REF', 1),
    'InvoiceSLRef' => env('SEPIDAR_INVOICE_SL_REF', 117),
    'InvoiceState' => env('SEPIDAR_INVOICE_STATE', 1),
    'SettlementType' => env('SEPIDAR_SETTLEMENT_TYPE', 1),
    'DefaultStockRef' => env('SEPIDAR_DEFAULT_STOCK_REF', 2),
    'DeliveryLocationRef' => env('SEPIDAR_DELIVERY_LOCATION_REF', 1),
    'TaxPercent' => env('SEPIDAR_TAX_PERCENT', 10),
    'TaxSLRef' => env('SEPIDAR_TAX_SL_REF', 531),
    'CogsSLRef' => env('SEPIDAR_COGS_SL_REF', 262),
    'VoucherType' => env('SEPIDAR_VOUCHER_TYPE', 2),
    'VoucherState' => env('SEPIDAR_VOUCHER_STATE', 1),
    'VoucherIssuerSystem' => env('SEPIDAR_VOUCHER_ISSUER_SYSTEM', 0),
    'DeliveryType' => env('SEPIDAR_DELIVERY_TYPE', 1),
    'DeliveryCreatorForm' => env('SEPIDAR_DELIVERY_CREATOR_FORM', 1),
    'InvoiceIssuerEntityName' => 'SG.Sales.InvoiceManagement.Common.DsInvoice, SG.Sales.InvoiceManagement.Common, Version=1.0.0.0, Culture=neutral, PublicKeyToken=null',
];
