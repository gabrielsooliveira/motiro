<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Response;

class SiteController extends Controller
{
    public function home(): Response
    {
        return inertia('Site/Home');
    }

    public function selectLogin(): Response
    {
        return inertia('Site/SelectLogin');
    }
}
