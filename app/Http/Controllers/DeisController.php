<?php

namespace App\Http\Controllers;

use App\Services\DeisActualizacionService;
use App\Services\DeisIndicadoresService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/**
 * Módulo Inteligencia DEIS: tableros Power BI del Servicio de Salud Talcahuano y datasets abiertos del DEIS-MINSAL de la comuna.
 */
class DeisController extends Controller
{
    /**
     * Pestañas del módulo (clave => etiqueta). La primera es la predeterminada.
     *
     * @var array<string, string>
     */
    public const PESTANAS = [
        'tableros' => 'Tableros SST',
        'urgencias' => 'Atenciones de urgencia',
        'defunciones' => 'Defunciones',
        'egresos' => 'Egresos hospitalarios',
    ];

    /** Aviso cuando no se inicia ninguna actualización porque ya hay una en curso. */
    private const AVISO_EN_CURSO = 'Los datos del DEIS ya se están actualizando. Espere unos minutos y vuelva a revisar esta página.';

    public function __construct(
        protected DeisIndicadoresService $indicadores,
        protected DeisActualizacionService $actualizacion,
    ) {}

    /**
     * Muestra la pestaña pedida: los tableros del SST o uno de los datasets DEIS con sus KPIs, gráficos y contraste local.
     */
    public function index(Request $request): View
    {
        $pestana = $this->pestana($request->query('tab'));
        $esAdmin = $request->user()->hasRole('Admin');

        // Carga inicial automática: encola los datasets que aún no tienen datos (sin duplicar ni esperar el candado).
        $this->actualizacion->cargarFaltantes();

        $datos = match ($pestana) {
            'tableros' => $this->tableros($this->texto($request, 'tablero')),
            'urgencias' => $this->indicadores->urgencias(
                $this->texto($request, 'anio'),
                $this->texto($request, 'causa'),
                $this->texto($request, 'establecimiento'),
            ),
            'defunciones' => $this->indicadores->defunciones($this->texto($request, 'anio')),
            'egresos' => $this->indicadores->egresos($this->texto($request, 'anio')),
        };

        return view('inteligencia-deis.index', [
            'pestana' => $pestana,
            'pestanas' => self::PESTANAS,
            'datos' => $datos,
            'fuente' => $pestana === 'tableros' ? null : $this->indicadores->fuente($pestana),
            'cargando' => $pestana !== 'tableros' && $this->actualizacion->enCurso($pestana),
            'esAdmin' => $esAdmin,
            'ultimasImportaciones' => $esAdmin ? $this->indicadores->ultimasImportaciones() : collect(),
        ]);
    }

    /**
     * Encola la descarga e importación de los tres datasets DEIS, salvo los que ya tienen una importación en curso reciente.
     */
    public function actualizar(Request $request): RedirectResponse
    {
        $redireccion = redirect()->route('inteligencia-deis.index', ['tab' => $this->pestana($request->input('tab'))]);

        try {
            ['encolados' => $encolados, 'omitidos' => $omitidos] = $this->actualizacion->encolar(userId: $request->user()->id);
        } catch (LockTimeoutException) {
            // Otra solicitud simultánea está iniciando la actualización.
            $encolados = [];
        }

        if ($encolados === []) {
            return $redireccion->with('warning', self::AVISO_EN_CURSO);
        }

        $mensaje = 'Se inició la actualización de '.$this->titulos($encolados).'. '
            .'La actualización se realiza automáticamente en segundo plano; puede tardar unos minutos.';

        if ($omitidos !== []) {
            $mensaje .= ' '.$this->titulos($omitidos).(count($omitidos) === 1 ? ' ya se estaba actualizando.' : ' ya se estaban actualizando.');
        }

        return $redireccion->with('status', $mensaje);
    }

    /**
     * Títulos de los datasets unidos en una frase («A, B y C»).
     *
     * @param  list<string>  $datasets
     */
    private function titulos(array $datasets): string
    {
        return Arr::join(array_map(fn (string $dataset) => (string) config("deis.datasets.{$dataset}.titulo"), $datasets), ', ', ' y ');
    }

    /**
     * Tablero Power BI activo (slug válido o el primero) y la lista completa; las URLs vienen solo de la configuración.
     *
     * @return array{tableros: list<array{slug: string, titulo: string, descripcion: string, url: string}>, activo: array{slug: string, titulo: string, descripcion: string, url: string}}
     */
    private function tableros(?string $slug): array
    {
        $tableros = array_values(config('deis.tableros', []));

        return [
            'tableros' => $tableros,
            'activo' => Arr::first($tableros, fn (array $tablero) => $tablero['slug'] === $slug) ?? $tableros[0],
        ];
    }

    /**
     * Pestaña válida o la predeterminada.
     */
    private function pestana(mixed $valor): string
    {
        return is_string($valor) && array_key_exists($valor, self::PESTANAS) ? $valor : array_key_first(self::PESTANAS);
    }

    /**
     * Parámetro de la URL como texto, o null si falta o no es texto (p. ej. `?anio[]=1`).
     */
    private function texto(Request $request, string $clave): ?string
    {
        $valor = $request->query($clave);

        return is_string($valor) ? $valor : null;
    }
}
