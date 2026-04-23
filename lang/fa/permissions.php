<?php

return [
    'user' => [
        'user_access' => 'دسترسی کاربر',
        'user_dashboard' => 'داشبورد کاربر',
        'user_settings' => 'تنظیمات کاربر',
        'user_support' => 'پشتیبانی کاربر',
        'user_announcement_display' => 'مشاهده اطلاعیه ها',
        'user_support_ticket_index' => 'فهرست تیکت‌های پشتیبانی کاربر',
        'user_support_ticket_create' => 'ایجاد تیکت پشتیبانی کاربر',
    ],

    'administrator' => [
        'administrator_access' => 'دسترسی مدیر',

        'administrator_dashboard_index' => 'داشبورد مدیر',
        'administrator_user_management' => 'مدیریت کاربران',

        'administrator_workspace_instruction_manage' => 'مدیریت دستورالعمل ها',

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

        'administrator_announcement_management' => 'مدیریت اطلاعیه‌ها',
        'administrator_announcement_index' => 'فهرست اطلاعیه‌ها',
        'administrator_announcement_create' => 'ایجاد اطلاعیه',
        'administrator_announcement_edit' => 'ویرایش اطلاعیه',
        'administrator_announcement_delete' => 'حذف اطلاعیه',
        'administrator_announcement_users' => 'مشاهده بینندگان اطلاعیه',

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
        'crm_access_dashboard_index' => 'داشبرد CRM',
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

    'accounting' => [
        'accounting_access' => 'دسترسی حسابداری',

        'accounting_dashboard_index' => 'داشبورد حسابداری',
        'accounting_account_index' => 'حساب ها حسابداری',

        'accounting_bank_index' => 'فهرست حساب‌های بانکی',
        'accounting_bank_create' => 'ایجاد حساب بانکی',
        'accounting_bank_edit' => 'ویرایش حساب بانکی',
        'accounting_bank_delete' => 'حذف حساب بانکی',

        'accounting_payment_header_index' => 'فهرست پرداخت‌ها',
        'accounting_payment_header_create' => 'ایجاد پرداخت',
        'accounting_payment_header_edit' => 'ویرایش پرداخت',
        'accounting_payment_header_delete' => 'حذف پرداخت',

        'accounting_receipt_header_index' => 'فهرست دریافت‌ها',
        'accounting_receipt_header_create' => 'ایجاد دریافت',
        'accounting_receipt_header_edit' => 'ویرایش دریافت',
        'accounting_receipt_header_delete' => 'حذف دریافت',

        'accounting_grouping_index' => 'فهرست گروه‌بندی‌ها',
        'accounting_grouping_create' => 'ایجاد گروه‌بندی',
        'accounting_grouping_edit' => 'ویرایش گروه‌بندی',
        'accounting_grouping_delete' => 'حذف گروه‌بندی',

        'accounting_inventory_receipt_index' => 'فهرست رسیدهای انبار',
        'accounting_inventory_receipt_create' => 'ایجاد رسید انبار',
        'accounting_inventory_receipt_edit' => 'ویرایش رسید انبار',
        'accounting_inventory_receipt_delete' => 'حذف رسید انبار',

        'accounting_payment_cheque_index' => 'فهرست چک‌های پرداختی',
        'accounting_payment_cheque_create' => 'ایجاد چک پرداختی',
        'accounting_payment_cheque_edit' => 'ویرایش چک پرداختی',
        'accounting_payment_cheque_delete' => 'حذف چک پرداختی',
        'accounting_payment_cheque_import' => 'وارد کردن چک‌های پرداختی',

        'accounting_receipt_cheque_index' => 'فهرست چک‌های دریافتی',
        'accounting_receipt_cheque_create' => 'ایجاد چک دریافتی',
        'accounting_receipt_cheque_edit' => 'ویرایش چک دریافتی',
        'accounting_receipt_cheque_delete' => 'حذف چک دریافتی',
        'accounting_receipt_cheque_import' => 'وارد کردن چک‌های دریافتی',

        'accounting_item_index' => 'فهرست کالا',
        'accounting_item_create' => 'ایجاد کالا',
        'accounting_item_edit' => 'ویرایش کالا',
        'accounting_item_delete' => 'حذف کالا',

        'accounting_invoice_index' => 'فهرست فاکتورها',
        'accounting_invoice_create' => 'ایجاد فاکتور',
        'accounting_invoice_edit' => 'ویرایش فاکتور',
        'accounting_invoice_delete' => 'حذف فاکتور',

        'accounting_party_index' => 'فهرست طرف حساب‌ها',
        'accounting_party_create' => 'ایجاد طرف حساب',
        'accounting_party_edit' => 'ویرایش طرف حساب',
        'accounting_party_phone' => 'تلفن طرف حساب',
        'accounting_party_address' => 'آدرس طرف حساب',
        'accounting_party_delete' => 'حذف طرف حساب',


        'accounting_profit_index' => 'مشاهده سود',

        'accounting_price_note_index' => 'مشاهده سود',
        'accounting_price_note_edit' => 'ویرایش اعلام قیمت',
        'accounting_price_note_site_edit' => 'ویرایش اعلان قیمت سایت',
        'accounting_price_note_fetchers' => 'دریافت کننده قیمت',
        'accounting_price_note_item_fee' => 'قیمت گذاری',
        'accounting_price_note_sale_price' => 'قیمت فروش',
        'accounting_price_note_purchase_price' => 'قیمت خرید',
        'accounting_price_note_stock_summary' => 'موجودی انبار',

        'accounting_full_report_index' => 'گزارش جامع حساب ها',
        'accounting_tax_index' => 'گزارش مالیاتی',
    ],

    'workspace' => [
        'workspace_access' => 'میزکار',
        'workspace_dashboard_index' => 'داشبرد میزکار',
        'workspace_dashboard_task_create' => 'ایجاد وظیفه در میزکار',
        'workspace_dashboard_task_edit' => 'ویرایش وظیفه در میزکار',
        'workspace_task_index' => 'لیست وظایف',
        'workspace_task_create' => 'ایجاد وظیفه',
        'workspace_task_edit' => 'ویرایش وظیفه',
        'workspace_task_delete' => 'ویرایش وظیفه',
    ],

    'workspace_review' => [
        'workspace_access' => 'میزکار',
        'workspace_review_index' => 'بررسی وظایف',
        'workspace_review_edit' => 'ویرایش بررسی وظایف',
        'workspace_review_approve' => 'تایید وظیفه',
        'workspace_review_reject' => 'رد وظیفه',
        'workspace_review_user_task' => 'مشاهده وظایف',
        'workspace_review_user_task_board' => 'مشاهده برد وظایف',
        'workspace_review_user_task_edit' => 'ویرایش بررسی وظایف',
        'workspace_review_user_task_delete' => 'حذف بررسی وظایف',
        'workspace_review_user_task_assign' => 'تخصیص وظیفه بررسی وظایف',
    ],


    'sales' => [
        'sales_access' => 'فروش',
    ],

];
