<?php

namespace Database\Factories;

use App\Models\Indicador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indicador>
 */
class IndicadorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('IND-###'),
            'nombre' => 'Cobertura de '.fake()->words(4, true),
            'tipo' => fake()->randomElement(Indicador::TIPOS),
            'numerador_description' => fake()->sentence(),
            'denominador_description' => fake()->sentence(),
            'target_value' => fake()->numberBetween(10, 95),
            'periodicidad' => fake()->randomElement(Indicador::PERIODICIDADES),
            'fu' => fake()->randomElement(Indicador::FUENTES),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the indicator is inactive.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
