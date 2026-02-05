<?php

return [
    'user' => [
        'user_access' => 'دسترسی کاربر',
        'user_dashboard' => 'داشبورد کاربر',
        'user_settings' => 'تنظیمات کاربر',
        'user_support' => 'پشتیبانی کاربر',
        'user_support_ticket_index' => 'فهرست تیکت‌های پشتیبانی کاربر',
        'user_support_ticket_create' => 'ایجاد تیکت پشتیبانی کاربر',
    ],
    'administrator' => [
        'administrator_access' => 'دسترسی مدیر',

        'administrator_dashboard_index' => 'داشبورد مدیر',
        'administrator_user_management' => 'مدیریت کاربران',

        'administrator_user_management_index' => 'فهرست کاربران',
        'administrator_user_management_create' => 'ایجاد کاربر',
        'administrator_user_management_edit' => 'ویرایش کاربر',
        'administrator_user_management_delete' => 'حذف کاربر',
        'administrator_user_management_roles' => 'نقش‌های کاربر',
        'administrator_user_management_permissions' => 'مجوزهای کاربر',

        'administrator_user_management_role_index' => 'فهرست نقش‌ها',
        'administrator_user_management_role_create' => 'ایجاد نقش',
        'administrator_user_management_role_edit' => 'ویرایش نقش',
        'administrator_user_management_role_delete' => 'حذف نقش',
        'administrator_user_management_role_users' => 'کاربران نقش',
        'administrator_user_management_role_permissions' => 'مجوزهای نقش',

        'administrator_user_management_permission_index' => 'فهرست مجوزها',
        'administrator_user_management_permission_create' => 'ایجاد مجوز',
        'administrator_user_management_permission_edit' => 'ویرایش مجوز',
        'administrator_user_management_permission_delete' => 'حذف مجوز',

        'administrator_content_management' => 'مدیریت محتوا',

        'administrator_content_category_index' => 'فهرست دسته‌بندی‌های محتوا',
        'administrator_content_category_create' => 'ایجاد دسته‌بندی محتوا',
        'administrator_content_category_edit' => 'ویرایش دسته‌بندی محتوا',
        'administrator_content_category_delete' => 'حذف دسته‌بندی محتوا',

        'administrator_content_faq_index' => 'فهرست پرسش‌های متداول',
        'administrator_content_faq_create' => 'ایجاد پرسش متداول',
        'administrator_content_faq_edit' => 'ویرایش پرسش متداول',
        'administrator_content_faq_delete' => 'حذف پرسش متداول',

        'administrator_content_article_index' => 'فهرست مقالات',
        'administrator_content_article_create' => 'ایجاد مقاله',
        'administrator_content_article_edit' => 'ویرایش مقاله',
        'administrator_content_article_delete' => 'حذف مقاله',

        'administrator_support_management' => 'مدیریت پشتیبانی',
        'administrator_support_ticket_index' => 'فهرست تیکت‌ها',
        'administrator_support_ticket_view' => 'نمایش تیکت',
        'administrator_support_ticket_replay' => 'پاسخ به تیکت',

        'administrator_setting_management' => 'مدیریت تنظیمات',

        'administrator_setting_category_index' => 'فهرست دسته‌بندی‌های تنظیمات',
        'administrator_setting_category_create' => 'ایجاد دسته‌بندی تنظیمات',
        'administrator_setting_category_edit' => 'ویرایش دسته‌بندی تنظیمات',
        'administrator_setting_category_delete' => 'حذف دسته‌بندی تنظیمات',

        'administrator_setting_option_index' => 'فهرست گزینه‌های تنظیمات',

        // API permissions
        'administrator_api_access' => 'دسترسی API مدیریت',
        'administrator_api_ticket_management' => 'مدیریت تیکت‌ها (API)',
        'administrator_api_ticket_index' => 'فهرست تیکت‌ها (API)',
        'administrator_api_ticket_view' => 'مشاهده تیکت (API)',
        'administrator_api_ticket_replay' => 'پاسخ به تیکت (API)',
        'administrator_api_ticket_categories' => 'دسته‌بندی‌های تیکت (API)',
        'administrator_api_ticket_download_file' => 'دانلود فایل تیکت (API)',

        'administrator_api_faq_management' => 'مدیریت پرسش‌های متداول (API)',
        'administrator_api_faq_index' => 'فهرست پرسش‌های متداول (API)',
        'administrator_api_faq_show' => 'نمایش پرسش متداول (API)',
        'administrator_api_faq_search' => 'جستجوی پرسش‌های متداول (API)',
    ],

    'crm' => [
        'crm_access' => 'دسترسی CRM',
    ],

    'service_center' => [
        'service_center_access' => 'مرکز خدمات',

        'service_center_dashboard_index' => 'داشبرد مرکز خدمات',
        'service_center_assembly_index' => 'اسمبل مرکز خدمات',
        'service_center_repair_index' => 'تعمیرات مرکز خدمات',
        'service_center_repair_create' => 'پذیرش تعمیرات مرکز خدمات',
        'service_center_repair_services' => 'خدمات (هزینه ها) تعمیرات مرکز خدمات',
        'service_center_repair_logs' => 'تغییرات Logs تعمیرات مرکز خدمات',
        'service_center_repair_edit' => 'ویرایش پذیرش مرکز خدمات',
        'service_center_repair_view' => 'مشاهده پذیرش مرکز خدمات',
    ],

    'shop' => [
        'shop_access' => 'فروشگاه',
    ],

    'accounting' => [
        'accounting_access' => 'دسترسی حساب داری',
        'accounting_bank_index' => 'فهرست حساب‌های بانکی',
        'accounting_bank_create' => 'ایجاد حساب بانکی',
        'accounting_bank_edit' => 'ویرایش حساب بانکی',
        'accounting_bank_delete' => 'حذف حساب بانکی',

        'accounting_bank_remittance_index' => 'فهرست حواله‌ها',
        'accounting_bank_remittance_create' => 'ایجاد حواله',
        'accounting_bank_remittance_edit' => 'ویرایش حواله',
        'accounting_bank_remittance_delete' => 'حذف حواله',
        'accounting_bank_remittance_check' => 'بررسی حواله',
        'accounting_bank_remittance_request' => 'درخواست حواله',
        'accounting_bank_remittance_transfer' => 'پرداخت حواله',

        'accounting_bank_transaction_index' => 'فهرست تراکنش‌ها',
        'accounting_bank_transaction_create' => 'ایجاد تراکنش',
        'accounting_bank_transaction_edit' => 'ویرایش تراکنش',
        'accounting_bank_transaction_delete' => 'حذف تراکنش',

        'accounting_remittance_index' => 'فهرست تراکنش‌ها',
        'accounting_remittance_create' => 'ایجاد تراکنش',
        'accounting_remittance_edit' => 'ویرایش تراکنش',
        'accounting_remittance_delete' => 'حذف تراکنش',

        'accounting_cheque_index' => 'فهرست تراکنش‌ها',
        'accounting_cheque_create' => 'ایجاد تراکنش',
        'accounting_cheque_edit' => 'ویرایش تراکنش',
        'accounting_cheque_delete' => 'حذف تراکنش',
        'accounting_cheque_import' => 'حذف تراکنش',
    ],
];
