<?php

$anioUrgencias = (int) env('DEIS_ANIO_URGENCIAS', 2025);
$anioEgresos = (int) env('DEIS_ANIO_EGRESOS', 2024);

return [

    /*
    |--------------------------------------------------------------------------
    | Comuna
    |--------------------------------------------------------------------------
    |
    | Comuna a la que se filtran los datasets abiertos del DEIS-MINSAL. El
    | código se compara como entero, por lo que "08110" y "8110" coinciden.
    |
    */

    'comuna' => [
        'codigo' => 8110,
        'nombre' => 'Talcahuano',
    ],

    /*
    |--------------------------------------------------------------------------
    | Datasets DEIS
    |--------------------------------------------------------------------------
    |
    | Archivos ZIP publicados en repositoriodeis.minsal.cl. El nombre del
    | archivo de defunciones cambia cada semana (fecha al final), por lo que
    | cada URL se puede reemplazar desde el archivo .env.
    |
    */

    'datasets' => [
        'urgencias' => [
            'titulo' => 'Atenciones de urgencia',
            'descripcion' => 'Atenciones de urgencia por establecimiento, causa y semana estadística.',
            'anio' => $anioUrgencias,
            'url' => env('DEIS_URL_URGENCIAS', "https://repositoriodeis.minsal.cl/SistemaAtencionesUrgencia/AtencionesUrgencia{$anioUrgencias}.zip"),
        ],

        'defunciones' => [
            'titulo' => 'Defunciones',
            'descripcion' => 'Defunciones por mes, sexo, grupo de edad y capítulo CIE-10 de la causa básica (cifras preliminares).',
            'anio' => null,
            'url' => env('DEIS_URL_DEFUNCIONES', 'https://repositoriodeis.minsal.cl/DatosAbiertos/VITALES/DEFUNCIONES_FUENTE_DEIS_2024_2026_27102026.zip'),
        ],

        'egresos' => [
            'titulo' => 'Egresos hospitalarios',
            'descripcion' => 'Egresos hospitalarios de residentes de la comuna por sector, sexo, grupo de edad, capítulo CIE-10 y condición de egreso.',
            'anio' => $anioEgresos,
            'url' => env('DEIS_URL_EGRESOS', "https://repositoriodeis.minsal.cl/DatosAbiertos/EGRESOS/EGRESOS_{$anioEgresos}.zip"),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Urgencias
    |--------------------------------------------------------------------------
    |
    | Par municipal (codigo_deis del CESFAM local) de cada establecimiento de
    | urgencia DEIS. Los establecimientos sin par (p. ej. 19-100, Hospital Las
    | Higueras) se muestran como "Red SST".
    |
    */

    'urgencias' => [
        'establecimientos_locales' => [
            '19-802' => '119302',
            '201018' => '119308',
            '19-803' => '119307',
            '19-807' => '119303',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tableros Power BI del Servicio de Salud Talcahuano
    |--------------------------------------------------------------------------
    |
    | Tableros públicos publicados en https://sstalcahuano.cl/datos-de-salud/.
    |
    */

    'tableros' => [
        [
            'slug' => 'anuario-produccion',
            'titulo' => 'Anuario de Producción 2020-2025',
            'descripcion' => 'Producción anual de actividades de la red asistencial del SST.',
            'url' => 'https://app.powerbi.com/view?r=eyJrIjoiNDE1Yzg4ZGMtYWQyNS00M2ViLTgyZDctYjA4OTIxODU2ODRjIiwidCI6ImY5MWNmYzZlLWMyNTQtNDRlYy04MTk3LTIzMzA3OTg1ODE4NiJ9&pageName=541e446a214de84e8641',
        ],
        [
            'slug' => 'consolidador-rem',
            'titulo' => 'Consolidador REM',
            'descripcion' => 'Consolidado de los Registros Estadísticos Mensuales (REM) de la red.',
            'url' => 'https://app.powerbi.com/view?r=eyJrIjoiNDg2NGIzODQtNzEwYS00OWMwLWE1ZDgtZjg5NTY3MmIyNDA3IiwidCI6ImY5MWNmYzZlLWMyNTQtNDRlYy04MTk3LTIzMzA3OTg1ODE4NiJ9&pageName=140c9d774db41e1be561',
        ],
        [
            'slug' => 'poblaciones',
            'titulo' => 'Poblaciones Servicio de Salud Talcahuano',
            'descripcion' => 'Datos de población del territorio y de la red del SST.',
            'url' => 'https://app.powerbi.com/view?r=eyJrIjoiZTgwNmRkMWItMzQyMS00NWNjLWFlYjQtNGZmN2JhOGQ3MmY0IiwidCI6ImY5MWNmYzZlLWMyNTQtNDRlYy04MTk3LTIzMzA3OTg1ODE4NiJ9',
        ],
        [
            'slug' => 'eno',
            'titulo' => 'ENO 2021-2025',
            'descripcion' => 'Enfermedades de Notificación Obligatoria notificadas en el territorio del SST.',
            'url' => 'https://app.powerbi.com/view?r=eyJrIjoiMGNiNDY2Y2YtNTNjZS00ZjQyLWE2ZGQtNGI5ZDQxN2E1NjRjIiwidCI6ImY5MWNmYzZlLWMyNTQtNDRlYy04MTk3LTIzMzA3OTg1ODE4NiJ9',
        ],
        [
            'slug' => 'defunciones',
            'titulo' => 'Defunciones y Tasas de Mortalidad SST',
            'descripcion' => 'Defunciones y tasas de mortalidad del territorio del SST.',
            'url' => 'https://app.powerbi.com/view?r=eyJrIjoiNGRiZDliZmUtZjEzZi00OGE0LWEyMmUtNjU4Yjc1YjcxNmFmIiwidCI6ImY5MWNmYzZlLWMyNTQtNDRlYy04MTk3LTIzMzA3OTg1ODE4NiJ9',
        ],
        [
            'slug' => 'nacimientos',
            'titulo' => 'Nacimientos y Tasas de Natalidad SST',
            'descripcion' => 'Nacimientos y tasas de natalidad del territorio del SST.',
            'url' => 'https://app.powerbi.com/view?r=eyJrIjoiM2NiZDQ5MzItMGY5OS00YjJlLTkwZWQtY2Q1OTRhYWEyNGEyIiwidCI6ImY5MWNmYzZlLWMyNTQtNDRlYy04MTk3LTIzMzA3OTg1ODE4NiJ9',
        ],
        [
            'slug' => 'vacunas',
            'titulo' => 'Vacunas Campañas',
            'descripcion' => 'Avance de las campañas de vacunación en la red del SST.',
            'url' => 'https://app.powerbi.com/view?r=eyJrIjoiZWIxYWIxOTAtZTNlYi00ZGVjLWE5NDgtMGZkOWU3ZDc1Y2RiIiwidCI6ImY5MWNmYzZlLWMyNTQtNDRlYy04MTk3LTIzMzA3OTg1ODE4NiJ9',
        ],
        [
            'slug' => 'urgencias',
            'titulo' => 'Consultas de Urgencia (2014-2025)',
            'descripcion' => 'Consultas de urgencia de la red del SST entre 2014 y 2025.',
            'url' => 'https://app.powerbi.com/view?r=eyJrIjoiNjQ0OWY5MGEtNDZjYy00MTJlLWI5YWMtOTY4NTNlOThhMTZhIiwidCI6ImY5MWNmYzZlLWMyNTQtNDRlYy04MTk3LTIzMzA3OTg1ODE4NiJ9',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tablero SST relacionado con cada dataset
    |--------------------------------------------------------------------------
    */

    'tablero_relacionado' => [
        'urgencias' => 'urgencias',
        'defunciones' => 'defunciones',
        'egresos' => 'anuario-produccion',
    ],

    /*
    |--------------------------------------------------------------------------
    | Descargas
    |--------------------------------------------------------------------------
    |
    | Directorio donde se guardan los ZIP descargados y tiempo máximo de
    | descarga en segundos.
    |
    */

    'descargas' => [
        'directorio' => storage_path('app/private/deis'),
        'timeout' => (int) env('DEIS_DESCARGA_TIMEOUT', 600),
    ],

];
