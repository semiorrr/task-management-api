<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        
        // Only admins can see all teams
        if ($authUser->role === 'admin') {
            return response()->json(Team::all());
        }
        
        // Team leaders and users see only their team
        $team = Team::where('id', $authUser->team_id)->first();
        return response()->json($team ? [$team] : []);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        
        // Only admins can create teams
        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Only admins can create teams'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'leader_id' => 'required|integer|exists:users,id',
        ]);

        $team = Team::create($validated);

        return response()->json($team, 201);
    }

    public function show(Team $team)
    {
        return response()->json($team);
    }

    public function update(Request $request, Team $team)
    {
        $authUser = auth()->user();
        
        // Only admins can update teams
        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Only admins can update teams'], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'leader_id' => 'nullable|integer|exists:users,id',
        ]);

        $validated = array_filter($validated, fn($value) => $value !== null);
        
        $team->update($validated);

        return response()->json($team);
    }

    public function destroy(Team $team)
    {
        $authUser = auth()->user();
        
        // Only admins can delete teams
        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Only admins can delete teams'], 403);
        }

        $team->delete();

        return response()->json(['message' => 'Team deleted successfully']);
    }
}
