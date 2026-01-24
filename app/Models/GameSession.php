<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToOrganization;

class GameSession extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'machine_id',
        'game_id',
        'pricing_mode_id',
        'pricing_reference_id',
        'customer_id',
        'start_time',
        'ended_at',
        'matches_played',
        'computed_price',
        'status',
        'organization_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'ended_at' => 'datetime',
        'computed_price' => 'decimal:2',
        'matches_played' => 'integer'
    ];

    // 🎮 Game
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    // 💰 Pricing (utilise pricing_reference_id)
    public function gamePricing()
    {
        return $this->belongsTo(GamePricing::class, 'pricing_reference_id');
    }

    // 🖥️ Machine
    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    // 👤 Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 🏷️ Pricing Mode
    public function pricingMode()
    {
        return $this->belongsTo(PricingMode::class);
    }
}