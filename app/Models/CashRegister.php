<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    protected $fillable = [
        'date',
        'opening_balance',
        'total_cash_in',
        'total_change_out',
        'closing_balance',
        'withdrawn_amount',
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_cash_in' => 'decimal:2',
        'total_change_out' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'withdrawn_amount' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Solde actuel calculé
    public function getCurrentBalanceAttribute()
    {
        return $this->opening_balance + $this->total_cash_in - $this->total_change_out - $this->withdrawn_amount;
    }

    // Profit net du jour
    public function getNetProfitAttribute()
    {
        return $this->total_cash_in - $this->total_change_out;
    }

    // Vérifier si la caisse est ouverte
    public function getIsOpenAttribute()
    {
        return $this->opened_at !== null && $this->closed_at === null;
    }
}
