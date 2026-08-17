<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Livewire\Admin\Properties\Manager;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PropertyManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(RoleName::Admin->value);

        return $user;
    }

    public function test_a_non_admin_cannot_view_the_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(RoleName::User->value);

        $this->actingAs($user)->get('/admin/properties')->assertForbidden();
    }

    public function test_an_admin_can_approve_a_pending_property(): void
    {
        $property = Property::factory()->pending()->create();

        Livewire::actingAs($this->admin())
            ->test(Manager::class)
            ->call('approve', $property->id);

        $this->assertDatabaseHas('properties', ['id' => $property->id, 'status' => 'approved']);
    }

    public function test_an_admin_can_reject_a_property_with_a_reason(): void
    {
        $property = Property::factory()->pending()->create();

        Livewire::actingAs($this->admin())
            ->test(Manager::class)
            ->call('startReject', $property->id)
            ->set('rejectionReason', 'Missing required photos.')
            ->call('confirmReject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'rejected',
            'rejection_reason' => 'Missing required photos.',
        ]);
    }

    public function test_rejection_reason_is_required(): void
    {
        $property = Property::factory()->pending()->create();

        Livewire::actingAs($this->admin())
            ->test(Manager::class)
            ->call('startReject', $property->id)
            ->set('rejectionReason', '')
            ->call('confirmReject')
            ->assertHasErrors(['rejectionReason' => 'required']);
    }

    public function test_an_admin_can_toggle_featured(): void
    {
        $property = Property::factory()->approved()->create(['is_featured' => false]);

        Livewire::actingAs($this->admin())
            ->test(Manager::class)
            ->call('toggleFeatured', $property->id);

        $this->assertDatabaseHas('properties', ['id' => $property->id, 'is_featured' => true]);
    }

    public function test_an_admin_can_expire_a_property(): void
    {
        $property = Property::factory()->approved()->create();

        Livewire::actingAs($this->admin())
            ->test(Manager::class)
            ->call('expireNow', $property->id);

        $this->assertDatabaseHas('properties', ['id' => $property->id, 'status' => 'expired']);
    }

    public function test_status_filter_narrows_results(): void
    {
        Property::factory()->approved()->create(['title' => 'Approved One']);
        Property::factory()->pending()->create(['title' => 'Pending One']);

        Livewire::actingAs($this->admin())
            ->test(Manager::class)
            ->set('statusFilter', 'pending')
            ->assertSee('Pending One')
            ->assertDontSee('Approved One');
    }
}
