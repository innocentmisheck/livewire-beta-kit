<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 
        'user_id',
        'wallet_id',
        'type',
        'amount',
        'price',
        'crypto',
        'currency',
        'rate',
        'dollar',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'price' => 'decimal:8',
        'rate' => 'decimal:8',
        'dollar' => 'decimal:2',
        'status' => 'string'
    ];

    public static function boot()
    {
        parent::boot();

        // Automatically generate a UUID for the id column
        static::creating(function ($transaction) {
            if (!$transaction->id) {
                $transaction->id = Str::uuid()->toString();
            }
        });
    }
}