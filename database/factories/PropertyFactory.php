<?php

namespace Database\Factories;

use App\Enums\PropertyStatus;
use App\Models\PropertyCategory;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => PropertyCategory::factory(),
            'type_id' => PropertyType::factory(),
            'title' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->paragraphs(3, true),
            'price' => $this->faker->numberBetween(50000, 5000000),
            'price_type' => 'fixed',
            'status' => PropertyStatus::Pending->value,
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'lat' => $this->faker->latitude(),
            'lng' => $this->faker->longitude(),
            'size' => $this->faker->numberBetween(500, 5000),
            'bedrooms' => $this->faker->numberBetween(1, 6),
            'bathrooms' => $this->faker->numberBetween(1, 4),
            'is_featured' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => PropertyStatus::Approved->value]);
    }

    public function pending(): static
    {
        return $this->state(['status' => PropertyStatus::Pending->value]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => PropertyStatus::Rejected->value]);
    }

    public function expired(): static
    {
        return $this->state(['status' => PropertyStatus::Expired->value]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
