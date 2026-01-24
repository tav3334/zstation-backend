<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;

class Game extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name', 'game_type_id', 'default_price_per_hour', 'active', 'organization_id'
    ];

    public function type()
    {
        return $this->belongsTo(GameType::class, 'game_type_id');
    }

    public function pricings()
    {
        return $this->hasMany(GamePricing::class, 'game_id');
    }

    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }
}
