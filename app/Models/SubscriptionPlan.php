<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'grace_days',
        'limits',
        'features',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'grace_days' => 'integer',
        'limits'     => 'array',
        'features'   => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
