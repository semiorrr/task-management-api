<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NormalUsersCannotManageMembers
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role === 'user') {
            return response()->json(['message' => 'Unauthorized - normal users cannot manage members'], 403);
        }

        return $next($request);
    }
}
