<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        $includes = array_filter(explode(',', request()->query('include', '')));

        if ($authUser->role === 'admin') {
            $query = Team::query();
        } else {
            $query = Team::where('id', $authUser->team_id);
        }

        if (in_array('users', $includes)) {
            $query->with('members');
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
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
        $includes = array_filter(explode(',', request()->query('include', '')));

        if (in_array('users', $includes)) {
            $team->load('members');
        }

        return response()->json($team);
    }

    public function addMember(Request $request, Team $team)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = \App\Models\User::find($validated['user_id']);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->team_id = $team->id;
        $user->save();

        return response()->json(['message' => 'Member added', 'user' => $user], 200);
    }

    public function removeMember(Team $team, $userId)
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->team_id !== $team->id) {
            return response()->json(['message' => 'User is not a member of this team'], 400);
        }

        $user->team_id = null;
        $user->save();

        return response()->json(['message' => 'Member removed'], 200);
    }

    public function update(Request $request, Team $team)
    {
        $authUser = auth()->user();
        
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
        $team->delete();

        return response()->json(['message' => 'Team deleted successfully']);
    }

    public function uploadProfilePic(Request $request, Team $team)
    {
        $authUser = auth()->user();
        if ($authUser->role !== 'admin' && $authUser->role !== 'team_leader') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'profile_pic' => 'required|image|max:4096',
        ]);

        $path = $request->file('profile_pic')->store('profile_pics/teams', 'public');

        $team->profile_pic = $path;
        $team->save();

        return response()->json(['profile_pic_url' => $team->profile_pic_url], 200);
    }

    public function previewProfilePic(Team $team)
    {
        if (!$team->profile_pic) {
            return response()->json(['message' => 'No profile picture'], 404);
        }

        return redirect($team->profile_pic_url);
    }

    public function export(Request $request)
    {
        $authUser = auth()->user();
        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teams = Team::with('members', 'leader')->get();

        $filename = 'teams_export_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['name', 'description', 'leader_email', 'members_emails', 'profile_pic_url'];

        $callback = function () use ($teams, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($teams as $team) {
                $members = $team->members->pluck('email')->toArray();
                $leaderEmail = $team->leader ? $team->leader->email : '';
                $row = [
                    $team->name,
                    $team->description,
                    $leaderEmail,
                    implode(';', $members),
                    $team->profile_pic_url ?? '',
                ];
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $authUser = auth()->user();
        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return response()->json(['message' => 'Unable to open file'], 400);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            return response()->json(['message' => 'Empty file'], 400);
        }

        $created = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!$data) {
                continue;
            }

            $team = Team::firstOrCreate([
                'name' => $data['name'],
            ], [
                'description' => $data['description'] ?? null,
            ]);

            // leader
            if (!empty($data['leader_email'])) {
                $leader = \App\Models\User::firstOrCreate([
                    'email' => $data['leader_email'],
                ], [
                    'name' => $data['leader_email'],
                    'password' => bcrypt(str()->random(12)),
                    'role' => 'team_leader',
                ]);
                $team->leader_id = $leader->id;
                $team->save();
            }

            // members
            if (!empty($data['members_emails'])) {
                $members = explode(';', $data['members_emails']);
                foreach ($members as $email) {
                    $email = trim($email);
                    if (!$email) continue;
                    $user = \App\Models\User::firstOrCreate([
                        'email' => $email,
                    ], [
                        'name' => $email,
                        'password' => bcrypt(str()->random(12)),
                        'role' => 'user',
                    ]);
                    $user->team_id = $team->id;
                    $user->save();
                }
            }

            $created[] = $team->id;
        }

        fclose($handle);

        return response()->json(['created_team_ids' => $created], 201);
    }
}
