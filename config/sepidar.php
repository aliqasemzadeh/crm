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

    /*
    |--------------------------------------------------------------------------
    | Accounting dashboard — SG&A (Topic 270) fixed vs variable accounts
    |--------------------------------------------------------------------------
    */
    'FixedCostAccountIds' => [
        // حقوق و مزایا
        265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 283, 284, 285, 286,
        // استهلاک
        330, 331, 333, 334, 335,
        // اجاره و یوتیلیتی
        306, 307, 308, 309, 323, 324, 326,
        // بیمه دارایی / موجودی / آتش‌سوزی
        314, 315, 316,
    ],

    'VariableCostAccountIds' => [
        // تعمیرات، سایت، پذیرایی، کارکنان عملیاتی
        288, 290, 291, 292, 293, 294, 295, 296, 297, 298, 299, 300, 301, 302, 303, 304, 305,
        // ملزومات، پست، آژانس، مطالبات، قطعات، پیک
        310, 311, 312, 313, 317, 318, 319, 325,
        // تبلیغات، فروش، نمایشگاه، واخواست
        336, 337, 338, 339, 343, 346,
    ],

    'OperatingIncomeTopicRef' => 255,
    'OtherOperatingIncomeTopicRef' => 260,
    'CogsTopicRef' => 265,
    'SgaTopicRef' => 270,
    'NonOperatingTopicRef' => 250,
    'FinancialExpenseTopicRef' => 165,
];
