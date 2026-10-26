<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HackathonController extends Controller
{
    public function index()
    {
        return inertia('Participant/Dashboard/Hackathons', [
            'hackathons' => [
                'data' => [],
                'links' => [],
            ],
        ]);
    }
}
