<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'title'            => fake()->sentence(3),
            'description'      => fake()->optional()->sentence(),
            'delivery_address' => fake()->address(),
            'phone'            => fake()->phoneNumber(),
            'status'           => 'new',
            'total_price'      => 0,
            'admin_comment'    => null,
        ];
    }
}
