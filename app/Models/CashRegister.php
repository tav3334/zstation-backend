<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;

class CashRegister extends Model
{
    use BelongsToOrganization;

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
        'organization_id',
    ];

    // No casts - handle formatting in controller to avoid serialization issues
    protected $casts = [];

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
