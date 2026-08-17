<?php

namespace App\Livewire\Frontend\Pages;

use App\Models\ContactMessage;
use App\Models\Page;
use App\Services\ContactNotifier;
use Livewire\Component;

class ContactFormBlock extends Component
{
    public Page $page;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $subject = '';

    public string $message = '';

    public bool $sent = false;

    public function mount(Page $page): void
    {
        $this->page = $page;

        if (auth()->check()) {
            $this->name = auth()->user()->name;
            $this->email = auth()->user()->email;
        }
    }

    public function send(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:10|max:2000',
        ]);

        $contactMessage = ContactMessage::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'subject' => $this->subject ?: null,
            'message' => $this->message,
            'page_title' => $this->page->title,
        ]);

        ContactNotifier::notify($contactMessage);

        $this->sent = true;
        $this->reset(['phone', 'subject', 'message']);
    }

    public function render()
    {
        return view('livewire.frontend.pages.contact-form-block');
    }
}
