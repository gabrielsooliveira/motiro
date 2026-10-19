<?php

namespace App\Policies;

use App\Models\Hackathon;
use App\Models\HackathonMember;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HackathonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the hackathon.
     */
    public function view(?User $user, Hackathon $hackathon): bool
    {
        if (in_array($hackathon->status, ['published', 'registration', 'submission', 'voting', 'finished'])) {
            return true;
        }

        if (!$user) {
            return false;
        }

        // Drafts can only be viewed by active members of the organization
        if ($hackathon->organization && $hackathon->organization->owner_id === $user->id) {
            return true;
        }

        return $hackathon->organization?->users()
            ->where('users.id', $user->id)
            ->wherePivot('is_active', true)
            ->exists() ?? false;
    }

    /**
     * Determine whether the user can manage the hackathon (view dashboard/manage area).
     */
    public function manage(User $user, Hackathon $hackathon): bool
    {
        // 1. Organization owner always has access
        if ($hackathon->organization && $hackathon->organization->owner_id === $user->id) {
            return true;
        }

        // 2. Organization admin or manager
        $orgRole = $user->getOrganizationRole($hackathon->organization_id);
        if (in_array($orgRole, ['admin', 'manager'])) {
            return true;
        }

        // 3. Member assigned specifically to this hackathon
        $member = HackathonMember::where('hackathon_id', $hackathon->id)
            ->where('user_id', $user->id)
            ->first();

        if ($member) {
            // Collaborators have read-only operational view in manage
            return true;
        }

        // 4. RBAC permission
        return $user->hasPermission('hackathon.configure', $hackathon->organization_id, $hackathon->id);
    }

    /**
     * Determine whether the user can update the hackathon configuration.
     * Collaborators are strictly forbidden from modifying hackathon settings.
     */
    public function update(User $user, Hackathon $hackathon): bool
    {
        if ($hackathon->organization && $hackathon->organization->owner_id === $user->id) {
            return true;
        }

        $orgRole = $user->getOrganizationRole($hackathon->organization_id);
        if ($orgRole === 'admin' || $orgRole === 'manager') {
            return true;
        }

        $member = HackathonMember::where('hackathon_id', $hackathon->id)
            ->where('user_id', $user->id)
            ->first();

        if ($member && in_array($member->role, ['admin', 'manager'])) {
            return true;
        }

        return $user->hasPermission('hackathon.update', $hackathon->organization_id, $hackathon->id);
    }

    /**
     * Determine whether the user can delete the hackathon.
     */
    public function delete(User $user, Hackathon $hackathon): bool
    {
        if ($hackathon->organization && $hackathon->organization->owner_id === $user->id) {
            return true;
        }

        return $user->isOrganizationAdmin($hackathon->organization_id);
    }

    /**
     * Determine whether the user can manage stages, dates and criteria.
     * Collaborators cannot alter schedule or evaluation criteria.
     */
    public function manageStages(User $user, Hackathon $hackathon): bool
    {
        return $this->update($user, $hackathon);
    }

    /**
     * Determine whether the user can manage hackathon staff/judges.
     * Collaborators cannot add or remove staff/judges.
     */
    public function manageStaff(User $user, Hackathon $hackathon): bool
    {
        if ($hackathon->organization && $hackathon->organization->owner_id === $user->id) {
            return true;
        }

        if ($user->isOrganizationAdmin($hackathon->organization_id)) {
            return true;
        }

        $member = HackathonMember::where('hackathon_id', $hackathon->id)
            ->where('user_id', $user->id)
            ->first();

        return $member && $member->role === 'admin';
    }

    /**
     * Determine whether the user can manage participants and registrations.
     * Collaborators cannot allocate, transfer or approve/reject.
     */
    public function manageParticipants(User $user, Hackathon $hackathon): bool
    {
        return $this->update($user, $hackathon);
    }

    /**
     * Determine whether the user can publish final results.
     */
    public function publishResults(User $user, Hackathon $hackathon): bool
    {
        return $this->update($user, $hackathon);
    }
}
