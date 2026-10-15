<?php

namespace App\Actions\Organization;

use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcceptOrganizationInvitationAction
{
    /**
     * Aceita o convite institucional vinculando o usuário à organização.
     *
     * @param OrganizationInvitation $invitation
     * @param User $user
     * @param Request|null $request
     * @return OrganizationUser
     */
    public function execute(OrganizationInvitation $invitation, User $user, ?Request $request = null): OrganizationUser
    {
        return DB::transaction(function () use ($invitation, $user, $request) {
            $orgUser = OrganizationUser::updateOrCreate(
                [
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $invitation->role,
                    'is_active' => true,
                ]
            );

            $invitation->update(['status' => 'accepted']);

            if ($request) {
                ContextService::setOrganizationContext($request, $invitation->organization_id);
            }

            return $orgUser;
        });
    }
}
