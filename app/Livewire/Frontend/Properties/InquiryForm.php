<?php

namespace App\Livewire\Frontend\Properties;

use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\User;
use App\Notifications\NewPropertyInquiry;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class InquiryForm extends Component
{
    public Property $property;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public bool $sent = false;

    public function mount(Property $property): void
    {
        $this->property = $property;

        if (auth()->check()) {
            $this->name = auth()->user()->name;
            $this->email = auth()->user()->email;
            $this->phone = (string) auth()->user()->phone;
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|min:10|max:2000',
        ];
    }

    public function send(): void
    {
        $this->validate();

        $inquiry = PropertyInquiry::create([
            'property_id' => $this->property->id,
            'user_id' => auth()->id(),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'message' => $this->message,
        ]);

        $recipients = User::query()
            ->where('id', $this->property->user_id)
            ->orWhereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin']))
            ->get()
            ->unique('id');

        Notification::send($recipients, new NewPropertyInquiry($inquiry));

        $this->sent = true;
        $this->message = '';
    }

    public function render()
    {
        return view('livewire.frontend.properties.inquiry-form');
    }
}
