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

            $text = "📉 *Цена снизилась!*\n"
                . "Название: {$product->name}\n"
                . "Цена: {$product->price}\n"
                . "Мин. цена: {$product->min_price}\n"
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
}
