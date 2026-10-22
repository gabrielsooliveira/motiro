<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'email',
        'slug',
        'type',
        'logo',
        'website',
        'description',
        'is_active',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user', 'organization_id', 'user_id')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class, 'organization_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'organization_id');
    }

    public function hackathons(): HasMany
    {
        return $this->hasMany(Hackathon::class, 'organization_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'organization_id');
    }
}
