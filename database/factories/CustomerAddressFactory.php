<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => fake()->randomElement(['Rumah', 'Kantor', 'Kos']),
            'recipient_name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'full_address' => fake()->streetAddress(),
            'province_id' => null,
            'city_id' => null,
            'subdistrict_id' => null,
            'postal_code' => fake()->numerify('#####'),
            'is_primary' => false,
        ];
    }

    /**
     * Indicate that the address is the customer's primary address.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
