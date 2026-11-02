<?php

namespace App\Services;

use App\Jobs\ImportarDatasetDeis;
use App\Models\DeisImportacion;
use Illuminate\Support\Facades\Cache;

/**
 * Encola las actualizaciones de los datasets DEIS sin duplicarlas: la usan el botón del Admin,
 * la actualización semanal programada y la carga inicial automática (tarea programada y panel).
 */
class DeisActualizacionService
{
    /**
     * Horas durante las que una importación en proceso impide encolar otra del mismo dataset. Una pendiente lo
     * impide siempre: aún espera su turno y encolar otra solo las acumularía (p. ej. con las tareas en segundo
     * plano detenidas).
     */
    public const HORAS_IMPORTACION_EN_CURSO = 2;

    /** Horas que la carga inicial espera antes de reintentar un dataset cuyo último intento falló. */
    public const HORAS_REINTENTO_CARGA_INICIAL = 6;

    /** Nombre del candado que serializa la verificación y el encolado. */
    private const CANDADO = 'deis-actualizar';

    /**
     * Encola los datasets pedidos, salvo los que ya tienen una importación en curso reciente.
     *
     * Espera el candado los segundos de `deis.actualizacion.espera_candado`; si otra solicitud lo mantiene,
     * lanza `LockTimeoutException`.
     *
     * @param  list<string>  $datasets
     * @return array{encolados: list<string>, omitidos: list<string>}
     */
    public function encolar(array $datasets = DeisImportacion::DATASETS, ?int $userId = null): array
    {
        // El candado evita que dos solicitudes simultáneas pasen ambas la verificación y encolen el mismo dataset dos veces.
        $espera = max(0, (int) config('deis.actualizacion.espera_candado', 5));

        return Cache::lock(self::CANDADO, 10)->block($espera, fn () => $this->encolarSinCandado($datasets, $userId));
    }

    /**
     * Encola la carga inicial de los datasets que aún no tienen datos ni un intento reciente.
     *
     * No espera el candado (se usa al abrir el panel): si otra solicitud lo tiene, no hace nada.
     *
     * @return list<string> Datasets encolados.
     */
    public function cargarFaltantes(): array
    {
        if (array_filter(DeisImportacion::DATASETS, $this->necesitaCargaInicial(...)) === []) {
            return [];
        }

        $encolados = Cache::lock(self::CANDADO, 10)->get(function (): array {
            $faltantes = array_values(array_filter(DeisImportacion::DATASETS, $this->necesitaCargaInicial(...)));

            return $this->encolarSinCandado($faltantes, null)['encolados'];
        });

        return is_array($encolados) ? $encolados : [];
    }

    /**
     * Un dataset necesita carga inicial si nunca se completó una importación, no tiene registros, no tiene
     * una importación en curso y no hubo ningún intento en las últimas horas (para no reintentar sin parar si
     * el DEIS falla).
     */
    public function necesitaCargaInicial(string $dataset): bool
    {
        $modelo = DeisImportService::MODELOS[$dataset];

        return ! DeisImportacion::query()->delDataset($dataset)->completadas()->exists()
            && ! $modelo::query()->exists()
            && ! $this->enCurso($dataset)
            && ! DeisImportacion::query()
                ->delDataset($dataset)
                ->where('created_at', '>=', now()->subHours(self::HORAS_REINTENTO_CARGA_INICIAL))
                ->exists();
    }

    /**
     * Indica si el dataset tiene una importación pendiente (de cualquier antigüedad) o una en proceso
     * solicitada hace poco; una en proceso más antigua se considera interrumpida.
     */
    public function enCurso(string $dataset): bool
    {
        return DeisImportacion::query()
            ->delDataset($dataset)
            ->where(fn ($query) => $query
                ->where('estado', DeisImportacion::ESTADO_PENDIENTE)
                ->orWhere(fn ($query) => $query
                    ->where('estado', DeisImportacion::ESTADO_PROCESANDO)
                    ->where('created_at', '>=', now()->subHours(self::HORAS_IMPORTACION_EN_CURSO))))
            ->exists();
    }

    /**
     * @param  list<string>  $datasets
     * @return array{encolados: list<string>, omitidos: list<string>}
     */
    private function encolarSinCandado(array $datasets, ?int $userId): array
    {
        $encolados = [];
        $omitidos = [];

        foreach (array_values(array_intersect(DeisImportacion::DATASETS, $datasets)) as $dataset) {
            if ($this->enCurso($dataset)) {
                $omitidos[] = $dataset;

                continue;
            }

            $importacion = DeisImportacion::create([
                'dataset' => $dataset,
                'origen' => DeisImportService::origenConfigurado($dataset),
                'estado' => DeisImportacion::ESTADO_PENDIENTE,
                'user_id' => $userId,
            ]);

            ImportarDatasetDeis::dispatch($dataset, $importacion->id);

            $encolados[] = $dataset;
        }

        return ['encolados' => $encolados, 'omitidos' => $omitidos];
    }
}
