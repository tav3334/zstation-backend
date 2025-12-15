<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index()
{
    return \App\Models\Machine::with(['activeSession'])->get();
}


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'code' => 'nullable',
            'status' => 'nullable',
            'location' => 'nullable',
            'notes' => 'nullable'
        ]);

        return Machine::create($data);
    }

    public function show($id)
    {
        return Machine::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $machine = Machine::findOrFail($id);
        $machine->update($request->all());
        return $machine;
    }

    public function destroy($id)
    {
        $machine = Machine::findOrFail($id);
        $machine->delete();

        return response()->json(['message' => 'Machine supprimée']);
    }
}

