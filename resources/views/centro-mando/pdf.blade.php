<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Mando · {{ $periodoSeleccionado }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; margin: 0; padding: 24px; background: #fff; font-size: 12px; }
        h1 { font-size: 20px; margin: 0; text-transform: uppercase; color: #0f1424; }
        h2 { font-size: 14px; margin: 22px 0 8px; color: #0f1424; }
        .subtitulo { color: #64748b; font-size: 11px; margin: 4px 0 0; }
        .meta { color: #64748b; font-size: 11px; margin: 6px 0 0; }
        .kpis { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; margin-top: 12px; }
        .kpi { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; }
        .kpi .label { font-size: 9px; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
        .kpi .valor { font-size: 18px; font-weight: 700; margin-top: 4px; color: #0f1424; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e2e8f0; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; color: #475569; }
        td.num, th.num { text-align: right; }
        td.center, th.center { text-align: center; }
        .ok { color: #15803d; font-weight: 700; }
        .warn { color: #b45309; font-weight: 700; }
        .bad { color: #b91c1c; font-weight: 700; }
        .toolbar { position: sticky; top: 0; display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 16px; background: #fff; padding: 8px 0; }
        .btn { border: none; border-radius: 6px; padding: 8px 14px; font-size: 12px; font-weight: 600; cursor: pointer; color: #fff; }
        .btn-print { background: #0f1424; }
        .btn-close { background: #64748b; }
        .foot { margin-top: 20px; color: #94a3b8; font-size: 10px; }
        @page { size: A4 landscape; margin: 10mm; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" class="btn btn-print" onclick="window.print()">Imprimir / Guardar PDF</button>
        <button type="button" class="btn btn-close" onclick="window.close()">Cerrar</button>
    </div>

    <header>
        <h1>Centro de Mando Ejecutivo</h1>
        <p class="subtitulo">Supervisión comunal de cumplimiento, semáforo de metas e indicadores críticos</p>
        <p class="meta">
            Periodo: <strong>{{ $mesNombre }} {{ $año }}</strong> ·
            Alcance: <strong>{{ $establecimientoNombre }}</strong> ·
            Generado el {{ now()->format('d/m/Y H:i') }}
        </p>
    </header>

    @php
        $resumenProyeccion = $proyecciones['resumen'] ?? [];
        $proyectados = $proyecciones['indicadores_proyectados'] ?? [];
    @endphp

    <section>
        <h2>Resumen del periodo</h2>
        <div class="kpis">
            <div class="kpi"><div class="label">Mediciones</div><div class="valor">{{ $totalMediciones }}</div></div>
            <div class="kpi"><div class="label">Cumpliendo</div><div class="valor ok">{{ $cumpliendoCount }}</div></div>
            <div class="kpi"><div class="label">En Riesgo</div><div class="valor warn">{{ $enRiesgoCount }}</div></div>
            <div class="kpi"><div class="label">Crítico</div><div class="valor bad">{{ $criticoCount }}</div></div>
            <div class="kpi"><div class="label">Cumplimiento promedio</div><div class="valor">{{ number_format((float) $cumplimientoPromedio, 1, ',', '.') }}%</div></div>
            <div class="kpi"><div class="label">Población beneficiaria</div><div class="valor">{{ number_format((int) $poblacionBeneficiaria, 0, ',', '.') }}</div></div>
        </div>
    </section>

    <section>
        <h2>Predicción a diciembre (mes 12)</h2>
        <div class="kpis">
            <div class="kpi"><div class="label">Total evaluados</div><div class="valor">{{ $resumenProyeccion['total_evaluados'] ?? 0 }}</div></div>
            <div class="kpi"><div class="label">Cumplirán meta</div><div class="valor ok">{{ $resumenProyeccion['cumpliran_count'] ?? 0 }}</div></div>
            <div class="kpi"><div class="label">En riesgo al cierre</div><div class="valor warn">{{ $resumenProyeccion['en_riesgo_count'] ?? 0 }}</div></div>
            <div class="kpi"><div class="label">Críticos al cierre</div><div class="valor bad">{{ $resumenProyeccion['criticos_count'] ?? 0 }}</div></div>
            <div class="kpi"><div class="label">Tasa de éxito</div><div class="valor">{{ number_format((float) ($resumenProyeccion['tasa_exito_proyectada'] ?? 0), 1, ',', '.') }}%</div></div>
            <div class="kpi"><div class="label">Cierre promedio</div><div class="valor">{{ number_format((float) ($resumenProyeccion['cumplimiento_promedio_proyectado'] ?? 0), 1, ',', '.') }}%</div></div>
        </div>
    </section>

    <section>
        <h2>Detalle de indicadores del periodo</h2>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Establecimiento</th>
                    <th class="num">Numerador</th>
                    <th class="num">Denominador</th>
                    <th class="num">Cumpl. (%)</th>
                    <th class="num">Meta (%)</th>
                    <th class="center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($medidas as $medida)
                    @php
                        $meta = (float) ($medida->indicador?->target_value ?? 100);
                        $cumplimiento = (float) $medida->porcentaje_cumplimiento;
                        $claseEstado = $medida->status === 'Cumpliendo' ? 'ok' : ($medida->status === 'En Riesgo' ? 'warn' : 'bad');
                    @endphp
                    <tr>
                        <td>{{ $medida->indicador?->codigo ?? 'N/A' }}</td>
                        <td>{{ $medida->indicador?->nombre ?? 'Sin nombre' }}</td>
                        <td>{{ $medida->indicador?->tipo ?? 'Meta' }}</td>
                        <td>{{ $medida->establecimiento?->nombre ?? 'N/A' }}</td>
                        <td class="num">{{ number_format((float) $medida->numerador_value, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $medida->denominador_value, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($cumplimiento, 1, ',', '.') }}</td>
                        <td class="num">{{ number_format($meta, 1, ',', '.') }}</td>
                        <td class="center {{ $claseEstado }}">{{ $medida->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="center">Sin mediciones para el periodo seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section>
        <h2>Detalle de predicciones</h2>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th class="num">Actual (%)</th>
                    <th class="num">Regresión (%)</th>
                    <th class="num">SMA-3 (%)</th>
                    <th class="num">Proyección anual (%)</th>
                    <th class="num">Brecha proyectada</th>
                    <th class="center">Estado anticipado</th>
                    <th class="center">Mes logro</th>
                    <th class="num">Confianza (%)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($proyectados as $proyeccion)
                    @php
                        $estadoAnticipado = $proyeccion['estado_anticipado'] ?? '—';
                        $claseEstado = $estadoAnticipado === 'Cumplirá' ? 'ok' : ($estadoAnticipado === 'En Riesgo' ? 'warn' : 'bad');
                    @endphp
                    <tr>
                        <td>{{ $proyeccion['codigo'] ?? 'N/A' }}</td>
                        <td>{{ $proyeccion['nombre'] ?? 'Sin nombre' }}</td>
                        <td>{{ $proyeccion['tipo'] ?? 'Meta' }}</td>
                        <td class="num">{{ number_format((float) ($proyeccion['cumplimiento_actual'] ?? 0), 1, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) ($proyeccion['regresion']['proyeccion_mes_12'] ?? 0), 1, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) ($proyeccion['media_movil']['proyeccion_mes_12'] ?? 0), 1, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) ($proyeccion['proyeccion_anual'] ?? 0), 1, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) ($proyeccion['brecha_proyectada'] ?? 0), 1, ',', '.') }}</td>
                        <td class="center {{ $claseEstado }}">{{ $estadoAnticipado }}</td>
                        <td class="center">{{ $proyeccion['mes_logro_estimado'] ?? '—' }}</td>
                        <td class="num">{{ number_format((float) ($proyeccion['confianza_modelo'] ?? 0), 1, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="center">Sin indicadores proyectables (se requieren al menos 3 meses de datos).</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <p class="foot">
        Reporte generado automáticamente por el Centro de Mando · {{ config('app.name') }}.
        La predicción usa Regresión Lineal Simple y Media Móvil (SMA-3) sobre los meses disponibles del año.
    </p>

    <script>
        window.addEventListener('load', () => {
            window.setTimeout(() => window.print(), 400);
        });
    </script>
</body>
</html>
