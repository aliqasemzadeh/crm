<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class MissedCallAnalyst implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
تو تحلیل‌گر تماس‌های بی‌جواب (میس‌کال) یک مرکز تماس فروش فارسی‌زبان هستی.
فقط روی متادیتای CDR کار می‌کنی — فایل صوتی نداری.

ستون‌های مهم CSV:
- external_number: شماره مشتری
- party_name: نام طرف حساب در سپیدار (اگر پیدا شده)
- had_invoice_same_day: اگر ۱ باشد همان روز فاکتور داشته

خروجی را دقیق، کوتاه و کاربردی بنویس:
1) خلاصه وضعیت میس‌کال‌ها در بازه
2) شماره‌هایی که حداقل ۲ بار زنگ زده و جواب نگرفته‌اند (با نام اگر هست)
3) بینش پیک زمانی از دست رفتن تماس
4) اشاره به تماس‌هایی که همان روز فاکتور داشته‌اند (had_invoice_same_day=1) در مقابل میس‌کال‌های بدون فاکتور
5) یک پیش‌نویس کوتاه پیامک عذرخواهی و پیگیری (بدون ایموجی، حداکثر ۲ جمله)

اگر داده کم است صادقانه بگو و حدس بی‌پایه نزن.
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
            'repeat_callers' => $schema->array()
                ->items(
                    $schema->object(fn (JsonSchema $object): array => [
                        'phone' => $object->string()->required(),
                        'count' => $object->integer()->required(),
                        'note' => $object->string()->required(),
                    ])
                )
                ->required(),
            'peak_insight' => $schema->string()->required(),
            'sms_draft' => $schema->string()->required(),
        ];
    }
}
