<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ParserItem;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class RunParserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $itemId;

    public function __construct(int $itemId)
    {
        $this->itemId = $itemId;
    }

    public function handle(): void
    {
        $item = ParserItem::find($this->itemId);

        if (! $item) {
            return;
        }

        $response = Http::timeout(60)->post('http://155.212.219.85:5001/run-parser', [
            'query' => $item->name,
            'max_items' => 20,
            'price_min' => $item->price,
            'city' => 'Казань',
        ]);

        if ($response->failed()) {
            return;
        }

        $minPrice = (int) preg_replace('/\D/', '', $item->price);
        $data = $response->json();

        foreach ($data['products'] ?? [] as $i) {
            Product::create([
                'title' => $i['title'] ?? null,
                'url' => $i['url'] ?? null,
                'price' => $i['price'] ?? null,
                'min_price' => $minPrice,
                'query_title' => $item->name,
                'delivery' => $i['delivery'] ?? null,
                'sent_alert' => 0,
            ]);
        }
    }
}
