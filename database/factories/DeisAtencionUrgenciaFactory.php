<?php

namespace Database\Factories;

use App\Models\DeisAtencionUrgencia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<DeisAtencionUrgencia>
 */
class DeisAtencionUrgenciaFactory extends Factory
{
    /**
     * Establecimientos de urgencia DEIS de Talcahuano: código => [nombre, tipo].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const ESTABLECIMIENTOS = [
        '19-802' => ['SAR San Vicente', 'SAR'],
        '201018' => ['SAR Los Cerros', 'SAR'],
        '19-803' => ['SAPU Paulina Avendaño Pereda', 'SAPU'],
        '19-807' => ['SAPU Alcalde Leocán Portus', 'SAPU'],
        '19-100' => ['Hospital Las Higueras (Talcahuano)', 'Hospital'],
    ];

    /**
     * Glosas DEIS de las causas más usadas.
     *
     * @var array<int, string>
     */
    public const CAUSAS = [
        1 => 'SECCIÓN 1. TOTAL ATENCIONES DE URGENCIA',
        2 => 'TOTAL CAUSAS SISTEMA RESPIRATORIO',
        12 => 'TOTAL CAUSAS SISTEMA CIRCULATORIO',
        18 => 'TOTAL TRAUMATISMOS Y ENVENENAMIENTO',
        34 => 'TOTAL DEMANDA',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$codigo, $semana] = fake()->unique()->randomElement(Arr::crossJoin(array_keys(self::ESTABLECIMIENTOS), range(1, 53)));
        $total = fake()->numberBetween(100, 2000);
        $menores1 = intdiv($total, 20);
        $de1a4 = intdiv($total, 10);
        $de5a14 = intdiv($total, 8);
        $de65YMas = intdiv($total, 6);

        return [
            'codigo_establecimiento' => (string) $codigo,
            'nombre_establecimiento' => self::ESTABLECIMIENTOS[$codigo][0],
            'tipo_establecimiento' => self::ESTABLECIMIENTOS[$codigo][1],
            'id_causa' => DeisAtencionUrgencia::CAUSA_TOTAL_ATENCIONES,
            'glosa_causa' => self::CAUSAS[DeisAtencionUrgencia::CAUSA_TOTAL_ATENCIONES],
            'anio' => 2025,
            'semana' => $semana,
            'total' => $total,
            'menores_1' => $menores1,
            'de_1_a_4' => $de1a4,
            'de_5_a_14' => $de5a14,
            'de_15_a_64' => $total - $menores1 - $de1a4 - $de5a14 - $de65YMas,
            'de_65_y_mas' => $de65YMas,
        ];
    }

    /**
     * Atenciones de uno de los establecimientos DEIS de Talcahuano.
     */
    public function establecimiento(string $codigo): static
    {
        return $this->state(fn (array $attributes) => [
            'codigo_establecimiento' => $codigo,
            'nombre_establecimiento' => self::ESTABLECIMIENTOS[$codigo][0] ?? "Establecimiento {$codigo}",
            'tipo_establecimiento' => self::ESTABLECIMIENTOS[$codigo][1] ?? 'SAPU',
        ]);
    }

    /**
     * Atenciones de una causa DEIS (IdCausa).
     */
    public function causa(int $idCausa): static
    {
        return $this->state(fn (array $attributes) => [
            'id_causa' => $idCausa,
            'glosa_causa' => self::CAUSAS[$idCausa] ?? "CAUSA {$idCausa}",
        ]);
    }
}
