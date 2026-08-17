<?php

namespace Tests\Feature\Properties;

use App\Enums\RoleName;
use App\Livewire\Frontend\Properties\PropertyForm;
use App\Models\Property;
use App\Models\PropertyAmenity;
use App\Models\PropertyCategory;
use App\Models\PropertyType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PropertyFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function agent(string $role = 'agent'): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    private function baseFormData(array $overrides = []): array
    {
        $category = PropertyCategory::factory()->create();
        $type = PropertyType::factory()->create();

        return array_merge([
            'title' => 'A Lovely Home',
            'description' => 'Great place to live.',
            'category_id' => (string) $category->id,
            'type_id' => (string) $type->id,
            'price' => '1500',
            'price_type' => 'fixed',
            'address' => '123 Main St',
            'city' => 'Springfield',
        ], $overrides);
    }

    public function test_an_agent_can_create_a_property(): void
    {
        $agent = $this->agent();
        $data = $this->baseFormData();

        Livewire::actingAs($agent)
            ->test(PropertyForm::class)
            ->set('title', $data['title'])
            ->set('description', $data['description'])
            ->set('category_id', $data['category_id'])
            ->set('type_id', $data['type_id'])
            ->set('price', $data['price'])
            ->set('price_type', $data['price_type'])
            ->set('address', $data['address'])
            ->set('city', $data['city'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('agent.listings.index'));

        $this->assertDatabaseHas('properties', [
            'title' => $data['title'],
            'user_id' => $agent->id,
            'status' => 'pending',
            'city' => 'Springfield',
        ]);
    }

    public function test_an_agency_can_also_create_a_property(): void
    {
        $agency = $this->agent('agency');
        $data = $this->baseFormData();

        Livewire::actingAs($agency)
            ->test(PropertyForm::class)
            ->set('title', $data['title'])
            ->set('category_id', $data['category_id'])
            ->set('type_id', $data['type_id'])
            ->set('price', $data['price'])
            ->set('price_type', $data['price_type'])
            ->set('city', $data['city'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('properties', ['title' => $data['title'], 'user_id' => $agency->id]);
    }

    public function test_a_plain_user_cannot_access_the_create_form(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(RoleName::User->value);

        $this->actingAs($user)->get('/agent/listings/create')->assertForbidden();
    }

    public function test_title_category_type_price_and_city_are_required(): void
    {
        Livewire::actingAs($this->agent())
            ->test(PropertyForm::class)
            ->set('title', '')
            ->set('category_id', '')
            ->set('type_id', '')
            ->set('price', '')
            ->set('city', '')
            ->call('save')
            ->assertHasErrors(['title', 'category_id', 'type_id', 'price', 'city']);
    }

    public function test_amenities_and_custom_features_are_saved(): void
    {
        $agent = $this->agent();
        $data = $this->baseFormData();
        $amenities = PropertyAmenity::factory()->count(2)->create();

        Livewire::actingAs($agent)
            ->test(PropertyForm::class)
            ->set('title', $data['title'])
            ->set('category_id', $data['category_id'])
            ->set('type_id', $data['type_id'])
            ->set('price', $data['price'])
            ->set('city', $data['city'])
            ->set('selectedAmenities', $amenities->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->set('customFeatures', [['name' => 'Furnishing', 'value' => 'Fully furnished']])
            ->call('save')
            ->assertHasNoErrors();

        $property = Property::query()->where('title', $data['title'])->firstOrFail();

        $this->assertCount(2, $property->amenities);
        $this->assertDatabaseHas('property_features', [
            'property_id' => $property->id,
            'name' => 'Furnishing',
            'value' => 'Fully furnished',
        ]);
    }

    public function test_setLocation_updates_lat_and_lng(): void
    {
        Livewire::actingAs($this->agent())
            ->test(PropertyForm::class)
            ->call('setLocation', 40.7128, -74.0060)
            ->assertSet('lat', 40.7128)
            ->assertSet('lng', -74.0060);
    }

    public function test_editing_own_property_resets_status_to_pending(): void
    {
        $agent = $this->agent();
        $property = Property::factory()->approved()->create(['user_id' => $agent->id]);

        Livewire::actingAs($agent)
            ->test(PropertyForm::class, ['property' => $property])
            ->set('title', 'Updated Title')
            ->call('save')
            ->assertHasNoErrors();

        $property->refresh();

        $this->assertEquals('pending', $property->status);
        $this->assertEquals('Updated Title', $property->title);
    }

    public function test_an_agent_cannot_edit_someone_elses_property(): void
    {
        $owner = $this->agent();
        $otherAgent = $this->agent();
        $property = Property::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($otherAgent)
            ->get(route('agent.listings.edit', $property))
            ->assertForbidden();
    }

    public function test_an_admin_can_add_a_property_and_it_is_auto_approved(): void
    {
        $admin = $this->agent(RoleName::Admin->value);
        $data = $this->baseFormData();

        Livewire::actingAs($admin)
            ->test(PropertyForm::class)
            ->set('title', $data['title'])
            ->set('category_id', $data['category_id'])
            ->set('type_id', $data['type_id'])
            ->set('price', $data['price'])
            ->set('city', $data['city'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.properties.index'));

        $this->assertDatabaseHas('properties', [
            'title' => $data['title'],
            'user_id' => $admin->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_editing_a_property_does_not_reset_its_status(): void
    {
        $admin = $this->agent(RoleName::Admin->value);
        $property = Property::factory()->approved()->create();

        Livewire::actingAs($admin)
            ->test(PropertyForm::class, ['property' => $property])
            ->set('title', 'Admin Updated Title')
            ->call('save')
            ->assertHasNoErrors();

        $property->refresh();

        $this->assertEquals('approved', $property->status);
        $this->assertEquals('Admin Updated Title', $property->title);
    }
}
