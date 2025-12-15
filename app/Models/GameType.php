<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameType extends Model
{
    protected $fillable = ['name', 'description'];

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
