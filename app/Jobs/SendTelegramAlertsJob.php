<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendTelegramAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 3;

    public function handle(): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = 955149250;
        //$chatIds = 1951908603; //Алексей


        if (! $token) {
            logger()->error('Telegram token is empty');
            return;
        }

        $products = Product::whereColumn('price', '<', 'min_price')
            ->where('sent_alert', false)
            ->get();

        foreach ($products as $product) {

            $days = $this->deliveryToDays($product->delivery);

            if ($days > 5) {
                continue;
            }

            $text =
                "📉 Цена снизилась!\n\n" .
                "🛒 {$product->title}\n" .
                "💰 Цена: {$product->price}\n" .
                "📉 Мин: {$product->min_price}\n" .
                "🚚 {$product->delivery}\n\n" .
                "{$product->url}";

            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$token}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]
            );

            if ($response->failed()) {
                logger()->error('Telegram send failed', [
                    'product_id' => $product->id,
                    'response' => $response->body(),
                ]);
                continue;
            }

            $product->update(['sent_alert' => true]);
        }
    }

    private function deliveryToDays(string $delivery): int
    {
        $delivery = trim(mb_strtolower($delivery));

        if ($delivery === 'завтра') return 1;
        if ($delivery === 'послезавтра') return 2;

        if (preg_match('/за\s*(\d*)\s*час/iu', $delivery)) {
            return 1;
        }

        $months = [
            'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
            'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
            'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12,
        ];

        if (preg_match('/(\d+)\s+([а-я]+)/u', $delivery, $m)) {
            $day = (int) $m[1];
            $month = $months[$m[2]] ?? null;

            if ($month) {
                $date = now()->setDate(now()->year, $month, $day);
                if ($date->isPast()) {
                    $date->addYear();
                }
                return now()->diffInDays($date);
            }
        }

        return 999;
    }
}
