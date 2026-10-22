<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->session()->get('current_context') !== ContextService::CONTEXT_COMPETITOR) {
            ContextService::setCompetitorContext($request);
        }

        return inertia('Participant/Dashboard/Home', [
            'user' => $user
        ]);
    }
}
