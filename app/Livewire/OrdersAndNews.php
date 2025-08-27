<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache; 
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use Carbon\Carbon;

class OrdersAndNews extends Component
{
    public $activeOrders = [];
    public $marketNews = [];

    private const NEWS_CACHE_KEY = 'market_news_daily';
    private const NEWS_CACHE_TTL = 86400; // 24 hours in seconds

    public function mount()
    {
        $this->fetchData();
    }

    public function fetchData()
    {
        try {
            $userId = auth()->id(); // Get the authenticated user's ID
            $this->activeOrders = Transaction::whereIn('status', ['pending', 'filled'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'price' => $transaction->dollar,
                        'timestamp' => $transaction->created_at->toDateTimeString(),
                        'status' => $transaction->status,
                        'id' => $transaction->id,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to fetch active orders for user: ' . $e->getMessage());
            $this->activeOrders = [];
        }
    }

    protected function fetchMarketNews()
    {
        $cachedNews = Cache::get(self::NEWS_CACHE_KEY);
    
        if ($cachedNews) {
            $this->marketNews = json_decode($cachedNews, true);
            return;
        }
    
        try {
            $response = Http::get('http://api.coincap.io/v2/markets');
    
            if ($response->successful()) {
                $coins = $response->json()['data'];
                $this->marketNews = array_map(function ($coin) {
                    $change = $coin['changePercent24Hr'] ?? 0;
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
    
                Cache::put(self::NEWS_CACHE_KEY, json_encode($this->marketNews), self::NEWS_CACHE_TTL);
            } else {
                $error = $response->json()['error'] ?? 'Unknown error';
                Log::warning('CoinCap API failed: ' . $error);
                $this->marketNews = [
                    ['description' => 'Please wait, refreshing soon... ' . $error, 'created_at' => now()->toDateTimeString()],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch market news: ' . $e->getMessage());
            $this->marketNews = [
                ['description' => 'Market updates unavailable', 'created_at' => now()->toDateTimeString()],
            ];
        }
    }

    public function cancelOrder($orderId)
    {       
        try {
            $order = Transaction::where('id', $orderId)
                ->where('user_id', auth()->id())
                ->firstOrFail();
    
            if ($order->status === 'pending') {
                $order->update(['status' => 'canceled']);
                $this->fetchData();
                session()->flash('success', 'Order canceled successfully');
            } else {
                session()->flash('error', 'Cannot cancel a non-pending order');
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', 'Order not found');
        } catch (\Exception $e) {
            Log::error('Failed to cancel order: ' . $e->getMessage());
            session()->flash('error', 'Failed to cancel order');
        }
    }

    public function hydrate()
    {
        $this->fetchData();
    }

    public function render()
    {
        return view('livewire.app.main.orders-and-news')
            ->with(['livewire polling' => '300s']);
    }
}