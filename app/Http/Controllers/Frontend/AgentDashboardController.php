<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

class AgentDashboardController extends Controller
{
    public function index()
    {
        return view('frontend.agent.dashboard');
    }
}
