<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Http;

class ProductAlertController extends Controller
{
    public function sendAlerts()
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        //$chatIds = [env('TELEGRAM_CHAT_ID'), 955149250];
        $chatIds = 955149250;

        // выбираем только товары, где price < min_price и уведомление ещё не отправлялось
        $products = Product::whereColumn('price', '<', 'min_price')
            ->where('sent_alert', false)
            ->get();

        foreach ($products as $product) {

            $days = $this->deliveryToDays($product->delivery);

            // Отправляем только если доставка < 5 дней
            if ($days > 5) {
                continue;
            }

            $text = "📉 *Цена снизилась!*\n"
                . "Название: {$product->name}\n"
                . "Цена: {$product->price}\n"
                . "Мин. цена: {$product->min_price}\n"
                . "Доставка: {$product->delivery}\n"
                . "Ссылка: {$product->url}";

            /*foreach ($chatIds as $chatId) {
                Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    //'parse_mode' => 'Markdown',
                ]);
            }*/
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatIds,
                'text' => $text,
                //'parse_mode' => 'Markdown',
            ]);


            // чтобы больше не отправлять второй раз
            $product->sent_alert = true;
            $product->save();
        }


        return response()->json(['status' => 'ok']);
    }



    private function deliveryToDays(string $delivery): int
    {
        $delivery = trim(mb_strtolower($delivery));

        // --- спец. случаи ---
        if ($delivery === 'завтра') {
            return 1;
        }
        if ($delivery === 'послезавтра') {
            return 2;
        }

        // --- формат вида "16 декабря" ---
        // пытаемся распарсить дату
        $months = [
            'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
            'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
            'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12,
        ];

        if (preg_match('/(\d+)\s+([а-я]+)/u', $delivery, $m)) {

            $day = (int)$m[1];
            $month = $months[$m[2]] ?? null;

            if ($month) {
                $deliveryDate = \Carbon\Carbon::create(date('Y'), $month, $day);
                $today = now();

                // если дата уже прошла — значит про следующий год
                if ($deliveryDate->isPast()) {
                    $deliveryDate->addYear();
                }

                return $today->diffInDays($deliveryDate);
            }
        }

        // 🤷 если формат незнакомый — считаем большим сроком
        return 999;
    }
}
