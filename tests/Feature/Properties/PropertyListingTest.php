<?php

namespace Tests\Feature\Properties;

use App\Livewire\Frontend\Properties\Listing;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\PropertyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PropertyListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_approved_properties_are_listed(): void
    {
        Property::factory()->approved()->create(['title' => 'Visible Approved']);
        Property::factory()->pending()->create(['title' => 'Hidden Pending']);
        Property::factory()->rejected()->create(['title' => 'Hidden Rejected']);
        Property::factory()->expired()->create(['title' => 'Hidden Expired']);

        Livewire::test(Listing::class)
            ->assertSee('Visible Approved')
            ->assertDontSee('Hidden Pending')
            ->assertDontSee('Hidden Rejected')
            ->assertDontSee('Hidden Expired');
    }

    public function test_filtering_by_category(): void
    {
        $categoryA = PropertyCategory::factory()->create();
        $categoryB = PropertyCategory::factory()->create();

        Property::factory()->approved()->create(['title' => 'In Category A', 'category_id' => $categoryA->id]);
        Property::factory()->approved()->create(['title' => 'In Category B', 'category_id' => $categoryB->id]);

        Livewire::test(Listing::class)
            ->set('category', (string) $categoryA->id)
            ->assertSee('In Category A')
            ->assertDontSee('In Category B');
    }

    public function test_filtering_by_type(): void
    {
        $typeA = PropertyType::factory()->create();
        $typeB = PropertyType::factory()->create();

        Property::factory()->approved()->create(['title' => 'Type A Listing', 'type_id' => $typeA->id]);
        Property::factory()->approved()->create(['title' => 'Type B Listing', 'type_id' => $typeB->id]);

        Livewire::test(Listing::class)
            ->set('type', (string) $typeA->id)
            ->assertSee('Type A Listing')
            ->assertDontSee('Type B Listing');
    }

    public function test_filtering_by_price_range(): void
    {
        Property::factory()->approved()->create(['title' => 'Cheap Place', 'price' => 1000]);
        Property::factory()->approved()->create(['title' => 'Expensive Place', 'price' => 900000]);

        Livewire::test(Listing::class)
            ->set('max_price', 5000)
            ->assertSee('Cheap Place')
            ->assertDontSee('Expensive Place');
    }

    public function test_filtering_by_bedrooms(): void
    {
        Property::factory()->approved()->create(['title' => 'One Bed', 'bedrooms' => 1]);
        Property::factory()->approved()->create(['title' => 'Four Bed', 'bedrooms' => 4]);

        Livewire::test(Listing::class)
            ->set('bedrooms', 3)
            ->assertSee('Four Bed')
            ->assertDontSee('One Bed');
    }

    public function test_filtering_by_city(): void
    {
        Property::factory()->approved()->create(['title' => 'Austin Home', 'city' => 'Austin']);
        Property::factory()->approved()->create(['title' => 'Miami Home', 'city' => 'Miami']);

        Livewire::test(Listing::class)
            ->set('city', 'Austin')
            ->assertSee('Austin Home')
            ->assertDontSee('Miami Home');
    }

    public function test_reset_filters_clears_all_filters(): void
    {
        Livewire::test(Listing::class)
            ->set('city', 'Austin')
            ->set('bedrooms', 3)
            ->call('resetFilters')
            ->assertSet('city', '')
            ->assertSet('bedrooms', null);
    }
}
