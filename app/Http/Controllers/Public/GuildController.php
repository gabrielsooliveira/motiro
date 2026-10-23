<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuildController extends Controller
{
    public function index()
    {
        return inertia('Site/Guild/Index', []);
    }

    public function show(string $slug)
    {
        return inertia('Site/Guild/Show', []);
    }
}
