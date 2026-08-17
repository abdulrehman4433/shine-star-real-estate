<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public static function roleDashboardProvider(): array
    {
        return [
            'super-admin lands on admin dashboard' => [RoleName::SuperAdmin, 'admin.dashboard'],
            'admin lands on admin dashboard' => [RoleName::Admin, 'admin.dashboard'],
            'agent lands on agent dashboard' => [RoleName::Agent, 'agent.dashboard'],
            'agency lands on agent dashboard' => [RoleName::Agency, 'agent.dashboard'],
            'user lands on account dashboard' => [RoleName::User, 'account.dashboard'],
        ];
    }

    #[DataProvider('roleDashboardProvider')]
    public function test_each_role_is_redirected_to_its_own_dashboard_after_login(RoleName $role, string $expectedRoute): void
    {
        $user = User::factory()->create([
            'email' => "{$role->value}@test.local",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role->value);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route($expectedRoute));
        $this->assertAuthenticatedAs($user);
    }
}
