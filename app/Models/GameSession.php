<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $table = 'game_sessions';

    protected $fillable = [
        'machine_id', 'game_id', 'pricing_mode_id', 'pricing_reference_id',
        'customer_id', 'start_time', 'end_time',
        'computed_price', 'status', 'created_by', 'closed_by'
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function pricingMode()
    {
        return $this->belongsTo(PricingMode::class);
    }

    public function pricingReference()
    {
        return $this->belongsTo(GamePricing::class, 'pricing_reference_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
