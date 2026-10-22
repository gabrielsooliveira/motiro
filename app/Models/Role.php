<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasUuids;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'model_has_roles', 'role_id', 'user_id')
            ->withPivot(['organization_id', 'hackathon_id'])
            ->withTimestamps();
    }
}
