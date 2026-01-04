<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email'
    ];

    // 📊 Sessions du client
    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }
}