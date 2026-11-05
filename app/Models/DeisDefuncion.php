<?php

namespace App\Models;

use Database\Factories\DeisDefuncionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Defunciones DEIS de la comuna, agregadas por mes, sexo, grupo de edad y capítulo CIE-10.
 */
#[Table('deis_defunciones')]
#[Fillable(['anio', 'mes', 'sexo', 'grupo_edad', 'capitulo_codigo', 'capitulo_glosa', 'total'])]
class DeisDefuncion extends Model
{
    /** @use HasFactory<DeisDefuncionFactory> */
    use HasFactory;

    /** Grupos de edad en su orden natural. */
    public const GRUPOS_EDAD = ['Menor de 1 año', '1 a 14', '15 a 44', '45 a 64', '65 a 79', '80 y más'];

    /** Grupos de edad que suman el indicador "65 años y más". */
    public const GRUPOS_65_Y_MAS = ['65 a 79', '80 y más'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'mes' => 'integer',
            'total' => 'integer',
        ];
    }
}
