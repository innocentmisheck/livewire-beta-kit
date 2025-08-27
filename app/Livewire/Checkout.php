<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Facades\Http;

class Checkout extends Component
{
    public Transaction $transaction;
    public float $fiatAmount;
    public string $fiatCurrency;
    public string $id; // This will receive the transaction ID from the route
    public string $cardNumber = '';
    public string $cardExpiry = '';
    public string $cardCvc = '';

    // Mount method accepts the $id parameter from the route
    public function mount($id): void
    {
        $this->id = $id; // Set the id property
        $this->transaction = Transaction::findOrFail($this->id);

        \Log::info('Transaction ID: ' . $this->id);

        if ($this->transaction->user_id !== Auth::id() || $this->transaction->status !== 'pending') {
            abort(403, 'Invalid transaction.');
        }

        $this->fiatCurrency = session('fiat_currency', 'USD');
        $this->fiatAmount = $this->calculateFiatAmount();
    }

    private function calculateFiatAmount(): float
    {
        if ($this->fiatCurrency === 'USD') {
            return $this->transaction->dollar;
        }

        $apiKey = config('services.exchangerate.key');
        $apiUrl = rtrim(config('services.exchangerate.url'), '/') . "/{$apiKey}/latest/USD";
        $response = Http::get($apiUrl);
        $rate = $response->json()['conversion_rates'][$this->fiatCurrency] ?? 1;
        return $this->transaction->dollar * $rate;
    }

    public function confirmPayment(): void
    {
        // If card details are provided, perform basic server-side validation
        if (!empty($this->cardNumber) || !empty($this->cardExpiry) || !empty($this->cardCvc)) {
            if (strlen($this->cardNumber) < 16 || !preg_match('/^\d{2}\/\d{2}$/', $this->cardExpiry) || strlen($this->cardCvc) !== 3) {
                session()->flash('notification', ['type' => 'error', 'message' => __('Invalid card details.')]);
                return;
            }
        }

        // Simulate payment processing with fiatAmount as the minimum amount
        // Replace this with actual payment gateway logic (e.g., Stripe, PayPal)
        $paymentSuccessful = true; // Placeholder

        if ($paymentSuccessful) {
            $this->transaction->update([
                'status' => 'completed',
                'payment_data' => !empty($this->cardNumber) ? [
                    'card_number' => $this->cardNumber,
                    'card_expiry' => $this->cardExpiry,
                    'card_cvc' => $this->cardCvc,
                    'amount' => $this->amount,
                    'price' => $this->price,
                    'crypto' => $this->crypto,
                    'currency' => $this->currency, 
                ] : [
                    'amount_paid' => $this->fiatAmount,
                    'currency' => $this->fiatCurrency,
                ],
            ]);

            // Debugging: Dump the updated transaction
            Wallet::where('id', $this->transaction->wallet_id)->increment('amount', $this->transaction->price);
            session()->flash('notification', ['type' => 'success', 'message' => __('Payment successful! Deposit added to your wallet.')]);
            //dd($this->transaction->price);
            $this->redirect('/app');
        } else {
            $this->transaction->update(['status' => 'failed']);
            session()->flash('notification', ['type' => 'error', 'message' => __('Payment failed. Please try again.')]);
        }
    }

    public function cancel(): void
    {
        $this->transaction->update(['status' => 'cancelled']);
        session()->flash('notification', ['type' => 'info', 'message' => __('Transaction cancelled.')]);
        $this->redirect('/wallet/deposit');
    }

    public function render()
    {
        return view('livewire.profile.wallet.checkout');
    }
}