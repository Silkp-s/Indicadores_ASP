<?php

namespace App\Models;

use Database\Factories\IndicadorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('indicadores', timestamps: false)]
#[Fillable(['codigo', 'nombre', 'tipo', 'numerador_description', 'denominador_description', 'target_value', 'periodicidad', 'fu', 'is_active'])]
class Indicador extends Model
{
    /** @use HasFactory<IndicadorFactory> */
    use HasFactory;

    /** Valores del enum `tipo` de la migración. */
    public const TIPOS = ['IAAPS', 'Meta Sanitaria', 'Ministerial'];

    public const FUENTES = ['REM-A', 'REM-P', 'DEIS', 'SIGGES'];

    public const PERIODICIDADES = ['Mensual', 'Trimestral', 'Semestral', 'Anual'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Meta sin ceros de más y con coma decimal (85.00 → "85", 29.50 → "29,5").
     */
    protected function metaFormateada(): Attribute
    {
        return Attribute::get(fn (): string => str_replace('.', ',', rtrim(rtrim((string) $this->target_value, '0'), '.')));
    }

    /**
     * Aplica la búsqueda por código o nombre y los filtros del catálogo.
     *
     * @param  array{q: string, tipo: ?string, fuente: ?string, estado: 'activo'|'inactivo'|'todos'}  $filtros
     */
    #[Scope]
    protected function filtrar(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['q'] !== '', function (Builder $query) use ($filtros): void {
                // "!" como carácter de escape explícito: % y _ se buscan literalmente, igual en MariaDB y SQLite.
                $texto = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filtros['q']).'%';

                $query->where(fn (Builder $query) => $query
                    ->whereRaw("codigo like ? escape '!'", [$texto])
                    ->orWhereRaw("nombre like ? escape '!'", [$texto]));
            })
            ->when($filtros['tipo'], fn (Builder $query, string $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['fuente'], fn (Builder $query, string $fuente) => $query->where('fu', $fuente))
            ->when($filtros['estado'] !== 'todos', fn (Builder $query) => $query->where('is_active', $filtros['estado'] === 'activo'));
    }
}
