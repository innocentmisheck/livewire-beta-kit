<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Livewire\Checkout; 

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/app', function () {
    return view('partials.app');
})
->middleware(['auth', 'verified'])
->name('app');

Route::view('account', 'account')
    ->middleware(['auth', 'verified'])
    ->name('account');

Route::get('/wallet/settings', function () {
    return view('profile.config');
})->name('profile.config');

Route::get('/wallet/create', function () {
    return view('profile.wallet.create-wallet');
})->name('profile.wallet.create-wallet');

Route::get('/wallet/deposit', function () {
    return view('profile.wallet.deposit-wallet');
})->name('profile.wallet.deposit-wallet' );

// Check out the deposit to buy crypto to wallet
Route::get('/checkout/{id}', Checkout::class)->name('profile.wallet.checkout');

Route::middleware(['auth'])->group(function () {
    Route::get('settings', function () {
        return redirect('settings/profile');
    });
    Route::get('wallet', function () {
        return redirect('wallet/settings');
    });
    Route::get('deposit', function () {
        return redirect('wallet/deposit');
    });
    Volt::route('wallet/settings', 'profile.config')->name('profile.config');
    Volt::route('wallet/create', 'profile.wallet.create-wallet')->name('profile.wallet.create-wallet');
    Volt::route('wallet/deposit', 'profile.wallet.deposit-wallet')->name('profile.deposit.create-wallet');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::get('/broadcast-test', function () {
    event(new \App\Events\TestEvent('Hello from Laravel Reverb!'));
    return 'Event has been dispatched. Check your WebSocket connection.';
});

Route::get('/websocket-test', function () {
    return view('websocket-test');
});

require __DIR__.'/auth.php';
