<?php

namespace App\Jobs;

use App\Exceptions\DeisImportacionException;
use App\Models\DeisImportacion;
use App\Services\DeisImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Descarga e importa un dataset DEIS desde la URL configurada, sobre una importación pendiente de la bitácora.
 */
class ImportarDatasetDeis implements ShouldQueue
{
    use Queueable;

    /** Texto de la bitácora cuando se descarta una solicitud porque ya hay otra más reciente del mismo dataset. */
    public const MENSAJE_OMITIDA = 'Se omitió porque hubo una actualización más reciente.';

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

        // Ya la procesó o la cerró otra ejecución: no se toca.
        if ($importacion === null || $importacion->estado !== DeisImportacion::ESTADO_PENDIENTE) {
            return;
        }

        // Si se acumularon solicitudes (p. ej. con las tareas en segundo plano detenidas), solo se procesa la más reciente.
        if ($this->tieneUnaMasReciente($importacion)) {
            Log::info("Se omitió la importación DEIS de «{$this->dataset}» porque hay una más reciente.", [
                'importacion_id' => $importacion->id,
            ]);

            $importacion->update([
                'estado' => DeisImportacion::ESTADO_FALLIDO,
                'mensaje' => self::MENSAJE_OMITIDA,
                'finalizado_en' => now(),
            ]);

            return;
        }

        $servicio->importar($this->dataset, importacion: $importacion);
    }

    /**
     * Indica si el dataset tiene una importación solicitada después (id mayor) o una completada después de esta.
     */
    private function tieneUnaMasReciente(DeisImportacion $importacion): bool
    {
        return DeisImportacion::query()
            ->delDataset($importacion->dataset)
            ->whereKeyNot($importacion->id)
            ->where(fn ($query) => $query
                ->where('id', '>', $importacion->id)
                ->orWhere(fn ($query) => $query
                    ->where('estado', DeisImportacion::ESTADO_COMPLETADO)
                    ->where('created_at', '>', $importacion->created_at)))
            ->exists();
    }

    /**
     * Marca como fallida la importación si el job terminó sin que el servicio la cerrara (p. ej. por tiempo límite).
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("La tarea de importación DEIS de «{$this->dataset}» terminó con error: ".($exception?->getMessage() ?: 'sin detalle'), [
            'importacion_id' => $this->importacionId,
        ]);

        DeisImportacion::query()
            ->whereKey($this->importacionId)
            ->enCurso()
            ->update([
                'estado' => DeisImportacion::ESTADO_FALLIDO,
                'mensaje' => 'La actualización no alcanzó a terminar. '.DeisImportacionException::REINTENTO,
                'finalizado_en' => now(),
            ]);
    }
}
