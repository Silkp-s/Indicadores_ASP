<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Falla de una importación DEIS: el mensaje de la excepción es el detalle técnico (para el log y la consola)
 * y `mensajeUsuario` es el texto simple que se guarda en la bitácora y se muestra en el panel.
 */
class DeisImportacionException extends RuntimeException
{
    /** Texto que se agrega a los avisos de error del panel. */
    public const REINTENTO = 'Se volverá a intentar en la próxima actualización automática.';

    public function __construct(string $detalle, public readonly string $mensajeUsuario, ?Throwable $previous = null)
    {
        parent::__construct($detalle, 0, $previous);
    }

    /**
     * No se pudo descargar el archivo desde el DEIS (sin conexión, archivo no encontrado o respuesta inválida).
     */
    public static function descarga(string $detalle, ?Throwable $previous = null): self
    {
        return new self($detalle, 'No se pudo descargar la información desde el DEIS. '.self::REINTENTO, $previous);
    }

    /**
     * El archivo publicado no tiene el formato esperado o está dañado.
     */
    public static function archivo(string $detalle): self
    {
        return new self($detalle, 'La información publicada por el DEIS no tiene el formato esperado o llegó incompleta. '.self::REINTENTO);
    }

    /**
     * Otra actualización del mismo conjunto de datos ya está en curso.
     */
    public static function enCurso(string $detalle): self
    {
        return new self($detalle, 'Ya había otra actualización de estos datos en curso. '.self::REINTENTO);
    }

    /**
     * Texto simple para la bitácora a partir de cualquier error de la importación.
     */
    public static function mensajeParaUsuario(Throwable $exception): string
    {
        return $exception instanceof self
            ? $exception->mensajeUsuario
            : 'No se pudo completar la actualización de los datos. '.self::REINTENTO;
    }
}
