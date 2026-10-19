<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the organization dashboard/details.
     */
    public function view(User $user, Organization $organization): bool
    {
        if ($organization->owner_id === $user->id) {
            return true;
        }

        return $organization->users()
            ->where('users.id', $user->id)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Determine whether the user can update the organization profile/settings.
     */
    public function update(User $user, Organization $organization): bool
    {
        if ($organization->owner_id === $user->id) {
            return true;
        }

        return $user->isOrganizationAdmin($organization->id);
    }

    /**
     * Determine whether the user can delete the organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $organization->owner_id === $user->id;
    }

    /**
     * Determine whether the user can manage organization members (invite, update role, remove).
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        if ($organization->owner_id === $user->id) {
            return true;
        }

        return $user->isOrganizationAdmin($organization->id);
    }

    /**
     * Determine whether the user can create a hackathon for this organization.
     */
    public function createHackathon(User $user, Organization $organization): bool
    {
        if ($organization->owner_id === $user->id) {
            return true;
        }

        $role = $user->getOrganizationRole($organization->id);
        if (in_array($role, ['admin', 'manager'])) {
            return true;
        }

        return $user->hasPermission('hackathon.create', $organization->id);
    }
}
