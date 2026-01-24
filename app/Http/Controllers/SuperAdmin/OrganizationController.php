<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::withCount(['users', 'machines', 'customers', 'games'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'organizations' => $organizations
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:organizations,code',
            'is_active' => 'boolean',
        ]);

        // Générer un code automatiquement si non fourni
        if (empty($validated['code'])) {
            $validated['code'] = Str::upper(Str::slug($validated['name'], '_'));

            // S'assurer que le code est unique
            $baseCode = $validated['code'];
            $counter = 1;
            while (Organization::where('code', $validated['code'])->exists()) {
                $validated['code'] = $baseCode . '_' . $counter;
                $counter++;
            }
        }

        $organization = Organization::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Organisation créée avec succès',
            'organization' => $organization
        ], 201);
    }

    public function show($id)
    {
        $organization = Organization::withCount(['users', 'machines', 'customers', 'games'])
            ->with(['users:id,name,email,role,organization_id'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'organization' => $organization
        ]);
    }

    public function update(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:organizations,code,' . $id,
            'is_active' => 'boolean',
        ]);

        $organization->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Organisation mise à jour avec succès',
            'organization' => $organization
        ]);
    }

    public function destroy($id)
    {
        $organization = Organization::findOrFail($id);

        // Vérifier si c'est l'organisation par défaut
        if ($organization->code === 'DEFAULT') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer l\'organisation par défaut'
            ], 400);
        }

        // Vérifier s'il y a des utilisateurs
        if ($organization->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une organisation avec des utilisateurs. Veuillez d\'abord réassigner ou supprimer les utilisateurs.'
            ], 400);
        }

        $organization->delete();

        return response()->json([
            'success' => true,
            'message' => 'Organisation supprimée avec succès'
        ]);
    }

    public function assignUser(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Ne pas assigner les super_admin à une organisation
        if ($user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'assigner un Super Admin à une organisation'
            ], 400);
        }

        $user->update(['organization_id' => $organization->id]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur assigné à l\'organisation avec succès',
            'user' => $user->fresh(['organization'])
        ]);
    }

    public function removeUser(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::where('id', $validated['user_id'])
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $user->update(['organization_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur retiré de l\'organisation avec succès'
        ]);
    }

    public function users($id)
    {
        $organization = Organization::findOrFail($id);

        $users = $organization->users()
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    public function stats($id)
    {
        $organization = Organization::findOrFail($id);

        return response()->json([
            'success' => true,
            'stats' => [
                'users_count' => $organization->users()->count(),
                'machines_count' => $organization->machines()->count(),
                'customers_count' => $organization->customers()->count(),
                'games_count' => $organization->games()->count(),
                'active_sessions' => $organization->gameSessions()->whereNull('ended_at')->count(),
                'total_sessions' => $organization->gameSessions()->count(),
                'products_count' => $organization->products()->count(),
            ]
        ]);
    }
}
