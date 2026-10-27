# AGENTS.md

## Project

Laravel 13 / PHP 8.4 application for Chilean primary-healthcare KPI monitoring: Metas Sanitarias Ley 19.813 and IAAPS. Server-rendered Blade + Tailwind 4 + Vite; no JS framework. User-facing copy and most domain names are Spanish.

Main modules: `centro-mando` dashboard, `indicadores` catalog, `iaaps-metas`, `establecimientos`, `importacion` (REM spreadsheets), and `admin` (users, indicators, REM cell mappings). Auth uses Spatie roles `Admin` / `Usuario`; regular users land on `/centro-mando`, admins on `/admin`.

`CLAUDE.md` is the stock Boost bootstrap and is stale. Boost is already installed and configured in `boost.json` and `opencode.json`; use this file as the source of truth.

## Commands

- Dev: `composer run dev` (runs `php artisan dev`: HTTP server on `:8000`, queue listener, and Vite).
- Frontend: `npm run build` after CSS/JS changes; `npm run dev` for watch mode. A Vite manifest error means the build has not been run.
- Tests: `php artisan test --compact [path] [--filter=name]` or `vendor/bin/pest [path] [--filter=name]`. `composer test` clears config and runs the whole suite.
- Format PHP: `vendor/bin/pint --dirty --format agent`.
- Setup: `composer run setup` installs dependencies, migrates, and builds assets, but does **not** seed. Run `php artisan db:seed` for demo accounts (`admin@das.cl` / `password`, `usuario@das.cl` / `password`).
- Local `.env` uses MySQL (`DB_CONNECTION=mysql`; database defaults to `laravel`). Tests override this to SQLite `:memory:` in `phpunit.xml`, so keep queries portable between MySQL and SQLite.

## Where things live

- `app/Services/RemReaderService.php` is the core REM parser: hardcoded DEIS sheet/cell ranges, metadata detection, numerator/denominator extraction, and `calculateCompliance()`.
- Import is stateful. `ImportacionController::previsualizar()` stores extraction data in cache under `rem_pending_{token}` for 2 hours; `guardar()` recalculates and `updateOrCreate`s `indicador_medidas`. Keep both paths in sync.
- Changing an indicator usually requires `database/seeders/IndicadorSeeder.php`, `RemReaderService::extractNumeradores()`, `RemReaderService::determinePendingInputs()`, and the `switch` in `ImportacionController::calculateAndSaveIndicadores()`. `rem_mappings` lets admins override the hardcoded cell formulas.
- `ProyeccionEstadisticaService` implements linear regression plus 3-month SMA for the Centro de Mando; there is no external statistics package.
- `RemMappingSeeder` is not called by `DatabaseSeeder`; run it explicitly if default REM mappings are needed.
- DB naming: `indicador_medidas` uses `ano` (not `año`), plus `numerador_value`, `denominador_value`, `porcentaje_cumplimiento`, and `status` (`Cumpliendo` / `En Riesgo` / `Crítico`). `año` is only an accessor. Enum values live in `Indicador::TIPOS` / `FUENTES` / `PERIODICIDADES` and `Establecimiento::TIPOS`.

## Conventions

- This project uses Laravel 13 model attributes: `#[Table]`, `#[Fillable]`, `#[Hidden]`, and `#[Scope]`. Do not replace them with `$table` / `$fillable` properties.
- Put new validation in the module's Form Request (`IndicadorRequest`, `IaapsMetaRequest`, `EstablecimientoRequest`, `RemMappingRequest`). `AdminController` user methods are the exception and validate inline.
- Frontend is Blade-first: ApexCharts and SweetAlert2 are CDN scripts, and page JavaScript is inline in the relevant Blade view. `resources/js/app.js` is effectively empty; do not assume a bundled JS app. Tailwind is v4 CSS-first (`@theme` in `resources/css/app.css`); there is no `tailwind.config.js`.
- Keep user-facing text, flash messages, and route names in Spanish.

## Testing quirks

- `tests/Pest.php` does **not** apply `RefreshDatabase` globally (the line is commented out). Every Feature test must call `uses(RefreshDatabase::class);`.
- REM integration tests read real fixtures from `D:/DAS/documentos/119308A06.xlsm` and `119308P06.xlsm` (some fall back to `D:/DAS/jkm/`). They call `markTestSkipped()` when the files are absent; do not commit spreadsheets or remove those skips.
- `PhpOffice\PhpSpreadsheet` is used by `RemReaderService` and several tests but is **not declared** in `composer.json` / `composer.lock`. Those tests currently fail with "Class ... not found"; add the dependency only with approval.
- Known pre-existing failures: `CentroMandoTest` expects `Metas Ley 19.813`, which the view no longer renders, and spreadsheet-dependent tests fail on the missing package above. Confirm a failure predates your change before fixing it.

## Agent workflow

- Boost MCP is configured in `opencode.json`. Prefer `search-docs` for Laravel/package API questions, `database-query` for DB inspection, `get-absolute-url` before sharing URLs, and `last-error` / `browser-logs` when debugging.
- Skills are committed under `.agents/skills` (there is no `.ai/rules` directory). Load the relevant skill for Laravel, Tailwind, testing, or convention work.
- Use `php artisan make:*` for new framework files. Do not add dependencies, create documentation, or delete tests without approval. Prefer tests over ad-hoc tinker or verification scripts.
