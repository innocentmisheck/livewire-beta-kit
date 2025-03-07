<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class OrdersAndNews extends Component
{
    public $activeOrders = [];
    public $marketNews = [];

    public function mount()
    {
        $this->fetchData();
    }

    public function fetchData()
    {
        // Fetch Active Orders (simulated, replace with database query)
        $this->activeOrders = [
            [
                'type' => 'buy',
                'amount' => 1,
                'currency' => 'BTC',
                'price' => 45000,
                'timestamp' => now()->subMinutes(5)->toDateTimeString(),
                'status' => 'pending',
                'id' => 1,
            ],
            [
                'type' => 'sell',
                'amount' => 0.5,
                'currency' => 'ETH',
                'price' => 3200,
                'timestamp' => now()->subHours(1)->toDateTimeString(),
                'status' => 'filled',
                'id' => 2,
            ],
        ];

        // Fetch Market News from CoinGecko using /coins/markets
        try {
            $response = Http::get(env('COINGECKO_API_URL') . '/coins/markets', [
                'vs_currency' => 'usd', // Explicitly required parameter
                'order' => 'market_cap_desc',
                'per_page' => 5,
                'page' => 1,
                'sparkline' => false,
            ]);

            if ($response->successful()) {
                $coins = $response->json();
                // Simulate news with top 2 coins' price changes
                $this->marketNews = array_map(function ($coin) {
                    $change = $coin['price_change_percentage_24h'] ?? 0;
                    $description = sprintf(
                        '%s %s %.2f%% in last 24h',
                        $coin['name'],
                        $change >= 0 ? 'up' : 'down',
                        abs($change)
                    );
                    return [
                        'description' => $description,
                        'created_at' => now()->toDateTimeString(),
                    ];
                }, array_slice($coins, 0, 2));
            } else {
            $error = $response->json()['error'] ?? '';
                \Log::warning('CoinGecko API failed: ' . $error);
                $this->marketNews = [
                    ['description' => 'Please wait, refreshing soon...' . $error, 'created_at' => now()->toDateTimeString()],
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Failed to fetch market news: ' . $e->getMessage());
            $this->marketNews = [
                ['description' => 'Market updates unavailable', 'created_at' => now()->toDateTimeString()],
            ];
        }
    }

    public function cancelOrder($orderId)
    {
        $this->activeOrders = array_filter($this->activeOrders, fn($order) => $order['id'] !== $orderId);
        session()->flash('success', 'Order canceled successfully');
    }

    public function render()
    {
        return view('livewire.app.main.orders-and-news');
    }
}
