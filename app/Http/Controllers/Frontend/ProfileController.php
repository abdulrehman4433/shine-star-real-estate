<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('frontend.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request, UpdatesUserProfileInformation $updater): RedirectResponse
    {
        $updater->update($request->user(), $request->all());

        return back()->with('status', 'profile-updated');
    }
}
