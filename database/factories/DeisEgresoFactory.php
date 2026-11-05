<?php

namespace Database\Factories;

use App\Models\DeisEgreso;
use App\Services\DeisImportService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<DeisEgreso>
 */
class DeisEgresoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $capitulos = Arr::only(DeisImportService::CAPITULOS_CIE10, [1, 8, 9, 10, 14]);

        [$pertenencia, $sexo, $grupoEdad, [$desde, $hasta, $glosa], $condicion] = fake()->unique()->randomElement(Arr::crossJoin(
            [DeisEgreso::PERTENENCIA_PUBLICO, DeisEgreso::PERTENENCIA_PRIVADO],
            ['Mujer', 'Hombre'],
            DeisEgreso::GRUPOS_EDAD,
            array_values($capitulos),
            [DeisEgreso::CONDICION_VIVO, DeisEgreso::CONDICION_FALLECIDO],
        ));
        $egresos = fake()->numberBetween(1, 40);

        return [
            'anio' => 2024,
            'pertenencia' => $pertenencia,
            'sexo' => $sexo,
            'grupo_edad' => $grupoEdad,
            'capitulo_codigo' => "{$desde}-{$hasta}",
            'capitulo_glosa' => $glosa,
            'condicion' => $condicion,
            'egresos' => $egresos,
            'dias_estada' => $egresos * fake()->numberBetween(1, 10),
        ];
    }
}
