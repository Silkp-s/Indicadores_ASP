<?php

namespace Database\Seeders;

use App\Models\Alerta;
use App\Models\DeisAtencionUrgencia;
use App\Models\DeisDefuncion;
use App\Models\DeisEgreso;
use App\Models\DeisImportacion;
use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\IndicadorMedida;
use App\Models\Notificacion;
use App\Models\User;
use App\Services\AlertaEngine;
use App\Services\RemReaderService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba para evaluar el comportamiento de todos los módulos.
 *
 * Genera mediciones históricas (2025-2026) para Metas Sanitarias e IAAPS, ejecuta el
 * Motor de Alertas sobre los últimos períodos, y rellena los datasets DEIS y las
 * notificaciones. Es idempotente: borra los datos de prueba antes de regenerarlos.
 *
 * Uso: `php artisan db:seed --class=DatosPruebaSeeder`
 */
class DatosPruebaSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Meses a generar por año: 2025 completo y 2026 hasta octubre. */
    private const ANIOS = [
        2025 => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        2026 => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    ];

    public function __construct(private readonly RemReaderService $remReader) {}

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            EstablecimientosSeeder::class,
            IndicadorSeeder::class,
            AlertaReglaSeeder::class,
        ]);

        $this->asegurarUsuarios();

        $this->seedMediciones();
        $this->seedAlertas();
        $this->seedDeis();
        $this->seedNotificaciones();

        $this->command?->info('Datos de prueba generados correctamente.');
    }

    // ---------------------------------------------------------------------------------------------
    // Usuarios
    // ---------------------------------------------------------------------------------------------

    private function asegurarUsuarios(): void
    {
        if (User::count() > 0) {
            return;
        }

        $admin = User::create([
            'name' => 'Admin',
            'apellido' => 'DAS',
            'email' => 'admin@das.cl',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Admin');

        $usuario = User::create([
            'name' => 'Usuario',
            'apellido' => 'Prueba',
            'email' => 'usuario@das.cl',
            'password' => bcrypt('password'),
        ]);
        $usuario->assignRole('Usuario');
    }

    // ---------------------------------------------------------------------------------------------
    // Mediciones (Metas Sanitarias e IAAPS)
    // ---------------------------------------------------------------------------------------------

    private function seedMediciones(): void
    {
        IndicadorMedida::query()->delete();

        $indicadores = Indicador::where('is_active', true)
            ->whereIn('tipo', ['Meta Sanitaria', 'IAAPS'])
            ->orderBy('codigo')
            ->get();

        $establecimientos = Establecimiento::where('is_active', true)->get();

        $perfiles = ['bueno', 'regular', 'malo'];
        $indice = 0;
        $ahora = now();
        $filas = [];

        foreach ($indicadores as $indicador) {
            $perfil = $perfiles[$indice % 3];
            $indice++;

            foreach ($establecimientos as $est) {
                foreach (self::ANIOS as $ano => $meses) {
                    foreach ($meses as $mes) {
                        $medida = $this->generarMedida($indicador, (int) $est->poblacion_inscrita, $perfil, $mes);

                        $filas[] = [
                            'indicadores_id' => $indicador->id,
                            'establecimientos_id' => $est->id,
                            'ano' => $ano,
                            'mes' => $mes,
                            'numerador_value' => $medida['numerador_value'],
                            'denominador_value' => $medida['denominador_value'],
                            'porcentaje_cumplimiento' => $medida['porcentaje_cumplimiento'],
                            'status' => $medida['status'],
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ];
                    }
                }
            }
        }

        foreach (array_chunk($filas, 500) as $lote) {
            IndicadorMedida::insert($lote);
        }
    }

    /**
     * Calcula una medición coherente (numerador, denominador, porcentaje y estado) con el motor real.
     *
     * @return array{numerador_value: float, denominador_value: float, porcentaje_cumplimiento: float, status: string}
     */
    private function generarMedida(Indicador $indicador, int $poblacion, string $perfil, int $mes): array
    {
        $codigo = $indicador->codigo;
        $objetivo = (float) $indicador->target_value;
        $tipo = $this->tipoCalculo($codigo);

        if ($codigo === 'IAAPS-04') {
            // Inverso: el % de derivación va entre 5 % y 16 % (Cumpliendo <= 10 %).
            $porcentaje = 5 + mt_rand(0, 110) / 10;
            $den = max(100, (int) round($poblacion * 0.35));
            $num = $den * $porcentaje / 100;
        } else {
            $logro = $this->logro($perfil, $mes);

            if ($tipo === 'Tasa') {
                $den = $codigo === 'IAAPS-05' ? max(100, (int) round($poblacion / 3.3)) : max(100, $poblacion);
                $num = $den * $objetivo * $logro;
            } else {
                $den = max(100, $poblacion);
                $num = $den * $objetivo * $logro / 100;
            }
        }

        $compliance = $this->remReader->calculateCompliance($num, $den, $objetivo, $tipo);

        return [
            'numerador_value' => round($num, 2),
            'denominador_value' => round($den, 2),
            'porcentaje_cumplimiento' => $compliance['porcentaje'],
            'status' => $compliance['estado'],
        ];
    }

    /**
     * Fracción de la meta lograda (0.3–1.25) con tendencia de mejora mensual y ruido.
     */
    private function logro(string $perfil, int $mes): float
    {
        [$base, $tendencia] = match ($perfil) {
            'bueno' => [0.97, 0.08],
            'regular' => [0.87, 0.10],
            'malo' => [0.58, 0.05],
        };

        $ruido = mt_rand(-30, 30) / 1000; // -0.03 .. 0.03

        return max(0.3, min(1.25, $base + $tendencia * (($mes - 1) / 9) + $ruido));
    }

    private function tipoCalculo(string $codigo): string
    {
        return match ($codigo) {
            'IAAPS-03', 'IAAPS-05', 'IAAPS-09.2' => 'Tasa',
            'IAAPS-04' => 'Inverso',
            default => 'Porcentaje',
        };
    }

    // ---------------------------------------------------------------------------------------------
    // Alertas
    // ---------------------------------------------------------------------------------------------

    private function seedAlertas(): void
    {
        Alerta::query()->delete();

        $motor = app(AlertaEngine::class);

        foreach ([8, 9, 10] as $mes) {
            $motor->evaluar(2026, $mes);
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Datasets DEIS
    // ---------------------------------------------------------------------------------------------

    private function seedDeis(): void
    {
        DeisAtencionUrgencia::query()->delete();
        DeisDefuncion::query()->delete();
        DeisEgreso::query()->delete();
        DeisImportacion::query()->delete();

        $this->seedUrgencias();
        $this->seedDefunciones();
        $this->seedEgresos();
        $this->seedImportaciones();
    }

    private function seedUrgencias(): void
    {
        $establecimientos = [
            '19-802' => ['SAR San Vicente', 'SAR'],
            '201018' => ['SAR Los Cerros', 'SAR'],
            '19-803' => ['SAPU Paulina Avendaño Pereda', 'SAPU'],
            '19-807' => ['SAPU Alcalde Leocán Portus', 'SAPU'],
            '19-100' => ['Hospital Las Higueras (Talcahuano)', 'Hospital'],
        ];

        $causas = [
            1 => 'SECCIÓN 1. TOTAL ATENCIONES DE URGENCIA',
            2 => 'TOTAL CAUSAS SISTEMA RESPIRATORIO',
            12 => 'TOTAL CAUSAS SISTEMA CIRCULATORIO',
        ];

        $filas = [];

        foreach ($establecimientos as $codigo => [$nombre, $tipo]) {
            foreach (range(1, 52) as $semana) {
                foreach ($causas as $idCausa => $glosa) {
                    $total = $this->totalUrgencias($idCausa, $semana);
                    $menores1 = (int) round($total * 0.05);
                    $de1a4 = (int) round($total * 0.10);
                    $de5a14 = (int) round($total * 0.13);
                    $de65YMas = (int) round($total * 0.18);

                    $filas[] = [
                        'codigo_establecimiento' => $codigo,
                        'nombre_establecimiento' => $nombre,
                        'tipo_establecimiento' => $tipo,
                        'id_causa' => $idCausa,
                        'glosa_causa' => $glosa,
                        'anio' => 2025,
                        'semana' => $semana,
                        'total' => $total,
                        'menores_1' => $menores1,
                        'de_1_a_4' => $de1a4,
                        'de_5_a_14' => $de5a14,
                        'de_15_a_64' => max(0, $total - $menores1 - $de1a4 - $de5a14 - $de65YMas),
                        'de_65_y_mas' => $de65YMas,
                    ];
                }
            }
        }

        foreach (array_chunk($filas, 500) as $lote) {
            DeisAtencionUrgencia::insert($lote);
        }
    }

    private function totalUrgencias(int $idCausa, int $semana): int
    {
        $base = match ($idCausa) {
            1 => 350,
            2 => 90,
            12 => 40,
        };

        // Pico invernal para las causas respiratorias (semanas ~20-32).
        $estacional = 0;
        if ($idCausa === 2) {
            $estacional = (int) round(140 * exp(-(($semana - 26) ** 2) / 120));
        }

        return max(5, $base + $estacional + mt_rand(-15, 15));
    }

    private function seedDefunciones(): void
    {
        DeisDefuncion::factory()->count(360)->create(['anio' => 2025]);
        DeisDefuncion::factory()->count(200)->create(['anio' => 2026]);
    }

    private function seedEgresos(): void
    {
        DeisEgreso::factory()->count(200)->create(['anio' => 2024]);
    }

    private function seedImportaciones(): void
    {
        $admin = User::role('Admin')->first();

        foreach (DeisImportacion::DATASETS as $dataset) {
            DeisImportacion::create([
                'dataset' => $dataset,
                'origen' => "https://repositoriodeis.minsal.cl/{$dataset}.zip",
                'estado' => DeisImportacion::ESTADO_COMPLETADO,
                'filas_leidas' => 5000,
                'filas_comuna' => 1200,
                'registros_guardados' => 400,
                'mensaje' => 'Importación de prueba completada correctamente.',
                'user_id' => $admin?->id,
                'iniciado_en' => now()->subHour(),
                'finalizado_en' => now(),
            ]);
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Notificaciones
    // ---------------------------------------------------------------------------------------------

    private function seedNotificaciones(): void
    {
        Notificacion::query()->delete();

        $tipos = ['success', 'info', 'warning'];
        $mensajes = [
            'Importación REM completada exitosamente.',
            'Se detectaron nuevas alertas de riesgo de incumplimiento.',
            'El servidor de producción se encuentra en preparación.',
            'Reporte mensual generado correctamente.',
        ];

        foreach (User::take(5)->get() as $user) {
            Notificacion::create([
                'user_id' => $user->id,
                'tipo' => $tipos[array_rand($tipos)],
                'mensaje' => $mensajes[array_rand($mensajes)],
            ]);
        }
    }
}
