<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Establecimiento>
 */
class EstablecimientoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(Establecimiento::TIPOS);

        return [
            'nombre' => $tipo.' '.fake()->unique()->streetName(),
            'codigo_deis' => (string) fake()->unique()->numberBetween(119000, 119999),
            'tipo' => $tipo,
            'poblacion_inscrita' => fake()->numberBetween(2000, 50000),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the establishment is inactive.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
