<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;

class ManageTeamMembers
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $routeTeam = $request->route('team');
        $team = $routeTeam instanceof Team ? $routeTeam : Team::find($routeTeam);

        if ($user->role === 'admin') {
            return $next($request);
        }

        if ($user->role === 'team_leader' && $team && $user->team_id === $team->id) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
