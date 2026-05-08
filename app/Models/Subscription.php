<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'organization_id',
        'subscription_plan_id',
        'starts_at',
        'ends_at',
        'grace_days',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at'   => 'date',
        'is_active' => 'boolean',
        'grace_days' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Days remaining until ends_at (negative = overdue).
     * Returns null if no end date (unlimited).
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->ends_at) return null;
        return (int) now()->startOfDay()->diffInDays($this->ends_at->startOfDay(), false);
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->ends_at) return false;
        return now()->startOfDay()->isAfter($this->ends_at->endOfDay());
    }

    public function getInGracePeriodAttribute(): bool
    {
        if (!$this->is_expired) return false;
        $graceEnd = $this->ends_at->copy()->addDays($this->grace_days);
        return now()->startOfDay()->lte($graceEnd);
    }

    public function getGraceDaysRemainingAttribute(): int
    {
        if (!$this->in_grace_period) return 0;
        $graceEnd = $this->ends_at->copy()->addDays($this->grace_days);
        return max(0, (int) now()->startOfDay()->diffInDays($graceEnd, false));
    }

    /**
     * The subscription is fully blocked when expired past grace period or manually suspended.
     */
    public function getIsBlockedAttribute(): bool
    {
        if (!$this->is_active) return true;
        if ($this->is_expired && !$this->in_grace_period) return true;
        return false;
    }
}
