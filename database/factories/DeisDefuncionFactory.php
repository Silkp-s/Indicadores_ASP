<?php

namespace Database\Factories;

use App\Models\DeisDefuncion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<DeisDefuncion>
 */
class DeisDefuncionFactory extends Factory
{
    /**
     * Capítulos CIE-10 frecuentes tal como los publica el DEIS: [código, glosa].
     *
     * @var list<array{0: string, 1: string}>
     */
    public const CAPITULOS = [
        ['C00-D48', 'Tumores [Neoplasias]'],
        ['I00-I99', 'Enfermedades del sistema circulatorio'],
        ['J00-J99', 'Enfermedades del sistema respiratorio'],
        ['K00-K93', 'Enfermedades del sistema digestivo'],
        ['S00-T98', 'Traumatismos, envenenamientos y algunas otras consecuencias de causas externas'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$mes, $sexo, $grupoEdad, [$capituloCodigo, $capituloGlosa]] = fake()->unique()->randomElement(
            Arr::crossJoin(range(1, 12), ['Mujer', 'Hombre'], DeisDefuncion::GRUPOS_EDAD, self::CAPITULOS),
        );

        return [
            'anio' => 2025,
            'mes' => $mes,
            'sexo' => $sexo,
            'grupo_edad' => $grupoEdad,
            'capitulo_codigo' => $capituloCodigo,
            'capitulo_glosa' => $capituloGlosa,
            'total' => fake()->numberBetween(1, 20),
        ];
    }
}
