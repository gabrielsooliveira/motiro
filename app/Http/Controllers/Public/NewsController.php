<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        return inertia('Site/News/Index', []);
    }

    public function show(string $slug)
    {
        return inertia('Site/News/Show', []);
    }
}
