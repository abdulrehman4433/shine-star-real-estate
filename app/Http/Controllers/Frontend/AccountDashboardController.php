<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PropertyInquiry;

class AccountDashboardController extends Controller
{
    public function index()
    {
        $favoritesCount = auth()->user()->favorites()->count();
        $inquiriesCount = PropertyInquiry::query()->where('user_id', auth()->id())->count();

        return view('frontend.account.dashboard', compact('favoritesCount', 'inquiriesCount'));
    }
}
