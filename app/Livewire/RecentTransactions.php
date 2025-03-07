<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class RecentTransactions extends Component
{
    public $transactions = [];

    public function mount()
    {
        $this->fetchTransactions();
    }

    public function fetchTransactions()
    {
        // Simulated data (replace with database query, e.g., auth()->user()->transactions()->latest()->take(3)->get())
        $this->transactions = [
            [
                'type' => 'sent',
                'amount' => 0.5,
                'currency' => 'BTC',
                'to' => 'Alice',
                'timestamp' => now()->subMinutes(10)->toDateTimeString(),
                'status' => 'completed',
            ],
            [
                'type' => 'received',
                'amount' => 1.2,
                'currency' => 'ETH',
                'from' => 'Bob',
                'timestamp' => now()->subHours(1)->toDateTimeString(),
                'status' => 'completed',
            ],
            [
                'type' => 'bought',
                'amount' => 0.3,
                'currency' => 'LTC',
                'timestamp' => now()->subHours(2)->toDateTimeString(),
                'status' => 'pending',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.app.main.recent-transactions');
    }
}
