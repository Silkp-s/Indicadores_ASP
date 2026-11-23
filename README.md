# Sistema de Control de Gestión e Indicadores APS

Plataforma web para la **Dirección de Administración de Salud (DAS) de Talcahuano** que consolida, monitorea y proyecta los indicadores de gestión sanitaria de la red de Atención Primaria de Salud (APS): **Metas Sanitarias Ley 19.813** e **IAAPS**, junto con la inteligencia de datos del DEIS-MINSAL y los tableros Power BI del Servicio de Salud Talcahuano.

> Proyecto de práctica profesional — Instituto Tecnológico UCSC, Sede Talcahuano (2.º semestre 2026).

## Módulos

| Módulo | Ruta | Descripción |
| :--- | :--- | :--- |
| Centro de Mando | `/centro-mando` | Dashboard ejecutivo con KPIs, semáforos, gráficos y proyección estadística |
| IAAPS y Metas | `/iaaps-metas` | Consulta de indicadores y mediciones por período y establecimiento |
| Establecimientos | `/establecimientos` | Directorio de recintos de la red comunal |
| Importación REM | `/importacion` | Carga de planillas REM (`.xlsm`/`.xlsx`) y cálculo de indicadores |
| Inteligencia DEIS | `/inteligencia-deis` | Tableros Power BI del SST y datasets abiertos DEIS-MINSAL |
| Alertas | `/alertas` | Bandeja de alertas generadas por el motor de reglas |
| Reportes | `/reportes` | Reportes mensuales, trimestrales y de alertas exportables (Excel/PDF) |
| Panel de Administración | `/admin` | Usuarios, indicadores, mapeo de celdas REM y establecimientos |

## Stack tecnológico

- **Backend:** PHP 8.4 · Laravel 13 · MySQL/MariaDB
- **Frontend:** Blade · Tailwind CSS 4 · Vite (sin framework JS)
- **Visualización:** ApexCharts · SweetAlert2 (CDN)
- **Permisos:** spatie/laravel-permission
- **Planillas y exportación:** PhpSpreadsheet · Dompdf
- **Pruebas:** Pest

## Requisitos

- PHP 8.4 con las extensiones de Laravel (pdo, mysql, mbstring, etc.)
- Composer 2
- Node.js 20+ y npm
- MySQL 8 / MariaDB

## Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/Silkp-s/Indicadores_ASP.git
cd Indicadores_ASP

# 2. Instalar dependencias, migrar y compilar assets
composer run setup

# 3. Configurar el entorno (crea .env y genera APP_KEY)
copy .env.example .env   # en Linux/macOS: cp .env.example .env
php artisan key:generate

# 4. Configurar la base de datos en .env (MySQL)
#    DB_CONNECTION=mysql · DB_DATABASE=laravel · DB_USERNAME · DB_PASSWORD

# 5. Ejecutar migraciones y datos base
php artisan migrate --seed
```

El comando `composer run setup` equivale a `composer install`, `php artisan key:generate`, `php artisan migrate --force`, `npm install` y `npm run build`.

## Ejecutar en desarrollo

```bash
composer run dev
```

Levanta el servidor HTTP (`:8000`), el listener de colas y Vite en un solo comando.

```bash
# Alternativa: servidor, colas y Vite por separado
php artisan serve
php artisan queue:work
npm run dev
```

## Cuentas de demostración

`php artisan db:seed` crea los roles, el catálogo de indicadores, los establecimientos, las reglas de alertas y las cuentas:

| Rol | Email | Contraseña |
| :--- | :--- | :--- |
| Admin | `admin@das.cl` | `password` |
| Usuario | `usuario@das.cl` | `password` |

## Datos de prueba

Para poblar la aplicación con datos de ejemplo (mediciones históricas, alertas, datasets DEIS y notificaciones) y evaluar todos los módulos:

```bash
php artisan db:seed --class=DatosPruebaSeeder
```

Otros seeders opcionales:

```bash
php artisan db:seed --class=RemMappingSeeder   # mapeos de celdas REM predeterminados
php artisan db:seed --class=IndicadorSeeder    # catálogo de Metas Sanitarias e IAAPS
```

## Comandos útiles

| Comando | Descripción |
| :--- | :--- |
| `composer run dev` | Servidor + colas + Vite |
| `npm run build` / `npm run dev` | Compilar assets |
| `php artisan test` | Ejecutar la suite (Pest) |
| `vendor/bin/pint --dirty` | Formatear PHP |
| `php artisan route:list` | Listar rutas |
| `php artisan alertas:evaluar` | Ejecutar el motor de alertas (programado a diario) |
| `php artisan deis:importar` | Importar datasets DEIS-MINSAL |

## Estructura del proyecto

```
app/
  Console/Commands/     # Comandos: alertas:evaluar, deis:importar
  Http/Controllers/     # Un controlador por módulo
  Http/Middleware/      # Roles (admin_only) y notificaciones
  Http/Requests/        # Form Requests de validación
  Jobs/                 # Colas (importación DEIS)
  Models/               # Modelos Eloquent
  Services/             # Lógica de negocio: RemReaderService, ProyeccionEstadisticaService,
                        #   AlertaEngine, DeisImportService, DeisIndicadoresService, ReporteExportService
database/
  migrations/           # Esquema de base de datos
  seeders/              # Roles, indicadores, establecimientos, alertas, datos de prueba
  factories/            # Factories para pruebas
resources/views/        # Vistas Blade (un directorio por módulo)
routes/web.php          # Rutas de la aplicación
config/deis.php         # Configuración de datasets DEIS y tableros Power BI
tests/                  # Pruebas Pest (Feature / Unit)
```

## Documentación

- [Manual de Usuario](docs/manual-de-usuario.md)
- [Documento de Diseño del Sistema (SDD)](docs/sdd.md)

## Equipo
| Estudiante |
| :--- |
| Vincent Arias |
| Ignacio Gutiérrez |
| Matías Jerez |

- **Organización:** Dirección de Administración de Salud (DAS) Talcahuano
- **Supervisor de la organización:** Emmanuel Cáceres
- **Profesor evaluador:** Nicolás Pérez Garay
