<?php

namespace Database\Factories;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notificacion>
 */
class NotificacionFactory extends Factory
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
            'tipo' => fake()->randomElement(Notificacion::TIPOS),
            'mensaje' => fake()->sentence(),
            'leida_en' => null,
        ];
    }

    /**
     * Indicate that the notification has already been read.
     */
    public function leida(): static
    {
        return $this->state(fn (array $attributes) => [
            'leida_en' => now(),
        ]);
    }
}
