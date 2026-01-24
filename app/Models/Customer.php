<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;

class Customer extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'organization_id',
    ];

    // 📊 Sessions du client
    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }
}