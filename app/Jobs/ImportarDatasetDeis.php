<?php

namespace App\Jobs;

use App\Models\DeisImportacion;
use App\Services\DeisImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

/**
 * Descarga e importa un dataset DEIS desde la URL configurada, sobre una importación pendiente de la bitácora.
 */
class ImportarDatasetDeis implements ShouldQueue
{
    use Queueable;

    /**
     * Segundos máximos de ejecución.
     *
     * @var int
     */
    public $timeout = 1800;

    /**
     * Intentos máximos: una importación fallida no se reintenta automáticamente.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $dataset, public int $importacionId) {}

    /**
     * Execute the job.
     */
    public function handle(DeisImportService $servicio): void
    {
        $importacion = DeisImportacion::find($this->importacionId);

        if ($importacion === null) {
            return;
        }

        $servicio->importar($this->dataset, importacion: $importacion);
    }

    /**
     * Marca como fallida la importación si el job terminó sin que el servicio la cerrara (p. ej. por tiempo límite).
     */
    public function failed(?Throwable $exception): void
    {
        DeisImportacion::query()
            ->whereKey($this->importacionId)
            ->enCurso()
            ->update([
                'estado' => DeisImportacion::ESTADO_FALLIDO,
                'mensaje' => Str::limit($exception?->getMessage() ?: 'La tarea de importación terminó inesperadamente.', 2000),
                'finalizado_en' => now(),
            ]);
    }
}
