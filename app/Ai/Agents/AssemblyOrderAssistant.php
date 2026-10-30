<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class AssemblyOrderAssistant implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
تو دستیار ساخت «دستور ساخت / اسمبل» فارسی‌زبان هستی.
کاربر توضیح آزاد درباره سفارش اسمبل می‌دهد.
باید فیلدهای فرم را استخراج کنی.

قواعد:
- فقط بر اساس متن ورودی قضاوت کن؛ حدس بی‌پایه نزن.
- اگر فیلدی در متن نیست، مقدار خالی یا صفر مناسب برگردان.
- quantity باید عدد صحیح مثبت باشد؛ اگر معلوم نیست 0 برگردان.
- notes را به فارسی و کوتاه بنویس.
- parts یک آرایه از قطعات پیشنهادی است؛ اگر قطعه‌ای در متن نیست آرایه خالی برگردان.
- برای هر قطعه title فارسی/نام کالا، quantity_per_unit عدد صحیح، requires_serial بولین.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'quantity' => $schema->integer()->required(),
            'notes' => $schema->string()->required(),
            'parts' => $schema->array()->items(
                $schema->object(fn (JsonSchema $object): array => [
                    'title' => $object->string()->required(),
                    'quantity_per_unit' => $object->integer()->required(),
                    'requires_serial' => $object->boolean()->required(),
                    'notes' => $object->string()->required(),
                ])
            )->required(),
        ];
    }
}
