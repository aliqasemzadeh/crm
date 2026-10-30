<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class AssemblySerialAssistant implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
تو دستیار اسمبل مرکز خدمات فارسی‌زبان هستی.
کاربر وضعیت قطعات و سریال‌های یک سیستم اسمبل را توضیح می‌دهد یا لیست قطعات بدون سریال را می‌فرستد.
باید پیشنهاد تکمیل بدهی.

قواعد:
- فقط بر اساس متن ورودی قضاوت کن.
- system_serial را اگر در متن هست برگردان؛ وگرنه رشته خالی.
- notes یادداشت کوتاه فارسی برای تکنسین.
- missing_parts_summary خلاصه قطعات ناقص به فارسی.
- suggestions آرایه‌ای از پیشنهادها با part_title و suggested_serial (اگر سریال در متن نبود خالی).
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'system_serial' => $schema->string()->required(),
            'notes' => $schema->string()->required(),
            'missing_parts_summary' => $schema->string()->required(),
            'suggestions' => $schema->array()->items(
                $schema->object(fn (JsonSchema $object): array => [
                    'part_title' => $object->string()->required(),
                    'suggested_serial' => $object->string()->required(),
                ])
            )->required(),
        ];
    }
}
