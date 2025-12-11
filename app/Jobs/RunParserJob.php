<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ParserItem;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunParserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $item;

    public function __construct(ParserItem $item)
    {
        $this->item = $item;
    }

    public function handle()
    {
        $item = $this->item;

        $response = Http::timeout(60)->post('http://155.212.219.85:5001/run-parser', [
            'query' => $item->name,
            'max_items' => 20,
            'price_min' => $item->price,
        ]);

        if ($response->failed()) {
            return;
        }

        $minPrice = intval(preg_replace('/[^\d.]/', '', $item->price));

        $data = $response->json();

        foreach ($data['products'] as $i) {
            Product::create([
                'title' => $i['title'] ?? null,
                'url'   => $i['url'] ?? null,
                'price' => $i['price'] ?? null,
                'min_price' => $minPrice,
                'delivery' => $i['delivery'] ?? null,
                'sent_alert' => 0,
            ]);
        }
    }
}
