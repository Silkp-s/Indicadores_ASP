<?php

namespace App\Models;

use Database\Factories\DeisImportacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de importaciones de datasets DEIS.
 */
#[Table('deis_importaciones')]
#[Fillable([
    'dataset',
    'origen',
    'estado',
    'filas_leidas',
    'filas_comuna',
    'registros_guardados',
    'mensaje',
    'user_id',
    'iniciado_en',
    'finalizado_en',
])]
class DeisImportacion extends Model
{
    /** @use HasFactory<DeisImportacionFactory> */
    use HasFactory;

    /** Datasets DEIS soportados. */
    public const DATASETS = ['urgencias', 'defunciones', 'egresos'];

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PROCESANDO = 'procesando';

    public const ESTADO_COMPLETADO = 'completado';

    public const ESTADO_FALLIDO = 'fallido';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_PROCESANDO,
        self::ESTADO_COMPLETADO,
        self::ESTADO_FALLIDO,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filas_leidas' => 'integer',
            'filas_comuna' => 'integer',
            'registros_guardados' => 'integer',
            'iniciado_en' => 'datetime',
            'finalizado_en' => 'datetime',
        ];
    }

    /**
     * Usuario que solicitó la importación (nulo si se ejecutó por consola).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Limita la consulta a un dataset.
     */
    #[Scope]
    protected function delDataset(Builder $query, string $dataset): void
    {
        $query->where('dataset', $dataset);
    }

    /**
     * Limita la consulta a las importaciones completadas.
     */
    #[Scope]
    protected function completadas(Builder $query): void
    {
        $query->where('estado', self::ESTADO_COMPLETADO);
    }

    /**
     * Limita la consulta a las importaciones pendientes o en proceso.
     */
    #[Scope]
    protected function enCurso(Builder $query): void
    {
        $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_PROCESANDO]);
    }

    /**
     * Última importación completada del dataset, o null si nunca se ha importado.
     */
    public static function ultimaCompletada(string $dataset): ?self
    {
        return static::query()
            ->delDataset($dataset)
            ->completadas()
            ->latest('finalizado_en')
            ->latest('id')
            ->first();
    }
}
