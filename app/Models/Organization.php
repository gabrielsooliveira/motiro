<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasUuids;

    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'email',
        'slug',
        'type',
        'logo',
        'website',
        'description',
        'password',
        'is_active',
        'owner_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_user', 'organization_id', 'user_id')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function memberships()
    {
        return $this->hasMany(OrganizationUser::class, 'organization_id');
    }

    public function invitations()
    {
        return $this->hasMany(OrganizationInvitation::class, 'organization_id');
    }

    public function hackathons()
    {
        return $this->hasMany(Hackathon::class);
    }
}
