<?php

namespace App\Policies;

use App\Models\HackathonMember;
use App\Models\HackathonRegistration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HackathonRegistrationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the registration.
     */
    public function view(User $user, HackathonRegistration $registration): bool
    {
        // 1. Participant himself
        if ($registration->user_id === $user->id) {
            return true;
        }

        // 2. Member of the same team
        if ($registration->team_id && $registration->team?->members()->where('users.id', $user->id)->exists()) {
            return true;
        }

        // 3. Organization staff
        $hackathon = $registration->hackathon;
        if ($hackathon) {
            if ($hackathon->organization && $hackathon->organization->owner_id === $user->id) {
                return true;
            }
            if ($user->isOrganizationAdmin($hackathon->organization_id)) {
                return true;
            }
            if (HackathonMember::where('hackathon_id', $hackathon->id)->where('user_id', $user->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can review and update status of a registration (approve, reject, disqualify).
     * Collaborators are strictly forbidden from approving or rejecting registrations.
     */
    public function updateStatus(User $user, HackathonRegistration $registration): bool
    {
        $hackathon = $registration->hackathon;
        if (!$hackathon) {
            return false;
        }

        // 1. Organization owner
        if ($hackathon->organization && $hackathon->organization->owner_id === $user->id) {
            return true;
        }

        // 2. Organization admin or manager
        $orgRole = $user->getOrganizationRole($hackathon->organization_id);
        if ($orgRole === 'admin' || $orgRole === 'manager') {
            return true;
        }

        // 3. Specific hackathon member with admin or manager role
        $member = HackathonMember::where('hackathon_id', $hackathon->id)
            ->where('user_id', $user->id)
            ->first();

        if ($member && in_array($member->role, ['admin', 'manager'])) {
            return true;
        }

        // 4. RBAC team.approve permission (excluding generic collaborators)
        if ($orgRole !== 'collaborator' && $user->hasPermission('team.approve', $hackathon->organization_id, $hackathon->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel their registration.
     */
    public function cancel(User $user, HackathonRegistration $registration): bool
    {
        if ($registration->user_id === $user->id) {
            return true;
        }

        if ($registration->team_id && $registration->team?->leader_id === $user->id) {
            return true;
        }

        return false;
    }
}
