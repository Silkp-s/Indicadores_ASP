<?php

namespace App\Models;

use Database\Factories\DeisEgresoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Egresos hospitalarios DEIS de residentes de la comuna, agregados por sector, sexo, edad, capítulo CIE-10 y condición.
 */
#[Table('deis_egresos')]
#[Fillable([
    'anio',
    'pertenencia',
    'sexo',
    'grupo_edad',
    'capitulo_codigo',
    'capitulo_glosa',
    'condicion',
    'egresos',
    'dias_estada',
])]
class DeisEgreso extends Model
{
    /** @use HasFactory<DeisEgresoFactory> */
    use HasFactory;

    public const PERTENENCIA_PUBLICO = 'Público (SNSS)';

    public const PERTENENCIA_PRIVADO = 'Privado';

    public const CONDICION_VIVO = 'Vivo';

    public const CONDICION_FALLECIDO = 'Fallecido';

    /** Grupos de edad tal como los publica el DEIS, en su orden natural. */
    public const GRUPOS_EDAD = [
        'menor de un año',
        '1 a 9',
        '10 a 19',
        '20 a 29',
        '30 a 39',
        '40 a 49',
        '50 a 59',
        '60 a 69',
        '70 a 79',
        '80 a 89',
        '90 y más',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'egresos' => 'integer',
            'dias_estada' => 'integer',
        ];
    }
}
