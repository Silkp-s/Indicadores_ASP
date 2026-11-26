<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('alerta_reglas')]
#[Fillable(['codigo', 'nombre', 'descripcion', 'severidad', 'is_active', 'parametros'])]
class AlertaRegla extends Model
{
    /** Valores del enum `severidad`, de mayor a menor. */
    public const SEVERIDADES = ['Crítica', 'Advertencia', 'Informativa'];

    /**
     * Parámetros configurables de cada regla: clave => etiqueta legible para la interfaz.
     *
     * @var array<string, array<string, string>>
     */
    public const PARAMETROS = [
        'REG-01' => [],
        'REG-02' => ['umbral_cercano' => 'Umbral de cercanía (%)'],
        'REG-03' => ['meses' => 'Meses consecutivos a la baja'],
        'REG-04' => [],
        'REG-05' => ['min_puntos' => 'Meses mínimos de historia'],
        'REG-06' => ['caida' => 'Caída mínima (puntos)'],
        'REG-07' => ['umbral_cercano' => 'Umbral de cercanía (%)'],
        'REG-08' => [],
        'REG-09' => [],
        'REG-10' => ['criticos' => 'Cantidad de indicadores críticos'],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'parametros' => 'array',
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
