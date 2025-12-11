<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id')
            ->orWhere('to_wallet_id', $this->id);
    }

    public function sentTransactions()
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }

    public function receivedTransactions()
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    public function hasBalance($amount): bool
    {
        return $this->balance >= $amount;
    }

    public function addBalance($amount): void
    {
        $this->balance += $amount;
        $this->save();
    }

    public function subtractBalance($amount): void
    {
        if (!$this->hasBalance($amount)) {
            throw new \Exception('Saldo insuficiente');
        }
        $this->balance -= $amount;
        $this->save();
    }
}

