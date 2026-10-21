<?php

namespace App\Models;

/**
 * Representa um Convite de Guilda na plataforma Motirõ.
 */
class GuildInvitation extends TeamInvitation
{
    protected $table = 'team_invitations';

    public function guild()
    {
        return $this->belongsTo(Guild::class, 'team_id');
    }
}
