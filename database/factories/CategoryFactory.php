<?php

namespace Database\Factories;

use App\CategoryType;
use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->unique()->word(),
            'type' => CategoryType::Expense,
            'icon' => 'tag',
            'color' => '#3F67C7',
            'is_archived' => false,
        ];
    }
}
