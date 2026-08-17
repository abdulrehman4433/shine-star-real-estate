<?php

namespace Tests\Feature\Properties;

use App\Enums\RoleName;
use App\Livewire\Frontend\Properties\MyListings;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyListingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function agent(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(RoleName::Agent->value);

        return $user;
    }

    public function test_an_agent_only_sees_their_own_listings(): void
    {
        $agent = $this->agent();
        $otherAgent = $this->agent();

        Property::factory()->create(['user_id' => $agent->id, 'title' => 'Mine']);
        Property::factory()->create(['user_id' => $otherAgent->id, 'title' => 'Not Mine']);

        Livewire::actingAs($agent)
            ->test(MyListings::class)
            ->assertSee('Mine')
            ->assertDontSee('Not Mine');
    }

    public function test_an_agent_can_delete_their_own_listing(): void
    {
        $agent = $this->agent();
        $property = Property::factory()->create(['user_id' => $agent->id]);

        Livewire::actingAs($agent)
            ->test(MyListings::class)
            ->call('delete', $property->id);

        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
    }

    public function test_an_agent_cannot_delete_someone_elses_listing(): void
    {
        $agent = $this->agent();
        $otherAgent = $this->agent();
        $property = Property::factory()->create(['user_id' => $otherAgent->id]);

        Livewire::actingAs($agent)
            ->test(MyListings::class)
            ->call('delete', $property->id)
            ->assertForbidden();

        $this->assertDatabaseHas('properties', ['id' => $property->id]);
    }
}
