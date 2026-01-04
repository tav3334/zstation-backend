<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status'
    ];

    // ✅ Ajouter active_session aux attributs JSON
    protected $appends = ['active_session'];

    // 🎮 Relation: Session active
    public function activeSession()
    {
        return $this->hasOne(GameSession::class)
            ->whereNull('ended_at')
            ->latest('start_time');
    }

    // 🔄 Relation: Toutes les sessions
    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }

    // ✅ Attribut virtuel pour le frontend
    public function getActiveSessionAttribute()
    {
        $session = $this->activeSession()->with('game', 'gamePricing')->first();

        if (!$session) {
            return null;
        }

        return [
            'id' => $session->id,
            'start_time' => $session->start_time->toISOString(),
            'game_name' => $session->game->name ?? 'N/A',
            'price' => $session->computed_price,
            'duration_minutes' => $session->gamePricing->duration_minutes ?? 6,
            'duration_seconds' => ($session->gamePricing->duration_minutes ?? 6) * 60
        ];
    }
}