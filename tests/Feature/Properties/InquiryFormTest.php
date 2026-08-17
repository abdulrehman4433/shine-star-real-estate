<?php

namespace Tests\Feature\Properties;

use App\Enums\RoleName;
use App\Livewire\Frontend\Properties\InquiryForm;
use App\Models\Property;
use App\Models\User;
use App\Notifications\NewPropertyInquiry;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class InquiryFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_guest_can_submit_an_inquiry(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Agent->value);
        $property = Property::factory()->approved()->create(['user_id' => $owner->id]);

        Livewire::test(InquiryForm::class, ['property' => $property])
            ->set('name', 'Jane Buyer')
            ->set('email', 'jane@example.com')
            ->set('phone', '555-1234')
            ->set('message', 'I am very interested in this property.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true);

        $this->assertDatabaseHas('property_inquiries', [
            'property_id' => $property->id,
            'user_id' => null,
            'name' => 'Jane Buyer',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_an_authenticated_users_details_are_recorded_on_the_inquiry(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Agent->value);
        $property = Property::factory()->approved()->create(['user_id' => $owner->id]);

        $buyer = User::factory()->create(['name' => 'Logged In Buyer', 'email' => 'buyer@example.com']);
        $buyer->assignRole(RoleName::User->value);

        Livewire::actingAs($buyer)
            ->test(InquiryForm::class, ['property' => $property])
            ->assertSet('name', 'Logged In Buyer')
            ->assertSet('email', 'buyer@example.com')
            ->set('message', 'Please contact me about this listing.')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('property_inquiries', [
            'property_id' => $property->id,
            'user_id' => $buyer->id,
        ]);
    }

    public function test_message_and_email_are_required(): void
    {
        $property = Property::factory()->approved()->create();

        Livewire::test(InquiryForm::class, ['property' => $property])
            ->set('name', 'Jane Buyer')
            ->set('email', 'not-an-email')
            ->set('message', 'short')
            ->call('send')
            ->assertHasErrors(['email', 'message']);
    }

    public function test_the_owner_and_admins_are_notified(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Agent->value);

        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $unrelatedUser = User::factory()->create();
        $unrelatedUser->assignRole(RoleName::User->value);

        $property = Property::factory()->approved()->create(['user_id' => $owner->id]);

        Livewire::test(InquiryForm::class, ['property' => $property])
            ->set('name', 'Jane Buyer')
            ->set('email', 'jane@example.com')
            ->set('message', 'I am very interested in this property.')
            ->call('send');

        Notification::assertSentTo($owner, NewPropertyInquiry::class);
        Notification::assertSentTo($admin, NewPropertyInquiry::class);
        Notification::assertSentTo($superAdmin, NewPropertyInquiry::class);
        Notification::assertNotSentTo($unrelatedUser, NewPropertyInquiry::class);
    }
}
