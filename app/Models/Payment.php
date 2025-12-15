<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'session_id', 'amount', 'method', 'status', 'paid_at', 'created_by', 'notes'
    ];

    public function session()
    {
        return $this->belongsTo(GameSession::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

