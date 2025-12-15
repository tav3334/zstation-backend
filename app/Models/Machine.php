<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $fillable = [
        'name', 'code', 'status', 'location', 'notes'
    ];

    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }
    public function activeSession()
{
    return $this->hasOne(\App\Models\GameSession::class)
        ->where('status', 'active');
}

}
