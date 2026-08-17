<?php

namespace Tests\Feature\Favorites;

use App\Enums\RoleName;
use App\Livewire\Frontend\Favorites\MyFavorites;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyFavoritesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function user(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(RoleName::User->value);

        return $user;
    }

    public function test_a_user_only_sees_their_own_favorites(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        $mine = Property::factory()->approved()->create(['title' => 'My Saved Property']);
        $theirs = Property::factory()->approved()->create(['title' => 'Their Saved Property']);

        $user->favorites()->attach($mine->id);
        $otherUser->favorites()->attach($theirs->id);

        Livewire::actingAs($user)
            ->test(MyFavorites::class)
            ->assertSee('My Saved Property')
            ->assertDontSee('Their Saved Property');
    }

    public function test_a_user_can_remove_a_favorite_from_the_list(): void
    {
        $user = $this->user();
        $property = Property::factory()->approved()->create();
        $user->favorites()->attach($property->id);

        Livewire::actingAs($user)
            ->test(MyFavorites::class)
            ->call('unfavorite', $property->id);

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
    }
}
