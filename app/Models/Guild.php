<?php

namespace App\Models;

/**
 * Representa a Guilda Permanente na plataforma Motirõ.
 * Estende Team mapeando para a persistência 'teams', proporcionando
 * um modelo conceitual explícito e orientado ao domínio de guildas.
 */
class Guild extends Team
{
    protected $table = 'teams';
}
