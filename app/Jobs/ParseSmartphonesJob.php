<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ParseSmartphonesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $results = [];

    protected array $models = [
        ['brand' => 'Samsung', 'model' => 'Galaxy a17', 'memory' => '4/128'],
        ['brand' => 'Xiaomi', 'model' => 'Redmi Note 14', 'memory' => '8/256'],
        // Добавь остальные модели
    ];

    public function handle(): void
    {
        foreach ($this->models as $model) {
            $query = "{$model['brand']} {$model['model']} {$model['memory']}";

            $response = Http::get('https://www.ozon.ru/api/composer-api.bx/search', [
                'text' => $query,
                'limit' => 10,
            ]);

            $data = $response->json();

            // Ozon возвращает товары в $data['items']
            foreach ($data['items'] ?? [] as $item) {
                // Исключаем Китай
                $country = $item['attributes']['country_of_origin'] ?? null;
                if ($country === 'China') continue;

                $this->results[] = [
                    'name' => $item['name'] ?? null,
                    'price' => $item['price'] ?? null,
                    'link' => isset($item['id']) ? "https://www.ozon.ru/product/{$item['id']}" : null,
                ];
            }
        }
    }
}
