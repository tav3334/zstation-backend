<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePricing extends Model
{
    protected $fillable = [
        'game_id', 'pricing_mode_id', 'duration_minutes', 'price', 'notes'
    ];

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function mode()
    {
        return $this->belongsTo(PricingMode::class, 'pricing_mode_id');
    }
}

