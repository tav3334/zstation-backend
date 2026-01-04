<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingMode extends Model
{
    protected $fillable = [
        'code',
        'label'
    ];

    // 💰 Game Pricings utilisant ce mode
    public function gamePricings()
    {
        return $this->hasMany(GamePricing::class);
    }

    // 📊 Sessions utilisant ce mode
    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }
}