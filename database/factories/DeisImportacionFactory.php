<?php

namespace Database\Factories;

use App\Models\DeisImportacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeisImportacion>
 */
class DeisImportacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dataset = fake()->randomElement(DeisImportacion::DATASETS);
        $iniciadoEn = now()->subMinutes(fake()->numberBetween(10, 600));

        return [
            'dataset' => $dataset,
            'origen' => config("deis.datasets.{$dataset}.url"),
            'estado' => DeisImportacion::ESTADO_COMPLETADO,
            'filas_leidas' => fake()->numberBetween(100000, 9000000),
            'filas_comuna' => fake()->numberBetween(1000, 80000),
            'registros_guardados' => fake()->numberBetween(100, 12000),
            'mensaje' => 'Importación completada.',
            'user_id' => null,
            'iniciado_en' => $iniciadoEn,
            'finalizado_en' => $iniciadoEn->copy()->addMinutes(2),
        ];
    }

    /**
     * Importación de un dataset específico.
     */
    public function delDataset(string $dataset): static
    {
        return $this->state(fn (array $attributes) => [
            'dataset' => $dataset,
            'origen' => config("deis.datasets.{$dataset}.url"),
        ]);
    }

    /**
     * Importación en cola, aún no iniciada.
     */
    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => DeisImportacion::ESTADO_PENDIENTE,
            'filas_leidas' => 0,
            'filas_comuna' => 0,
            'registros_guardados' => 0,
            'mensaje' => null,
            'iniciado_en' => null,
            'finalizado_en' => null,
        ]);
    }

    /**
     * Importación en ejecución.
     */
    public function procesando(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => DeisImportacion::ESTADO_PROCESANDO,
            'filas_leidas' => 0,
            'filas_comuna' => 0,
            'registros_guardados' => 0,
            'mensaje' => null,
            'finalizado_en' => null,
        ]);
    }

    /**
     * Importación terminada con error.
     */
    public function fallido(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => DeisImportacion::ESTADO_FALLIDO,
            'filas_leidas' => 0,
            'filas_comuna' => 0,
            'registros_guardados' => 0,
            'mensaje' => 'La descarga falló (HTTP 404).',
        ]);
    }
}
