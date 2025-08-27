<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('wallet_id')->unique(); // Unique UUID wallet ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('currency', 100); // e.g., BTC, ETH
            $table->decimal('amount', 15, 8); // Precision for crypto amounts
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wallets');
    }
};
