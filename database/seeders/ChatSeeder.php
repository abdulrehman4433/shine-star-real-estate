<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Conversation::query()->exists()) {
            return;
        }

        $user = User::query()->where('email', 'user@shinestarmarketing.test')->first();
        $property = Property::query()->where('title', 'Modern 2-Bed Apartment in Downtown')->first();

        if (! $user || ! $property) {
            $this->command?->warn('Skipping ChatSeeder: run DemoUserSeeder and PropertySeeder first.');

            return;
        }

        $conversation = Conversation::startBetween($user, $property->owner, $property);

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => 'Hi! Is this apartment still available for rent?',
        ]);

        $conversation->messages()->create([
            'sender_id' => $property->owner->id,
            'body' => 'Yes it is! Would you like to schedule a viewing?',
            'read_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);
    }
}
