<?php

namespace Database\Factories;

use App\Enums\BlogPostStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'title' => ucfirst($this->faker->unique()->sentence(4)),
            'excerpt' => $this->faker->sentence(12),
            'content' => '<p>'.implode('</p><p>', $this->faker->paragraphs(3)).'</p>',
            'status' => BlogPostStatus::Draft->value,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => BlogPostStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
    }
}
