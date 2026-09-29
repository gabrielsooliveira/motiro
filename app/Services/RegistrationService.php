<?php

namespace App\Services;

use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    /**
     * Register a team or user into a hackathon
     */
    public function registerTeam(Hackathon $hackathon, Team $team, User $user, array $answers = [], array $documents = []): HackathonRegistration
    {
        // 1. Check hackathon status and dates
        $now = Carbon::now();
        if ($hackathon->registration_start_date && $now->lt($hackathon->registration_start_date)) {
            throw new Exception('O período de inscrições para este Hackathon ainda não foi iniciado.');
        }

        if ($hackathon->registration_end_date && $now->gt($hackathon->registration_end_date)) {
            throw new Exception('O período de inscrições para este Hackathon já foi encerrado.');
        }

        if (!in_array($hackathon->status, ['published', 'registration'])) {
            throw new Exception('Este Hackathon não está aberto para inscrições no momento.');
        }

        // 2. Check team constraints
        $memberCount = $team->members()->count();
        if ($memberCount < $hackathon->min_team_members) {
            throw new Exception("A equipe possui {$memberCount} integrantes, mas o mínimo exigido é de {$hackathon->min_team_members}.");
        }

        if ($memberCount > $hackathon->max_team_members) {
            throw new Exception("A equipe possui {$memberCount} integrantes, excedendo o máximo permitido de {$hackathon->max_team_members}.");
        }

        // 3. Check if team already registered
        $existing = HackathonRegistration::where('hackathon_id', $hackathon->id)
            ->where('team_id', $team->id)
            ->first();

        if ($existing) {
            throw new Exception('Esta equipe já realizou inscrição neste Hackathon.');
        }

        // 4. Check if any member of this team is already registered in this hackathon with another team
        $memberIds = $team->members()->pluck('users.id')->toArray();
        $conflictingMember = DB::table('hackathon_registrations')
            ->join('teams', 'hackathon_registrations.team_id', '=', 'teams.id')
            ->join('team_members', 'teams.id', '=', 'team_members.team_id')
            ->join('users', 'team_members.user_id', '=', 'users.id')
            ->where('hackathon_registrations.hackathon_id', $hackathon->id)
            ->whereIn('hackathon_registrations.status', ['pending', 'under_review', 'approved'])
            ->whereIn('team_members.user_id', $memberIds)
            ->select('users.name')
            ->first();

        if ($conflictingMember) {
            throw new Exception("O participante {$conflictingMember->name} já está inscrito em outra equipe neste Hackathon.");
        }

        // 5. Max teams limit check
        if ($hackathon->max_teams) {
            $approvedOrPending = HackathonRegistration::where('hackathon_id', $hackathon->id)
                ->whereIn('status', ['approved', 'pending'])
                ->count();

            if ($approvedOrPending >= $hackathon->max_teams) {
                throw new Exception('O limite máximo de equipes para este Hackathon foi atingido.');
            }
        }

        $initialStatus = $hackathon->auto_approve_registrations ? 'approved' : 'pending';

        return DB::transaction(function () use ($hackathon, $team, $user, $initialStatus, $answers, $documents) {
            $registration = HackathonRegistration::create([
                'hackathon_id' => $hackathon->id,
                'team_id' => $team->id,
                'user_id' => $user->id,
                'status' => $initialStatus,
                'custom_answers' => $answers,
                'submitted_documents' => $documents,
                'approved_at' => $initialStatus === 'approved' ? Carbon::now() : null,
                'approved_by' => $initialStatus === 'approved' ? $user->id : null,
            ]);

            AuditService::log('registration.created', $registration, null, $registration->toArray(), $hackathon->organization_id, $hackathon->id);

            NotificationService::notify(
                $user,
                'registration_created',
                'Inscrição Realizada',
                "Sua equipe '{$team->name}' foi inscrita no Hackathon '{$hackathon->title}'. Status: {$initialStatus}."
            );

            return $registration;
        });
    }

    /**
     * Admin review for registrations (approve/reject/disqualify)
     */
    public function updateStatus(HackathonRegistration $registration, string $status, User $adminUser, ?string $reason = null): HackathonRegistration
    {
        $old = $registration->toArray();

        $registration->status = $status;
        if ($status === 'approved') {
            $registration->approved_at = Carbon::now();
            $registration->approved_by = $adminUser->id;
            $registration->rejection_reason = null;
        } elseif ($status === 'rejected' || $status === 'disqualified') {
            $registration->rejection_reason = $reason;
        }

        $registration->save();

        AuditService::log("registration.{$status}", $registration, $old, $registration->toArray(), $registration->hackathon->organization_id, $registration->hackathon_id);

        if ($registration->team) {
            NotificationService::notifyTeam(
                $registration->team,
                "registration_{$status}",
                "Status da Inscrição Atualizado",
                "A inscrição da sua equipe no Hackathon '{$registration->hackathon->title}' agora está: {$status}." . ($reason ? " Motivo: {$reason}" : "")
            );
        }

        return $registration;
    }
}
