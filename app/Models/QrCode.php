<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'type',
        'user_id',
        'wallet_id',
        'amount',
        'description',
        'expires_at',
        'usage_limit',
        'times_used',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($qrCode) {
            if (empty($qrCode->token)) {
                $qrCode->token = Str::random(32);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function isPayment(): bool
    {
        return $this->type === 'payment';
    }

    public function isDeposit(): bool
    {
        return $this->type === 'deposit';
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function incrementUsage(): void
    {
        $this->times_used++;
        $this->save();
    }

    public function getUrl(): string
    {
        $baseUrl = config('app.url');
        if ($this->isPayment()) {
            return $baseUrl . '/qr/pay/' . $this->token;
        }
        return $baseUrl . '/qr/deposit/' . $this->token;
    }
}

