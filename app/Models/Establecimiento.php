<?php

namespace App\Models;

use Database\Factories\EstablecimientoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('establecimientos')]
#[Fillable(['nombre', 'codigo_deis', 'tipo', 'poblacion_inscrita', 'is_active'])]
class Establecimiento extends Model
{
    /** @use HasFactory<EstablecimientoFactory> */
    use HasFactory;

    /** Valores del enum `tipo` en la base de datos */
    public const TIPOS = ['CESFAM', 'CECOSF', 'SAR', 'SAPU', 'DAS'];

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
     * Aplica la búsqueda por nombre o código DEIS y los filtros de tipo y estado.
     *
     * @param  array{q: string, tipo: ?string, estado: 'activo'|'inactivo'|'todos'}  $filtros
     */
    #[Scope]
    protected function filtrar(Builder $query, array $filtros): void
    {
        $query
            ->when(($filtros['q'] ?? '') !== '', function (Builder $query) use ($filtros): void {
                $texto = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filtros['q']).'%';

                $query->where(fn (Builder $query) => $query
                    ->whereRaw("nombre like ? escape '!'", [$texto])
                    ->orWhereRaw("codigo_deis like ? escape '!'", [$texto]));
            })
            ->when(($filtros['tipo'] ?? null) && in_array($filtros['tipo'], self::TIPOS, true), function (Builder $query) use ($filtros): void {
                $query->where('tipo', $filtros['tipo']);
            })
            ->when(($filtros['estado'] ?? 'todos') !== 'todos', function (Builder $query) use ($filtros): void {
                $query->where('is_active', $filtros['estado'] === 'activo');
            });
    }

    /**
     * Mediciones asociadas al establecimiento.
     */
    public function medidas(): HasMany
    {
        return $this->hasMany(IndicadorMedida::class, 'establecimientos_id');
    }
}
