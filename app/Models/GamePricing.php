<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePricing extends Model
{
    protected $fillable = [
        'game_id',
        'pricing_mode_id',
        'duration_minutes',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer'
    ];

    // 🎮 Game
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    // 🏷️ Pricing Mode
    public function pricingMode()
    {
        return $this->belongsTo(PricingMode::class);
    }

    // 📊 Sessions utilisant ce pricing
    public function sessions()
    {
        return $this->hasMany(GameSession::class, 'pricing_reference_id');
    }
}