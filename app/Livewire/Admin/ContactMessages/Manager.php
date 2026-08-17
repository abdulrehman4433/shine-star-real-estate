<?php

namespace App\Livewire\Admin\ContactMessages;

use App\Livewire\Concerns\Notifies;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use Notifies;
    use WithPagination;

    public string $search = '';

    public bool $unreadOnly = false;

    // 0 (not null) when nothing is selected — same sentinel pattern as Admin\Chat\Manager, and for the
    // same reason: makes "notification bell"-style deep links like ?contact=5 straightforward to add later.
    #[Url(as: 'contact')]
    public int $activeMessageId = 0;

    public string $replyBody = '';

    public bool $notifyEnabled = true;

    public string $notifyEmail = '';

    public function mount(): void
    {
        if ($this->activeMessageId) {
            $this->markRead($this->activeMessageId);
        }

        $this->notifyEnabled = (bool) Setting::get('contact_notify_enabled', true);
        $this->notifyEmail = Setting::get('contact_notify_email', '');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingUnreadOnly(): void
    {
        $this->resetPage();
    }

    public function view(int $messageId): void
    {
        $this->activeMessageId = $messageId;
        $this->markRead($messageId);
    }

    private function markRead(int $messageId): void
    {
        ContactMessage::find($messageId)?->markRead();
    }

    public function markUnread(int $messageId): void
    {
        ContactMessage::find($messageId)?->markUnread();
    }

    public function closeThread(): void
    {
        $this->activeMessageId = 0;
    }

    public function delete(int $messageId): void
    {
        ContactMessage::find($messageId)?->delete();

        if ($this->activeMessageId === $messageId) {
            $this->activeMessageId = 0;
        }

        $this->notifySuccess('Message deleted.');
    }

    public function sendReply(): void
    {
        if (! $this->activeMessageId) {
            return;
        }

        $this->validate(['replyBody' => 'required|string|max:5000']);

        $contact = ContactMessage::findOrFail($this->activeMessageId);

        try {
            Mail::raw($this->replyBody, function ($mail) use ($contact) {
                $mail->to($contact->email, $contact->name)
                    ->subject('Re: '.($contact->subject ?: 'Your message to us'));
            });

            $this->reset('replyBody');
            $this->notifySuccess('Reply sent to '.$contact->email.'.');
        } catch (\Throwable $e) {
            Log::warning('Contact message reply failed to send: '.$e->getMessage());
            $this->notifyError('Could not send the reply — check SMTP Settings. The error was: '.$e->getMessage());
        }
    }

    /** The on-page "receive emails" control — deliberately available right here where the submissions
     *  themselves are shown, not just buried in Settings, per explicit request. Writes to the exact same
     *  contact_notify_enabled/contact_notify_email settings Admin\Settings\GeneralManager edits, so
     *  changing it in either place is instantly reflected in the other. */
    public function saveNotifySettings(): void
    {
        $this->validate(['notifyEmail' => 'nullable|email|max:255']);

        Setting::set('contact_notify_enabled', $this->notifyEnabled ? '1' : '0');
        Setting::set('contact_notify_email', $this->notifyEmail);

        $this->notifySuccess('Notification settings saved.');
    }

    public function render()
    {
        $messages = ContactMessage::query()
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('subject', 'like', $term);
                });
            })
            ->when($this->unreadOnly, fn ($query) => $query->unread())
            ->latest()
            ->paginate(15);

        $activeMessage = $this->activeMessageId
            ? ContactMessage::find($this->activeMessageId)
            : null;

        $totalMessages = ContactMessage::count();
        $unreadMessages = ContactMessage::unread()->count();

        return view('livewire.admin.contact-messages.manager', [
            'messages' => $messages,
            'activeMessage' => $activeMessage,
            'totalMessages' => $totalMessages,
            'unreadMessages' => $unreadMessages,
            'readMessages' => $totalMessages - $unreadMessages,
        ])->extends('admin.layouts.app')->section('content');
    }
}
