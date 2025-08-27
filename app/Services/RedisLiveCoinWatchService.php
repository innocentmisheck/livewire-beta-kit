<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;  

class RedisLiveCoinWatchService
{
    protected $apiUrl;
    protected $apiKey;
    protected $httpClient;

    public function __construct()
    {
        $this->apiUrl = config('services.livecoinwatch.url', 'https://api.livecoinwatch.com');
        $this->apiKey = config('services.livecoinwatch.key');

        if (empty($this->apiKey)) {
            Log::warning('LiveCoinWatch API key is not configured.');
        }

        $this->httpClient = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ]);
    }

    public function getCurrenciesList(): array
    {
        $cacheKey = 'livecoinwatch_currencies_list';
        $ttl = 300; // 5 minutes in seconds

        // Use Redis directly or stick with Cache facade
        return $this->cacheWithRedis($cacheKey, $ttl, function () {
            try {
                $response = $this->httpClient->post($this->apiUrl . '/coins/list', [
                    'currency' => 'USD',
                    'sort' => 'rank',
                    'order' => 'ascending',
                    'offset' => 0,
                    'limit' => 50,
                    'meta' => true,
                ]);

                if ($response->successful()) {
                    $data = $response->json() ?: [];
                    if (empty($data)) {
                        throw new \Exception('Empty response from currencies list API');
                    }
                    return $data;
                }
                throw new \Exception('Failed to fetch currencies list: ' . $response->status());
            } catch (\Exception $e) {
                Log::error('Exception when fetching currencies list: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function getCoinDetails(array $codes): array
    {
        $cacheKey = 'livecoinwatch_coins_' . implode('_', $codes);
        $ttl = 300;

        return $this->cacheWithRedis($cacheKey, $ttl, function () use ($codes) {
            try {
                $response = $this->httpClient->post($this->apiUrl . '/coins/list', [
                    'currency' => 'USD',
                    'sort' => 'rank',
                    'order' => 'ascending',
                    'offset' => 0,
                    'limit' => 50,
                    'code' => $codes,
                    'meta' => true
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (empty($data)) {
                        throw new \Exception('Empty response from coin details API');
                    }

                    $result = [];
                    foreach ($data as $coin) {
                        $result[$coin['code']] = [
                            'name' => $coin['name'] ?? $coin['code'],
                            'price' => $coin['rate'] ?? 0,
                            'change_24h' => ($coin['delta']['day'] ?? 0) * 100,
                            'volume' => $coin['volume'] ?? 0,
                            'market_cap' => $coin['cap'] ?? 0,
                            'icon' => $coin['webp64']
                        ];
                    }

                    foreach ($codes as $code) {
                        if (!isset($result[$code])) {
                            $result[$code] = null;
                        }
                    }

                    return $result;
                }
                throw new \Exception('Failed to fetch coin details: ' . $response->status());
            } catch (\Exception $e) {
                Log::error('Exception when fetching coin details: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function getPriceHistory(string $code, int $days = 30): array
    {
        $cacheKey = "livecoinwatch_history_{$code}_{$days}";
        $ttl = 300;

        return $this->cacheWithRedis($cacheKey, $ttl, function () use ($code, $days) {
            try {
                $start = now()->subDays($days)->timestamp * 1000;
                $end = now()->timestamp * 1000;

                $response = $this->httpClient->post($this->apiUrl . '/coins/single/history', [
                    'currency' => 'USD',
                    'code' => $code,
                    'start' => $start,
                    'end' => $end,
                    'meta' => true
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!isset($data['history']) || empty($data['history'])) {
                        throw new \Exception('No history data in response');
                    }

                    $history = $data['history'];
                    $labels = [];
                    $values = [];

                    $step = max(1, intval(count($history) / 7));
                    for ($i = 0; $i < count($history); $i += $step) {
                        if (count($labels) >= 7) break;
                        if (isset($history[$i])) {
                            $date = date('M j', $history[$i]['date'] / 1000);
                            $labels[] = $date;
                            $values[] = $history[$i]['rate'];
                        }
                    }

                    return [
                        'labels' => $labels,
                        'datasets' => [
                            [
                                'label' => 'Price',
                                'data' => $values,
                                'borderColor' => '#10B981',
                                'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                            ]
                        ]
                    ];
                }
                throw new \Exception('Failed to fetch price history: ' . $response->status());
            } catch (\Exception $e) {
                Log::error('Exception when fetching price history: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Helper method to handle Redis caching
     */
    protected function cacheWithRedis(string $key, int $ttl, callable $callback): array
    {
        return Cache::remember($key, $ttl, $callback);
    }
}