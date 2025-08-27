<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class RecentTransactions extends Component
{
    public $transactions = [];

    public function mount()
    {
        $this->fetchTransactions();
    }

    public function fetchTransactions()
    {
        // Fetch the latest 5 transactions for the authenticated user
        $this->transactions = Transaction::where('user_id', Auth::id())
            ->latest('created_at') // Order by creation date, newest first
            ->take(5) // Limit to 5 transactions
            ->get()
            ->map(function ($transaction) {
                return [
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'to' => $transaction->type === 'sent' ? ($transaction->to ?? 'Unknown') : null,
                    'from' => $transaction->type === 'received' ? ($transaction->from ?? 'Unknown') : null,
                    'timestamp' => $transaction->created_at->toDateTimeString(),
                    'status' => $transaction->status,
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.app.main.recent-transactions');
    }
}