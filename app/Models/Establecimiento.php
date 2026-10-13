<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('establecimientos')]
#[Fillable(['nombre', 'codigo_deis', 'tipo', 'poblacion_inscrita', 'is_active'])]
class Establecimiento extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'poblacion_inscrita' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mediciones asociadas al establecimiento.
     */
    public function medidas(): HasMany
    {
        return $this->hasMany(IndicadorMedida::class, 'establecimientos_id');
    }
}
