<?php

namespace App\Http\Controllers;

use App\Models\OurTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OurteamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     public function index()
    {
        $teams = OurTeam::all();
        return response()->json([
            'success' => true,
            'data' => $teams
        ], 200);
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
            'name'        => 'required|string',
            'position'    => 'required|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp,gif',
            'phone'       => ['nullable', 'regex:/^(97|98)[0-9]{8}$/'],
            'email'       => 'nullable|email|unique:our_teams,email',
            'description' => 'nullable|string',
        ]);

        // handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('teams', 'public'); // stores to storage/app/public/teams
        }

        $team = OurTeam::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Team member created successfully',
            'data'    => $team->fresh(),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $team = OurTeam::find($id);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team member not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $team
        ], 200);
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
         $team = OurTeam::find($id);
        if (!$team) {
            return response()->json(['success' => false, 'message' => 'Team member not found'], 404);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'position'    => 'sometimes|required|string|max:255',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
            'phone'       => ['nullable', 'regex:/^(97|98)[0-9]{8}$/'],
            'email'       => ['nullable','email', Rule::unique('our_teams','email')->ignore($team->id)],
            'description' => 'nullable|string',
        ]);

        // replace image if new file given
        if ($request->hasFile('image')) {
            // delete old if exists
            if ($team->image && Storage::disk('public')->exists($team->image)) {
                Storage::disk('public')->delete($team->image);
            }
            $validated['image'] = $request->file('image')->store('teams', 'public');
        }

        $team->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Team member updated successfully',
            'data'    => $team->fresh(),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $team = OurTeam::find($id);
        if (!$team) {
            return response()->json(['success' => false, 'message' => 'Team member not found'], 404);
        }

        // delete stored image
        if ($team->image && Storage::disk('public')->exists($team->image)) {
            Storage::disk('public')->delete($team->image);
        }

        $team->delete();

        return response()->json(['success' => true, 'message' => 'Team member deleted successfully'], 200);
    }

    /**
     * Count Total number of team.
     */
    public function team_count()
    {
        $total = OurTeam::count();

        return response()->json([
            'success' => true,
            'total' => $total,
        ], 200);
    }

}
