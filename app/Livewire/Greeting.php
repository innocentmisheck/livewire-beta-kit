<?php

namespace App\Livewire;

use App\Services\LiveCoinWatchService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Greeting extends Component
{
    public $greeting;
    public $userName;
    public $cryptoCount;
    public $cryptoSymbols;

    protected $liveCoinWatchService;

    public function mount(LiveCoinWatchService $liveCoinWatchService)
    {
        $this->liveCoinWatchService = $liveCoinWatchService;
        $this->setCryptoSymbols();
        $this->setGreeting();
        $this->setCryptoCount();
        $this->userName = Auth::check() ? optional(Auth::user())->name : 'Guest';
    }

    public function setCryptoSymbols()
    {
        try {
            $this->cryptoSymbols = cache()->remember('livecoinwatch_all_crypto_symbols', 3600, function () {
                $currencies = $this->liveCoinWatchService->getCurrenciesList();

                if (!empty($currencies)) {
                    return collect($currencies)->pluck('code')->toArray();
                }
                // Fallback to basic symbols if API fails
                return [];
            });
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('Failed to fetch crypto symbols: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Unexpected error while fetching crypto symbols: ' . $e->getMessage());
        }
    }

    public function setCryptoCount()
    {
        $this->cryptoCount = count($this->cryptoSymbols);
    }

    public function setGreeting()
    {
        $hour = Carbon::now()->hour;
        if ($hour < 12) {
            $this->greeting = 'Good morning';
        } elseif ($hour < 17) {
            $this->greeting = 'Good afternoon';
        } else {
            $this->greeting = 'Good evening';
        }
    }

    public function render()
    {
        try {
            return view('livewire.auth.greeting');
        } catch (\Exception $e) {
            \Log::error('Failed to render Greeting component: ' . $e->getMessage());
            return view('errors.livewire-render');
        }
    }
}
