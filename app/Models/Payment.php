<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'session_id',
        'amount',
        'amount_given',
        'change_given',
        'payment_method',
        'payment_date',
        'staff_id',
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
        'amount_given' => 'decimal:2',
        'change_given' => 'decimal:2'
    ];

    // Session
    public function session()
    {
        return $this->belongsTo(GameSession::class, 'session_id');
    }

    // Staff qui a enregistré le paiement
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    // Machine via session
    public function machine()
    {
        return $this->hasOneThrough(
            Machine::class,
            GameSession::class,
            'id',
            'id',
            'session_id',
            'machine_id'
        );
    }

    // Game via session
    public function game()
    {
        return $this->hasOneThrough(
            Game::class,
            GameSession::class,
            'id',
            'id',
            'session_id',
            'game_id'
        );
    }
}