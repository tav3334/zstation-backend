<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['organization', 'plan'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($s) => $this->format($s));

        return response()->json(['success' => true, 'subscriptions' => $subscriptions]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id'      => 'required|exists:organizations,id',
            'plan_id'              => 'required|exists:subscription_plans,id',
            'starts_at'            => 'required|date',
            'ends_at'              => 'nullable|date|after_or_equal:starts_at',
            'grace_days'           => 'integer|min:0|max:90',
            'is_active'            => 'boolean',
            'notes'                => 'nullable|string|max:1000',
        ]);

        $sub = Subscription::create([
            'organization_id'      => $validated['organization_id'],
            'subscription_plan_id' => $validated['plan_id'],
            'starts_at'            => $validated['starts_at'],
            'ends_at'              => $validated['ends_at'] ?? null,
            'grace_days'           => $validated['grace_days'] ?? 7,
            'is_active'            => $validated['is_active'] ?? true,
            'notes'                => $validated['notes'] ?? null,
        ]);

        $sub->load(['organization', 'plan']);

        return response()->json(['success' => true, 'subscription' => $this->format($sub)], 201);
    }

    public function show(int $id)
    {
        $sub = Subscription::with(['organization', 'plan'])->findOrFail($id);

        return response()->json(['success' => true, 'subscription' => $this->format($sub)]);
    }

    public function update(Request $request, int $id)
    {
        $sub = Subscription::findOrFail($id);

        $validated = $request->validate([
            'plan_id'    => 'sometimes|exists:subscription_plans,id',
            'starts_at'  => 'sometimes|date',
            'ends_at'    => 'nullable|date',
            'grace_days' => 'integer|min:0|max:90',
            'is_active'  => 'boolean',
            'notes'      => 'nullable|string|max:1000',
        ]);

        if (isset($validated['plan_id'])) {
            $validated['subscription_plan_id'] = $validated['plan_id'];
            unset($validated['plan_id']);
        }

        $sub->update($validated);
        $sub->load(['organization', 'plan']);

        return response()->json(['success' => true, 'subscription' => $this->format($sub)]);
    }

    public function toggle(int $id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->update(['is_active' => !$sub->is_active]);
        $sub->load(['organization', 'plan']);

        return response()->json(['success' => true, 'subscription' => $this->format($sub)]);
    }

    // ─── Public endpoint: status for the authenticated user's org ─────────────

    /**
     * GET /api/subscription/status
     * Returns subscription status for the authenticated user's organisation.
     */
    public static function status(Request $request)
    {
        $user = $request->user();

        if (!$user->organization_id) {
            return response()->json([
                'subscription'        => null,
                'is_active'           => true,
                'is_expired'          => false,
                'in_grace_period'     => false,
                'grace_days_remaining'=> 0,
                'days_remaining'      => null,
                'is_blocked'          => false,
            ]);
        }

        $sub = Subscription::with('plan')
            ->where('organization_id', $user->organization_id)
            ->latest()
            ->first();

        if (!$sub) {
            return response()->json([
                'subscription'        => null,
                'is_active'           => false,
                'is_expired'          => false,
                'in_grace_period'     => false,
                'grace_days_remaining'=> 0,
                'days_remaining'      => null,
                'is_blocked'          => false,
            ]);
        }

        return response()->json([
            'subscription'         => [
                'id'         => $sub->id,
                'plan'       => $sub->plan,
                'starts_at'  => $sub->starts_at,
                'ends_at'    => $sub->ends_at,
                'grace_days' => $sub->grace_days,
                'is_active'  => $sub->is_active,
            ],
            'is_active'            => $sub->is_active,
            'is_expired'           => $sub->is_expired,
            'in_grace_period'      => $sub->in_grace_period,
            'grace_days_remaining' => $sub->grace_days_remaining,
            'days_remaining'       => $sub->days_remaining,
            'is_blocked'           => $sub->is_blocked,
        ]);
    }

    // ─── Private helper ───────────────────────────────────────────────────────

    private function format(Subscription $sub): array
    {
        return [
            'id'              => $sub->id,
            'organization_id' => $sub->organization_id,
            'plan_id'         => $sub->subscription_plan_id,
            'organization'    => $sub->organization,
            'plan'            => $sub->plan,
            'starts_at'       => $sub->starts_at,
            'ends_at'         => $sub->ends_at,
            'grace_days'      => $sub->grace_days,
            'is_active'       => $sub->is_active,
            'notes'           => $sub->notes,
            'is_expired'      => $sub->is_expired,
            'in_grace_period' => $sub->in_grace_period,
            'days_remaining'  => $sub->days_remaining,
            'is_blocked'      => $sub->is_blocked,
            'created_at'      => $sub->created_at,
        ];
    }
}
