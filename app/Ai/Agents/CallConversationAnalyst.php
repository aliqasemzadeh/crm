<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class CallConversationAnalyst implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
تو تحلیل‌گر مکالمات مرکز تماس فروش فارسی‌زبان هستی.
متن پیاده‌سازی‌شدهٔ تماس‌های یک مشتری را می‌گیری و باید بفهمی مشتری چه می‌خواست.

قواعد:
- فقط بر اساس متن مکالمه قضاوت کن؛ حدس بی‌پایه نزن.
- خلاصه را کوتاه، کاربردی و به فارسی بنویس.
- اگر مکالمه نامفهوم یا خیلی کوتاه است صادقانه بگو.
- اگر نشانه‌ای از خرید/سفارش/فاکتور در مکالمه هست در sale_outcome_hint بنویس.
- follow_up_suggestion یک پیشنهاد کوتاه پیگیری برای کارشناس فروش باشد.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'wanted_summary' => $schema->string()->required(),
            'key_points' => $schema->array()
                ->items($schema->string())
                ->required(),
            'sale_outcome_hint' => $schema->string()->required(),
            'follow_up_suggestion' => $schema->string()->required(),
        ];
    }
}
