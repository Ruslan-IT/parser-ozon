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
use Illuminate\Support\Facades\Log;

class RunParserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $itemId;
    public $tries = 3;
    public $timeout = 180;

    public function __construct(int $itemId)
    {
        $this->itemId = $itemId;
        $this->onQueue('parsers'); // Устанавливаем очередь здесь
    }

    public function handle(): void
    {
        Log::info('RunParserJob started', ['item_id' => $this->itemId]);

        $item = ParserItem::find($this->itemId);

        if (! $item) {
            Log::error('ParserItem not found', ['item_id' => $this->itemId]);
            return;
        }

        Log::info('Sending request to parser', [
            'item_id' => $this->itemId,
            'name' => $item->name,
            'price' => $item->price
        ]);

        try {
            $response = Http::timeout(60)->post('http://155.212.219.85:5001/run-parser', [
                'query' => $item->url,
                'max_items' => 20,
                'price_min' => $item->price,
                'name' => $item->name,
                'city' => 'Казань',
            ]);

            Log::info('Parser response status', [
                'status' => $response->status(),
                'item_id' => $this->itemId
            ]);

            if ($response->failed()) {
                Log::error('Parser request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'item_id' => $this->itemId
                ]);
                return;
            }

            $minPrice = (int) preg_replace('/\D/', '', $item->price);
            $name = $item->name;

            $data = $response->json();

            Log::info('Parsed products count', [
                'count' => count($data['products'] ?? []),
                'item_id' => $this->itemId
            ]);

            $createdCount = 0;
            foreach ($data['products'] ?? [] as $i) {
                Product::create([
                    'title' => $i['title'] ?? null,
                    'name' => $name ?? null,
                    'url' => $i['url'] ?? null,
                    'price' => $i['price'] ?? null,
                    'min_price' => $minPrice,
                    'query_title' => $item->name,
                    'delivery' => $i['delivery'] ?? null,
                    'sent_alert' => 0,
                ]);
                $createdCount++;
            }

            Log::info('Products created', [
                'count' => $createdCount,
                'item_id' => $this->itemId
            ]);

        } catch (\Exception $e) {
            Log::error('Parser job exception', [
                'item_id' => $this->itemId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
