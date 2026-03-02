<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'team_id',
        'profile_pic',
    ];

    protected $appends = ['profile_pic_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'team_id', 'team_id');
    }

    public function leaderTeam()
    {
        return $this->hasOne(Team::class, 'leader_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function getProfilePicUrlAttribute()
    {
        if (!$this->profile_pic) {
            return url('images/default-user.png');
        }

        return url('storage/' . $this->profile_pic);
    }
}
