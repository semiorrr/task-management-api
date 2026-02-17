<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;

class TeamLeaderOwnTeam
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Resolve team from route (could be model or id)
        $routeTeam = $request->route('team');
        $team = $routeTeam instanceof Team ? $routeTeam : Team::find($routeTeam);

        // Admins can modify any team
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Team leaders can only modify their own team
        if ($user->role === 'team_leader' && $team && $user->id === $team->leader_id) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
