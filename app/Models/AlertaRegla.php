<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('alerta_reglas')]
#[Fillable(['codigo', 'nombre', 'descripcion', 'severidad', 'is_active'])]
class AlertaRegla extends Model
{
    /** Valores del enum `severidad`, de mayor a menor. */
    public const SEVERIDADES = ['Crítica', 'Advertencia', 'Informativa'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function activas(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'alerta_regla_id');
    }
}
