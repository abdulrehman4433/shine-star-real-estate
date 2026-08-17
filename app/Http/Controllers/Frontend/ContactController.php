<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\ContactNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles the plain-HTML contact form on the custom-built /contact-us page (see Page id with slug
 * 'contact-us' — it has its own hand-authored `html`/`css`, stored in the database and rendered via
 * Page::renderHtml()'s restricted safeSubstitute(), same mechanism the homepage uses). That stored HTML
 * can't contain Blade directives or a Livewire component (safeSubstitute() only recognizes `{{ $var }}`/
 * `{!! $var !!}` referencing a plain variable — see Page::renderHtml()'s doc comment for why), so this is
 * a plain, non-Livewire POST endpoint rather than the Livewire ContactFormBlock used by Page Builder pages.
 */
class ContactController extends Controller
{
    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|min:10|max:2000',
        ]);

        $contactMessage = ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'message' => $validated['message'],
            'page_title' => 'Contact Us',
        ]);

        ContactNotifier::notify($contactMessage);

        return back()->with('contact_success', true);
    }
}
