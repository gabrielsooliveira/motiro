<?php

namespace App\Http\Controllers\Participant;

use App\Models\Team;
use Inertia\Response;

class GuildController extends TeamController
{
    public function index(): Response
    {
        $teams = Team::whereNull('hackathon_id')->get();

        return inertia('Participant/Dashboard/Guild/Index', [
            'teams' => $teams,
        ]);
    }
}
