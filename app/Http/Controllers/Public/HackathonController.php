<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HackathonController extends Controller
{
    public function index()
    {
        return inertia('Site/Hackathon/Index', []);
    }

    public function show(string $slug)
    {
        return inertia('Site/Hackathon/Show', [
            'hackathon' => [],
        ]);
    }
}
