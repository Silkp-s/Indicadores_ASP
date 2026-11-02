<?php

namespace App\Console\Commands;

use App\Services\AlertaEngine;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('alertas:evaluar {--ano= : Año a evaluar (por defecto, el actual)} {--mes= : Mes 1-12 a evaluar (por defecto, el actual)}')]
#[Description('Ejecuta el Motor de Alertas sobre las mediciones de un período')]
class EvaluarAlertas extends Command
{
    public function handle(AlertaEngine $motor): int
    {
        $ano = (int) ($this->option('ano') ?: now()->year);
        $mes = (int) ($this->option('mes') ?: now()->month);

        if ($mes < 1 || $mes > 12) {
            $this->error('El mes debe estar entre 1 y 12.');

            return self::FAILURE;
        }

        $resultado = $motor->evaluar($ano, $mes);

        $this->info("Período {$ano}-".sprintf('%02d', $mes).": {$resultado['detectadas']} alerta(s) detectada(s), {$resultado['retiradas']} pendiente(s) retirada(s).");

        return self::SUCCESS;
    }
}
