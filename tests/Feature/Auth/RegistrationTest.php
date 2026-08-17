<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_visitor_can_register_as_a_plain_user(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '555-1234',
            'role' => RoleName::User->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('user'));
        $response->assertRedirect(route('account.dashboard'));
    }

    public function test_agency_registration_requires_an_agency_name(): void
    {
        $response = $this->post('/register', [
            'name' => 'Agency Owner',
            'email' => 'owner@example.com',
            'role' => RoleName::Agency->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('agency_name');
        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }

    public function test_a_visitor_cannot_self_register_as_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sneaky Admin',
            'email' => 'sneaky@example.com',
            'role' => 'admin',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }
}
