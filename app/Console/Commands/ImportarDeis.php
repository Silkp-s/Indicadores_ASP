<?php

namespace App\Console\Commands;

use App\Models\DeisImportacion;
use App\Services\DeisImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('deis:importar
    {dataset=todos : urgencias|defunciones|egresos|todos}
    {--archivo= : ZIP/CSV local}
    {--url= : URL alternativa}')]
#[Description('Importa los datasets abiertos del DEIS-MINSAL (urgencias, defunciones y egresos) filtrados a la comuna configurada')]
class ImportarDeis extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DeisImportService $servicio): int
    {
        $dataset = (string) $this->argument('dataset');
        $archivo = $this->option('archivo') ?: null;
        $url = $this->option('url') ?: null;

        if ($dataset !== 'todos' && ! in_array($dataset, DeisImportacion::DATASETS, true)) {
            $this->components->error("Dataset no válido: «{$dataset}». Use urgencias, defunciones, egresos o todos.");

            return self::INVALID;
        }

        if ($dataset === 'todos' && ($archivo !== null || $url !== null)) {
            $this->components->error('Las opciones --archivo y --url solo se pueden usar con un dataset específico.');

            return self::INVALID;
        }

        if ($archivo !== null && $url !== null) {
            $this->components->error('Use --archivo o --url, no ambas a la vez.');

            return self::INVALID;
        }

        $datasets = $dataset === 'todos' ? DeisImportacion::DATASETS : [$dataset];
        $huboErrores = false;

        foreach ($datasets as $actual) {
            $this->components->info('Importando '.mb_strtolower(config("deis.datasets.{$actual}.titulo", $actual)));
            $inicio = microtime(true);

            try {
                $importacion = $servicio->importar($actual, $archivo, $url);
            } catch (Throwable $exception) {
                $huboErrores = true;
                $this->components->error("Falló la importación de {$actual}: {$exception->getMessage()}");

                continue;
            }

            $this->components->twoColumnDetail('Origen', $importacion->origen);
            $this->components->twoColumnDetail('Filas leídas', $this->numero($importacion->filas_leidas));
            $this->components->twoColumnDetail('Filas de '.config('deis.comuna.nombre'), $this->numero($importacion->filas_comuna));
            $this->components->twoColumnDetail('Registros guardados', $this->numero($importacion->registros_guardados));
            $this->components->twoColumnDetail('Duración', number_format(microtime(true) - $inicio, 1, ',', '.').' s');
            $this->components->twoColumnDetail('Memoria máxima', number_format(memory_get_peak_usage(true) / 1048576, 1, ',', '.').' MB');
            $this->newLine();
        }

        return $huboErrores ? self::FAILURE : self::SUCCESS;
    }

    private function numero(int $valor): string
    {
        return number_format($valor, 0, ',', '.');
    }
}
