<?php

namespace App\Models;

use Database\Factories\DeisAtencionUrgenciaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Atenciones de urgencia DEIS de la comuna, agregadas por establecimiento, causa y semana estadística.
 */
#[Table('deis_atenciones_urgencia')]
#[Fillable([
    'codigo_establecimiento',
    'nombre_establecimiento',
    'tipo_establecimiento',
    'id_causa',
    'glosa_causa',
    'anio',
    'semana',
    'total',
    'menores_1',
    'de_1_a_4',
    'de_5_a_14',
    'de_15_a_64',
    'de_65_y_mas',
])]
class DeisAtencionUrgencia extends Model
{
    /** @use HasFactory<DeisAtencionUrgenciaFactory> */
    use HasFactory;

    /** IdCausa DEIS de la fila "SECCIÓN 1. TOTAL ATENCIONES DE URGENCIA". */
    public const CAUSA_TOTAL_ATENCIONES = 1;

    /** IdCausa DEIS del total de causas respiratorias. */
    public const CAUSA_RESPIRATORIA = 2;

    /** IdCausa DEIS de "TOTAL DEMANDA". */
    public const CAUSA_TOTAL_DEMANDA = 34;

    /**
     * Columnas de grupos de edad, en orden, con su etiqueta.
     *
     * @var array<string, string>
     */
    public const GRUPOS_EDAD = [
        'menores_1' => 'Menores de 1 año',
        'de_1_a_4' => '1 a 4 años',
        'de_5_a_14' => '5 a 14 años',
        'de_15_a_64' => '15 a 64 años',
        'de_65_y_mas' => '65 años y más',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id_causa' => 'integer',
            'anio' => 'integer',
            'semana' => 'integer',
            'total' => 'integer',
            'menores_1' => 'integer',
            'de_1_a_4' => 'integer',
            'de_5_a_14' => 'integer',
            'de_15_a_64' => 'integer',
            'de_65_y_mas' => 'integer',
        ];
    }
}
