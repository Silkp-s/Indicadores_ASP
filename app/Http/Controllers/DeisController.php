<?php

namespace App\Http\Controllers;

use App\Jobs\ImportarDatasetDeis;
use App\Models\DeisImportacion;
use App\Services\DeisIndicadoresService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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

    /** Horas durante las que una importación pendiente o en proceso impide encolar otra del mismo dataset. */
    private const HORAS_IMPORTACION_EN_CURSO = 2;

    public function __construct(
        protected DeisIndicadoresService $indicadores
    ) {}

    /**
     * Muestra la pestaña pedida: los tableros del SST o uno de los datasets DEIS con sus KPIs, gráficos y contraste local.
     */
    public function index(Request $request): View
    {
        $pestana = $this->pestana($request->query('tab'));
        $esAdmin = $request->user()->hasRole('Admin');

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
            'esAdmin' => $esAdmin,
            'ultimasImportaciones' => $esAdmin ? $this->indicadores->ultimasImportaciones() : collect(),
        ]);
    }

    /**
     * Encola la descarga e importación de los tres datasets DEIS, salvo los que ya tienen una importación en curso reciente.
     */
    public function actualizar(Request $request): RedirectResponse
    {
        $encolados = [];
        $omitidos = [];

        // El candado evita que dos solicitudes simultáneas pasen ambas la verificación y encolen el mismo dataset dos veces.
        Cache::lock('deis-actualizar', 10)->block(5, function () use ($request, &$encolados, &$omitidos) {
            foreach (DeisImportacion::DATASETS as $dataset) {
                $titulo = (string) config("deis.datasets.{$dataset}.titulo");

                $enCurso = DeisImportacion::query()
                    ->delDataset($dataset)
                    ->enCurso()
                    ->where('created_at', '>=', now()->subHours(self::HORAS_IMPORTACION_EN_CURSO))
                    ->exists();

                if ($enCurso) {
                    $omitidos[] = $titulo;

                    continue;
                }

                $importacion = DeisImportacion::create([
                    'dataset' => $dataset,
                    'origen' => Str::limit((string) config("deis.datasets.{$dataset}.url"), 500, ''),
                    'estado' => DeisImportacion::ESTADO_PENDIENTE,
                    'user_id' => $request->user()->id,
                ]);

                ImportarDatasetDeis::dispatch($dataset, $importacion->id);

                $encolados[] = $titulo;
            }
        });

        $redireccion = redirect()->route('inteligencia-deis.index', ['tab' => $this->pestana($request->input('tab'))]);

        if ($encolados === []) {
            return $redireccion->with('warning', 'No se encoló ninguna actualización: los tres datasets DEIS ya tienen una importación pendiente o en proceso solicitada hace menos de '.self::HORAS_IMPORTACION_EN_CURSO.' horas.');
        }

        $mensaje = 'Actualización de datos DEIS en cola ('.Arr::join($encolados, ', ', ' y ').'). '
            .'Se procesará en segundo plano y requiere que el worker de colas esté activo (composer run dev o php artisan queue:work).';

        if ($omitidos !== []) {
            $mensaje .= ' Se omitió '.Arr::join($omitidos, ', ', ' y ').' porque ya tiene una importación en curso.';
        }

        return $redireccion->with('status', $mensaje);
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
