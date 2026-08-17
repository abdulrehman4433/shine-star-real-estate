<?php

namespace Tests\Feature\Properties;

use App\Enums\RoleName;
use App\Livewire\Frontend\Properties\FavoriteButton;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FavoriteButtonTest extends TestCase
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

    public function test_an_authenticated_user_can_favorite_a_property(): void
    {
        $user = $this->user();
        $property = Property::factory()->approved()->create();

        Livewire::actingAs($user)
            ->test(FavoriteButton::class, ['property' => $property])
            ->call('toggle')
            ->assertSet('favorited', true);

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);
    }

    public function test_toggling_twice_unfavorites_it(): void
    {
        $user = $this->user();
        $property = Property::factory()->approved()->create();

        Livewire::actingAs($user)
            ->test(FavoriteButton::class, ['property' => $property])
            ->call('toggle')
            ->call('toggle')
            ->assertSet('favorited', false);

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);
    }

    public function test_the_component_reflects_an_existing_favorite_on_mount(): void
    {
        $user = $this->user();
        $property = Property::factory()->approved()->create();
        $user->favorites()->attach($property->id);

        Livewire::actingAs($user)
            ->test(FavoriteButton::class, ['property' => $property])
            ->assertSet('favorited', true);
    }

    public function test_a_guest_is_redirected_to_login_and_no_favorite_is_created(): void
    {
        $property = Property::factory()->approved()->create();

        Livewire::test(FavoriteButton::class, ['property' => $property])
            ->call('toggle')
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('favorites', ['property_id' => $property->id]);
    }
}
