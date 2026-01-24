<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MachineController extends Controller
{
    /**
     * Display a listing of all machines
     */
    public function index()
    {
        try {
            // SuperAdmin voit toutes les machines sans filtre d'organisation
            $machines = Machine::withoutGlobalScope('organization')
                ->with(['activeSession', 'organization'])
                ->orderBy('organization_id', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($machine) {
                    return [
                        'id' => $machine->id,
                        'machine_number' => $machine->id,
                        'type' => $machine->name ?? 'Standard',
                        'status' => $machine->status,
                        'organization_id' => $machine->organization_id,
                        'organization_name' => $machine->organization->name ?? 'Non assignée',
                        'created_at' => $machine->created_at,
                        'updated_at' => $machine->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'machines' => $machines
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des machines',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created machine
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machine_number' => 'nullable|integer',
            'type' => 'nullable|string|max:255',
            'status' => 'required|in:available,in_session,maintenance',
            'organization_id' => 'required|exists:organizations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $machine = Machine::create([
                'name' => $request->type ?? 'Standard',
                'status' => $request->status ?? 'available',
                'organization_id' => $request->organization_id
            ]);

            $machine->load('organization');

            return response()->json([
                'success' => true,
                'message' => 'Machine créée avec succès',
                'machine' => [
                    'id' => $machine->id,
                    'machine_number' => $machine->id,
                    'type' => $machine->name,
                    'status' => $machine->status,
                    'organization_id' => $machine->organization_id,
                    'organization_name' => $machine->organization->name ?? 'Non assignée',
                    'created_at' => $machine->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la machine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified machine
     */
    public function show($id)
    {
        try {
            $machine = Machine::withoutGlobalScope('organization')
                ->with(['activeSession', 'organization'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'machine' => [
                    'id' => $machine->id,
                    'machine_number' => $machine->id,
                    'type' => $machine->name,
                    'status' => $machine->status,
                    'organization_id' => $machine->organization_id,
                    'organization_name' => $machine->organization->name ?? 'Non assignée',
                    'created_at' => $machine->created_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Machine non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified machine
     */
    public function update(Request $request, $id)
    {
        try {
            $machine = Machine::withoutGlobalScope('organization')->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|nullable|string|max:255',
                'status' => 'sometimes|required|in:available,in_session,maintenance',
                'organization_id' => 'sometimes|required|exists:organizations,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update fields
            if ($request->has('type')) {
                $machine->name = $request->type;
            }
            if ($request->has('status')) {
                $machine->status = $request->status;
            }
            if ($request->has('organization_id')) {
                $machine->organization_id = $request->organization_id;
            }

            $machine->save();
            $machine->load('organization');

            return response()->json([
                'success' => true,
                'message' => 'Machine modifiée avec succès',
                'machine' => [
                    'id' => $machine->id,
                    'machine_number' => $machine->id,
                    'type' => $machine->name,
                    'status' => $machine->status,
                    'organization_id' => $machine->organization_id,
                    'organization_name' => $machine->organization->name ?? 'Non assignée',
                    'updated_at' => $machine->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la machine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified machine
     */
    public function destroy($id)
    {
        try {
            $machine = Machine::withoutGlobalScope('organization')->findOrFail($id);

            // Check if machine has an active session
            if ($machine->activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une machine avec une session active'
                ], 403);
            }

            $machine->delete();

            return response()->json([
                'success' => true,
                'message' => 'Machine supprimée avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la machine',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
