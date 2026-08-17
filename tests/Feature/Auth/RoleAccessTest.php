<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(RoleName $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role->value);

        return $user;
    }

    public function test_a_plain_user_is_blocked_from_the_admin_area(): void
    {
        $user = $this->userWithRole(RoleName::User);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_a_plain_user_is_blocked_from_the_agent_area(): void
    {
        $user = $this->userWithRole(RoleName::User);

        $this->actingAs($user)->get('/agent')->assertForbidden();
    }

    public function test_an_agent_is_blocked_from_the_admin_area(): void
    {
        $agent = $this->userWithRole(RoleName::Agent);

        $this->actingAs($agent)->get('/admin')->assertForbidden();
    }

    public function test_an_admin_can_access_the_admin_area(): void
    {
        $admin = $this->userWithRole(RoleName::Admin);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_a_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/agent')->assertRedirect(route('login'));
        $this->get('/account')->assertRedirect(route('login'));
    }

    /** Every route under /admin — none of them may ever be reachable by a self-registered guest. */
    public static function adminRouteProvider(): array
    {
        return [
            'dashboard' => ['/admin'],
            'properties list' => ['/admin/properties'],
            'properties create' => ['/admin/properties/create'],
            'projects list' => ['/admin/projects'],
            'projects create' => ['/admin/projects/create'],
            'leads list' => ['/admin/leads'],
            'chat list' => ['/admin/chat'],
            'pages list' => ['/admin/pages'],
            'menus list' => ['/admin/menus'],
            'headers list' => ['/admin/headers'],
            'footer settings' => ['/admin/footer'],
            'cdn list' => ['/admin/cdn'],
            'reviews list' => ['/admin/reviews'],
            'settings' => ['/admin/settings'],
            'seo settings' => ['/admin/settings/seo'],
            'blog posts' => ['/admin/blog/posts'],
            'blog post create' => ['/admin/blog/posts/create'],
        ];
    }

    #[DataProvider('adminRouteProvider')]
    public function test_a_guest_user_is_blocked_from_every_admin_route(string $url): void
    {
        $guest = $this->userWithRole(RoleName::Guest);

        $this->actingAs($guest)->get($url)->assertForbidden();
    }

    public function test_a_guest_user_is_blocked_from_agent_and_account_areas(): void
    {
        $guest = $this->userWithRole(RoleName::Guest);

        $this->actingAs($guest)->get('/agent')->assertForbidden();
        $this->actingAs($guest)->get('/account')->assertForbidden();
    }
}
