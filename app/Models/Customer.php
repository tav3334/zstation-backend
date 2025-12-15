<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'points', 'notes'];

    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }
}
