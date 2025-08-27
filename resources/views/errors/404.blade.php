@extends('errors::minimal')

@section('title', __('Page Not Found - Beta'))

@section('message')
<center>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gray-900 text-white text-center">
        <div class="max-w-xl w-full">
          
            <!-- Main Error Message -->
            <h4 class="text-xs md:text-6xl font-bold text-red-400">
                404
            </h4>
          
            <!-- Navigation Buttons -->
            <div class="mt-6 flex justify-center gap-3">
                <a href="/"
                   class="px-5 py-2.5 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded shadow transition">
                    BACK TO BASE
                </a>
            </div>
    
            <!-- Crypto-Themed Logo with Beta Flair -->
            <div class="mt-6 relative">
                <x-app-logo class="w-20 h-auto opacity-80" />
                <span class="absolute -top-2 -right-2 bg-green-500 text-xs font-bold text-white px-2 py-1 rounded-full">
                    BETA
                </span>
            </div>
        </div>
    </div>
</center>
@endsection