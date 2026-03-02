<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'leader_id',
        'profile_pic',
    ];

    protected $appends = ['profile_pic_url'];

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        return $this->hasMany(User::class, 'team_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function getProfilePicUrlAttribute()
    {
        if (!$this->profile_pic) {
            return url('images/default-team.png');
        }

        return url('storage/' . $this->profile_pic);
    }
}
