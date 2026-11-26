<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Parámetros del sistema configurables (clave-valor).
 */
#[Table('configuraciones')]
#[Fillable(['clave', 'valor'])]
class Configuracion extends Model
{
    /** Umbral crítico por defecto (% de la meta bajo el cual el indicador es Crítico). */
    public const UMBRAL_CRITICO_DEFECTO = 85;

    /** Obtiene el valor de un parámetro, o el valor por defecto si no existe. */
    public static function obtener(string $clave, mixed $default = null): mixed
    {
        try {
            return static::query()->where('clave', $clave)->value('valor') ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /** Guarda (o actualiza) un parámetro. */
    public static function guardar(string $clave, mixed $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => (string) $valor]);
    }

    /** Umbral crítico (% de la meta) usado por el semáforo de cumplimiento. */
    public static function umbralCritico(): float
    {
        return (float) static::obtener('umbral_critico', self::UMBRAL_CRITICO_DEFECTO);
    }

    /** Año de trabajo por defecto del sistema. */
    public static function anoTrabajo(): int
    {
        return (int) static::obtener('ano_trabajo', (int) date('Y'));
    }
}
