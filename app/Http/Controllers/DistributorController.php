<?php

namespace App\Http\Controllers;

use App\Models\Distributor;
use Illuminate\Http\Request;

class DistributorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $distributors = Distributor::all();
        return response()->json($distributors);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $validated = $request->validate([
            'name'     => 'required|string|unique:distributors,name',
            'location' => 'required|string',
            'phone'    => ['required','regex:/^(98|97)[0-9]{8}$/'],
            'email'    => 'required|email|unique:distributors,email',
        ]);

        $distributor = Distributor::create($validated);

        return response()->json([
            'message' => 'Distributor created successfully',
            'data' => $distributor
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $distributor = Distributor::find($id);

        if (!$distributor) {
            return response()->json(['message' => 'Distributor not found'], 404);
        }

        return response()->json($distributor);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         $distributor = Distributor::find($id);

        if (!$distributor) {
            return response()->json(['message' => 'Distributor not found'], 404);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|required|string|max:255|unique:distributors,name,' . $id,
            'location' => 'sometimes|required|string|max:255',
            'phone'    => ['sometimes','required','regex:/^(98|97)[0-9]{8}$/'],
            'email'    => 'sometimes|required|email|max:255|unique:distributors,email,' . $id,
        ]);

        $distributor->update($validated);

        return response()->json([
            'message' => 'Distributor updated successfully',
            'data' => $distributor
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
         $distributor = Distributor::find($id);

        if (!$distributor) {
            return response()->json(['message' => 'Distributor not found'], 404);
        }

        $distributor->delete();

        return response()->json(['message' => 'Distributor deleted successfully']);
    }
}
