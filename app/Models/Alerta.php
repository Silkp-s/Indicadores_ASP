<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('alertas')]
#[Fillable([
    'alerta_regla_id',
    'indicadores_id',
    'establecimientos_id',
    'ano',
    'mes',
    'mensaje',
    'brecha_detectada',
    'estado',
    'observacion_gestion',
])]
class Alerta extends Model
{
    /** Valores del enum `estado`. */
    public const ESTADOS = ['Pendiente', 'Revisada', 'Resuelta'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ano' => 'integer',
            'mes' => 'integer',
            'brecha_detectada' => 'decimal:2',
        ];
    }

    public function regla(): BelongsTo
    {
        return $this->belongsTo(AlertaRegla::class, 'alerta_regla_id');
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class, 'indicadores_id');
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'establecimientos_id');
    }

    /**
     * Ordena la bandeja: Crítica > Advertencia > Informativa y, dentro de cada nivel, mayor brecha primero.
     * Usa CASE para funcionar igual en MariaDB y SQLite.
     */
    #[Scope]
    protected function porSeveridad(Builder $query): void
    {
        $query->join('alerta_reglas', 'alerta_reglas.id', '=', 'alertas.alerta_regla_id')
            ->select('alertas.*')
            ->orderByRaw("case alerta_reglas.severidad when 'Crítica' then 1 when 'Advertencia' then 2 else 3 end")
            ->orderByDesc('alertas.brecha_detectada')
            ->orderBy('alertas.id');
    }
}
