# Despliegue a producción

Checklist para subir el proyecto al servidor (Apache/Nginx + PHP-FPM 8.4 + MySQL).

## 1. Base de datos

```sql
CREATE DATABASE indicadores_asp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'indicadores_asp'@'localhost' IDENTIFIED BY '<contraseña-fuerte>';
GRANT ALL PRIVILEGES ON indicadores_asp.* TO 'indicadores_asp'@'localhost';
FLUSH PRIVILEGES;
```

## 2. Configuración del entorno

1. Copiar `.env.production` a `.env` en el servidor.
2. `php artisan key:generate` (genera un `APP_KEY` nuevo, NO reutilizar el de desarrollo).
3. Rellenar `DB_PASSWORD` (y `DB_USERNAME` si es distinto).

## 3. Dependencias y assets

```bash
composer install --no-dev --optimize-autoloader
npm ci --omit=dev
npm run build
```

## 4. Migraciones y datos

```bash
php artisan migrate --force
php artisan db:seed --force
```

> El seeder imprime por consola las **contraseñas fuertes** generadas para
> `admin@das.cl` y `usuario@das.cl`. Guárdalas y cámbialas tras el primer inicio.
>
> ⚠️ **NO** ejecutar `php artisan db:seed --class=DatosPruebaSeeder` (genera datos
> ficticios y, además, el seeder se bloquea automáticamente en producción).

## 5. Caché de configuración

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Permisos

```bash
chown -R <usuario-web>:<grupo-web> storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## 7. Document root

Apuntar el virtual host a la carpeta `public/` (no a la raíz del proyecto).

## 8. Colas y scheduler

- Colas (importación DEIS): supervisar `php artisan queue:work` (Supervisor o systemd).
- Scheduler (motor de alertas 06:00): añadir el cron
  `* * * * * php /ruta/al/proyecto/artisan schedule:run >> /dev/null 2>&1`

## 9. HTTPS

Instalar certificado SSL para `indicadores-asp.app` (necesario para
`SESSION_SECURE_COOKIE=true`).

## 10. Verificación final

- `APP_DEBUG=false` y `APP_ENV=production` en `.env`.
- El login funciona con las credenciales impresas por `db:seed`.
- `php artisan about` muestra el entorno correcto.
