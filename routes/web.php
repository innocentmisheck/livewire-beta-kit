<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('account', 'account')
    ->middleware(['auth', 'verified'])
    ->name('account');

Route::get('/wallet/settings', function () {
    return view('profile.config');
})->name('profile.config');

Route::get('/wallet/create', function () {
    return view('profile.create-wallet');
})->name('profile.create-wallet');





Route::middleware(['auth'])->group(function () {
    Route::get('settings', function () {
        return redirect('settings/profile');
    });
    Route::get('wallet', function () {
        return redirect('wallet/settings');
    });
    Volt::route('wallet/settings', 'profile.config')->name('profile.config');
    Volt::route('wallet/create', 'profile.create-wallet')->name('profile.create-wallet');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
