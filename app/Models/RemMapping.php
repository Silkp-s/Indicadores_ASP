<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('rem_mappings')]
#[Fillable([
    'indicadores_id',
    'hoja',
    'celda_numerador',
    'celda_denominador',
    'seccion',
    'descripcion',
    'is_active',
])]
class RemMapping extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Indicador vinculado a este mapeo de celda REM.
     */
    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class, 'indicadores_id');
    }
}
