<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OzonApiService
{
    protected $client;
    protected $clientId;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('ozon.url'),
            'timeout'  => 15,
        ]);

        // Получаем параметры из конфига
        $this->clientId = config('ozon.client_id');
        $this->apiKey   = config('ozon.api_key');
    }

    /**
     * Универсальный метод запросов к Ozon API
     */
    public function request(string $endpoint, array $body = [])
    {
        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Client-Id' => config('ozon.client_id'),
                    'Api-Key'   => config('ozon.api_key'),
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {

            Log::error('Ozon API error', [
                'endpoint' => $endpoint,
                'body' => $body,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Получить информацию о товарах (по SKU/FBO/FBS)
     */
    public function getProductInfo(array $offerIds)
    {
        return $this->request('/v2/product/info/list', [
            'offer_id' => $offerIds,
        ]);
    }

    /**
     * Получить цены
     */
    public function getPrices(array $offerIds)
    {
        return $this->request('/v4/product/info/prices', [
            'offer_id' => $offerIds,
        ]);
    }

    /**
     * Получить остатки
     */
    public function getStocks(array $offerIds)
    {
        return $this->request('/v3/product/info/stocks', [
            'offer_id' => $offerIds,
        ]);
    }

    /**
     * Характеристики товара
     */
    public function getAttributes(int $productId)
    {
        return $this->request('/v2/product/info/attributes', [
            'product_id' => $productId,
        ]);
    }

    /**
     * Информация о продавце
     */
    public function getSeller()
    {
        return $this->request('/v2/seller/info');
    }


    public function getAllProducts(int $limit = 100, int $page = 1): array
    {
        // Ограничиваем лимит
        if ($limit < 1) $limit = 1;
        if ($limit > 1000) $limit = 1000;

        $products = [];

        do {
            $response = $this->client->post('/v3/product/list', [
                'headers' => [
                    'Client-Id'    => $this->clientId,
                    'Api-Key'      => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'filter' => (object)[],  // пустой фильтр
                    'page'   => $page,
                    'limit'  => $limit,      // вместо page_size
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['result']['items']) || empty($data['result']['items'])) {
                break;
            }

            foreach ($data['result']['items'] as $item) {
                $products[] = [
                    'product_id' => $item['product_id'] ?? null,
                    'offer_id'   => $item['offer_id'] ?? null,
                    'name'       => $item['name'] ?? null,
                    'barcode'    => $item['barcode'] ?? null,
                ];
            }

            $page++;

        } while (count($data['result']['items']) === $limit);

        return $products;
    }


    public function getCompetitorPrices(array $offerIds, int $chunkSize = 50): array
    {
        if (empty($offerIds)) {
            return [];
        }

        $allPrices = [];

        // Разбиваем на чанки, чтобы не отправлять слишком большой массив за один запрос
        $chunks = array_chunk($offerIds, $chunkSize);

        foreach ($chunks as $chunk) {
            $response = $this->request('/v1/pricing/items-info', [
                'offer_id' => $chunk
            ]);

            if ($response && isset($response['result']['items'])) {
                $allPrices = array_merge($allPrices, $response['result']['items']);
            }
        }

        return $allPrices;
    }


    public function getPricingCompetitors(array $offerIds, int $strategyId = null)
    {
        // Структура тела запроса может зависеть от документации Ozon
        $body = [
            'offer_id' => $offerIds,
        ];

        if ($strategyId !== null) {
            $body['strategy_id'] = $strategyId;
        }

        $response = $this->request('/v1/pricing-strategy/competitors/list', $body);

        if (!$response) {
            return [];
        }

        // Предположим, что ответ содержит `result.competitors`
        return $response['result']['competitors'] ?? [];
    }


}
