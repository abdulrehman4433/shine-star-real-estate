<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Livewire\Frontend\Properties\MyProperties;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyPropertiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function guestUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Guest->value);

        return $user;
    }

    public function test_logged_out_user_is_redirected_to_login_for_the_hub(): void
    {
        $this->get('/my/properties')->assertRedirect(route('login'));
    }

    public function test_guest_can_open_the_properties_hub(): void
    {
        $this->actingAs($this->guestUser())->get('/my/properties')->assertOk();
    }

    public function test_guest_can_open_the_create_property_form(): void
    {
        $this->actingAs($this->guestUser())->get('/my/properties/create')->assertOk();
    }

    public function test_registration_as_guest_creates_a_guest_user(): void
    {
        $this->post('/register', [
            'name' => 'New Guest',
            'email' => 'guest2@example.com',
            'phone' => '555-0000',
            'role' => RoleName::Guest->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('my.properties.index'));

        $user = User::query()->where('email', 'guest2@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('guest'));
        $this->assertSame(route('my.properties.index'), $user->dashboardRoute());
    }

    public function test_registration_without_phone_fails(): void
    {
        $this->post('/register', [
            'name' => 'No Phone',
            'email' => 'nophone@example.com',
            'role' => RoleName::Guest->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('phone');
    }

    public function test_hub_lists_approved_properties_and_own_pending_properties(): void
    {
        $guest = $this->guestUser();
        $other = $this->guestUser();

        $approved = Property::factory()->approved()->create(['user_id' => $other->id]);
        $ownPending = Property::factory()->pending()->create(['user_id' => $guest->id]);
        $otherPending = Property::factory()->pending()->create(['user_id' => $other->id]);

        Livewire::actingAs($guest)->test(MyProperties::class)
            ->assertSee($approved->title)
            ->assertSee($ownPending->title)
            ->assertDontSee($otherPending->title);
    }

    public function test_guest_can_delete_own_property(): void
    {
        $guest = $this->guestUser();
        $own = Property::factory()->create(['user_id' => $guest->id]);

        Livewire::actingAs($guest)->test(MyProperties::class)
            ->call('delete', $own->id);

        $this->assertDatabaseMissing('properties', ['id' => $own->id]);
    }

    public function test_guest_is_blocked_from_admin_and_account_areas(): void
    {
        $guest = $this->guestUser();

        $this->actingAs($guest)->get('/admin')->assertForbidden();
        $this->actingAs($guest)->get('/account')->assertForbidden();
    }

    public function test_guest_cannot_delete_another_users_property(): void
    {
        $guest = $this->guestUser();
        $other = $this->guestUser();
        $theirs = Property::factory()->create(['user_id' => $other->id]);

        Livewire::actingAs($guest)->test(MyProperties::class)
            ->call('delete', $theirs->id)
            ->assertForbidden();

        $this->assertDatabaseHas('properties', ['id' => $theirs->id]);
    }
}
