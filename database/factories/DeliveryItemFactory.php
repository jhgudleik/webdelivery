<?php

namespace Database\Factories;

use App\Models\Delivery;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'delivery_id'  => Delivery::factory(),
            'product_id'   => fake()->optional()->randomNumber(3),
            'product_title'=> fake()->words(3, true),
            'product_sku'  => fake()->optional()->bothify('SKU-####'),
            'quantity'     => fake()->numberBetween(1, 5),
            'price'        => fake()->randomFloat(2, 100, 50000),
        ];
    }
}
