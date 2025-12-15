<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingMode extends Model
{
    protected $fillable = ['code', 'label'];

    public function pricings()
    {
        return $this->hasMany(GamePricing::class);
    }
}

