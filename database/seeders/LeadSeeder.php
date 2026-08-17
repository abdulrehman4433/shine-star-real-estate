<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Lead::query()->exists()) {
            return;
        }

        $agent = User::query()->where('email', 'agent@shinestarmarketing.test')->first();
        $property = Property::query()->where('title', 'Modern 2-Bed Apartment in Downtown')->first();

        if (! $agent || ! $property) {
            $this->command?->warn('Skipping LeadSeeder: run DemoUserSeeder and PropertySeeder first.');

            return;
        }

        // Creating the inquiry fires PropertyInquiryObserver, which auto-creates the matching lead.
        $inquiry = PropertyInquiry::create([
            'property_id' => $property->id,
            'name' => 'Sam Prospective Buyer',
            'email' => 'sam.buyer@example.com',
            'phone' => '555-0100',
            'message' => 'Is this still available? I would like to schedule a viewing this week.',
        ]);

        $lead = $inquiry->lead;
        $lead->update(['assigned_to' => $agent->id]);

        $lead->activities()->create([
            'user_id' => $agent->id,
            'type' => 'call',
            'notes' => 'Called to confirm availability, buyer is very interested.',
            'occurred_at' => now()->subDay(),
        ]);

        $lead->tasks()->create([
            'assigned_to' => $agent->id,
            'title' => 'Follow up with Sam about viewing time',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);
    }
}
