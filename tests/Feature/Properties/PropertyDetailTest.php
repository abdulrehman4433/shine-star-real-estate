<?php

namespace Tests\Feature\Properties;

use App\Enums\RoleName;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_guest_can_view_an_approved_property(): void
    {
        $property = Property::factory()->approved()->create(['title' => 'Public Listing']);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('Public Listing');
    }

    public function test_a_guest_cannot_view_a_pending_property(): void
    {
        $property = Property::factory()->pending()->create();

        $this->get(route('properties.show', $property))->assertForbidden();
    }

    public function test_the_owner_can_view_their_own_pending_property(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $owner->assignRole(RoleName::Agent->value);
        $property = Property::factory()->pending()->create(['user_id' => $owner->id, 'title' => 'My Pending Listing']);

        $this->actingAs($owner)
            ->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('My Pending Listing');
    }

    public function test_an_admin_can_view_a_pending_property(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(RoleName::Admin->value);
        $property = Property::factory()->pending()->create(['title' => 'Needs Review']);

        $this->actingAs($admin)
            ->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('Needs Review');
    }

    public function test_another_agent_cannot_view_a_pending_property(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $owner->assignRole(RoleName::Agent->value);
        $otherAgent = User::factory()->create(['email_verified_at' => now()]);
        $otherAgent->assignRole(RoleName::Agent->value);

        $property = Property::factory()->pending()->create(['user_id' => $owner->id]);

        $this->actingAs($otherAgent)
            ->get(route('properties.show', $property))
            ->assertForbidden();
    }
}
