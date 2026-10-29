<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class RepairAdmissionAssistant implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
تو دستیار پذیرش مرکز خدمات (تعمیرات) فارسی‌زبان هستی.
کاربر توضیح آزاد درباره مشتری و دستگاه می‌دهد (متن یا پیاده‌سازی صوت).
باید فیلدهای فرم پذیرش را استخراج و مرتب کنی.

قواعد:
- فقط بر اساس متن ورودی قضاوت کن؛ حدس بی‌پایه نزن.
- اگر فیلدی در متن نیست، رشته خالی برگردان.
- warranty_type فقط یکی از: yes، no، یا رشته خالی.
- شماره‌ها را به صورت ارقام لاتین نرمال کن (مثلاً ۰۹۱۲… → 0912…).
- device_problem را واضح و کوتاه بنویس.
- device_accessories لوازم همراه را جدا با ویرگول بنویس.
- device_description و admission_description را در صورت وجود جزئیات تکمیل کن.
- همه متن‌های توضیحی به فارسی باشند.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'owner_name' => $schema->string()->required(),
            'owner_organization' => $schema->string()->required(),
            'owner_mobile' => $schema->string()->required(),
            'owner_email' => $schema->string()->required(),
            'owner_national_code' => $schema->string()->required(),
            'owner_address' => $schema->string()->required(),
            'warranty_type' => $schema->string()->required(),
            'warranty_date' => $schema->string()->required(),
            'device_type' => $schema->string()->required(),
            'device_brand' => $schema->string()->required(),
            'device_model' => $schema->string()->required(),
            'device_serial_number' => $schema->string()->required(),
            'device_problem' => $schema->string()->required(),
            'device_accessories' => $schema->string()->required(),
            'device_description' => $schema->string()->required(),
            'admission_description' => $schema->string()->required(),
        ];
    }
}
