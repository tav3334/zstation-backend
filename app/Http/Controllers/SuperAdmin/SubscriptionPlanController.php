<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::withCount('subscriptions')->orderBy('price')->get();

        return response()->json(['success' => true, 'plans' => $plans]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price'       => 'required|numeric|min:0',
            'grace_days'  => 'integer|min:0|max:90',
            'limits'      => 'nullable|array',
            'features'    => 'nullable|array',
        ]);

        $plan = SubscriptionPlan::create($validated);

        return response()->json(['success' => true, 'plan' => $plan], 201);
    }

    public function show(int $id)
    {
        $plan = SubscriptionPlan::withCount('subscriptions')->findOrFail($id);

        return response()->json(['success' => true, 'plan' => $plan]);
    }

    public function update(Request $request, int $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price'       => 'sometimes|required|numeric|min:0',
            'grace_days'  => 'integer|min:0|max:90',
            'limits'      => 'nullable|array',
            'features'    => 'nullable|array',
        ]);

        $plan->update($validated);

        return response()->json(['success' => true, 'plan' => $plan]);
    }

    public function destroy(int $id)
    {
        $plan = SubscriptionPlan::withCount('subscriptions')->findOrFail($id);

        if ($plan->subscriptions_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Ce plan est utilisé par {$plan->subscriptions_count} abonnement(s). Supprimez-les d'abord.",
            ], 422);
        }

        $plan->delete();

        return response()->json(['success' => true, 'message' => 'Plan supprimé']);
    }
}
