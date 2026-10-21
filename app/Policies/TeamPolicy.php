<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determina se o usuário pode visualizar os detalhes da guilda.
     */
    public function view(User $user, Team $team): bool
    {
        return $team->isMember($user) || $team->canManage($user);
    }

    /**
     * Determina se o usuário pode atualizar informações e imagem da guilda.
     */
    public function update(User $user, Team $team): bool
    {
        return $team->canManage($user);
    }

    /**
     * Determina se o usuário pode excluir a guilda.
     */
    public function delete(User $user, Team $team): bool
    {
        // Apenas o criador (ou líder atual se criador_id for nulo) pode excluir a guilda
        if ($team->creator_id) {
            return $team->creator_id === $user->id;
        }

        return $team->leader_id === $user->id;
    }

    /**
     * Determina se o usuário pode enviar convites para a guilda.
     */
    public function invite(User $user, Team $team): bool
    {
        return $team->canManage($user) || $team->isMember($user);
    }

    /**
     * Determina se o usuário pode cancelar ou reenviar convites da guilda.
     */
    public function manageInvitations(User $user, Team $team): bool
    {
        return $team->canManage($user);
    }

    /**
     * Determina se o usuário pode transferir a liderança da guilda.
     */
    public function transferLeadership(User $user, Team $team, ?User $newLeader = null): bool
    {
        if (!$team->canManage($user)) {
            return false;
        }

        if ($newLeader && !$team->isMember($newLeader)) {
            return false;
        }

        return true;
    }

    /**
     * Determina se o usuário pode remover um membro da guilda.
     */
    public function removeMember(User $user, Team $team, User $member): bool
    {
        // Usuário tentando remover precisa ter poderes administrativos
        if (!$team->canManage($user)) {
            return false;
        }

        // Não é permitido remover o criador original da guilda
        if ($team->isCreator($member)) {
            return false;
        }

        // Não se remove a si mesmo por esta rota (deve usar a ação de sair)
        if ($user->id === $member->id) {
            return false;
        }

        return true;
    }

    /**
     * Determina se o usuário pode sair da guilda voluntariamente.
     */
    public function leave(User $user, Team $team): bool
    {
        // Deve ser membro da guilda
        if (!$team->isMember($user)) {
            return false;
        }

        // Se for o líder atual e ainda houver outros integrantes na guilda,
        // não pode sair sem antes transferir a liderança
        if ($team->isLeader($user) && $team->members()->count() > 1) {
            return false;
        }

        return true;
    }
}
