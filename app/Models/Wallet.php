<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory;

    protected $primaryKey = 'id'; // Default primary key

    protected $fillable = [
        'wallet_id',
        'user_id',
        'currency',
        'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:8',
    ];

    public static function boot()
    {
        parent::boot();

        // Automatically generate a UUID for id and wallet_id
        static::creating(function ($wallet) {
            $wallet->wallet_id = Str::uuid()->toString();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
