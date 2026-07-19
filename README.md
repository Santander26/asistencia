# Asistencia

Proyecto PHP/LAMP para sistema de asistencia con Docker.

## Estado actual

- Ya existe un `Dockerfile` para la aplicación PHP.
- Ya existe un `docker-compose.yml` para app + MySQL.
- El proyecto usa `.gitignore` y `.dockerignore`.
- El SQL de inicialización está en `backups/asistencia_db.sql`.

## Pasos para subir a GitHub

1. Crea un repositorio nuevo en GitHub.
2. En tu carpeta local del proyecto:
   ```bash
   git init
   git add .
   git commit -m "Inicializar proyecto Asistencia"
   git remote add origin https://github.com/TU_USUARIO/TU_REPO.git
   git branch -M main
   git push -u origin main
   ```
3. No subas archivos sensibles como `.env` ni `config/smtp.json` si contienen datos reales.

## Configurar variables de entorno

Copia el archivo de ejemplo y edita las contraseñas:

```bash
cp .env.example .env
```

Ejemplo de variables:

```text
MYSQL_ROOT_PASSWORD=CambiaEstaRootPass
DB_PASSWORD=CambiaEstaPass
```

## Ejecución local con Docker Compose

```bash
docker compose up -d --build
```

Luego abre `http://localhost`.

## Uso en VPS con Dokku

Si tu VPS usa Dokku, puedes desplegar con el `Dockerfile` del repositorio:

1. Instala Dokku en el VPS.
2. Crea la app:
   ```bash
   dokku apps:create asistencia
   ```
3. Crea la base de datos MySQL para Dokku:
   ```bash
   dokku mysql:create asistencia-db
   dokku mysql:link asistencia-db asistencia
   ```
4. Configura variables de entorno en Dokku:
   ```bash
   dokku config:set asistencia DB_HOST=asistencia-db DB_NAME=asistencia_db DB_USER=asistencia_user DB_PASSWORD=TuPass
   ```
5. Agrega el remoto Dokku en tu repositorio local:
   ```bash
   git remote add dokku dokku@VPS_IP:asistencia
   git push dokku main
   ```

> Si usas una herramienta llamada "dokploy", el flujo es similar: subes el repositorio a GitHub y haces que dokploy despliegue desde esa URL.

## Uso en VPS con Docker Compose

Si prefieres desplegar directamente con Docker Compose en el servidor:

1. Copia el proyecto al VPS o clona el repositorio.
2. Crea `.env` en el servidor con las contraseñas.
3. Ejecuta:
   ```bash
   docker compose up -d --build
   ```

## Notas importantes

- `config/Conexion.php` se genera automáticamente en `docker-entrypoint.sh` cuando no existe.
- `config/smtp.json` y `config/access_time.json` también se crean si faltan.
- `tmp/`, `foto_perfil/` y `backups/` deben ser escribibles por Apache.

## Corrección aplicada

En `docker-compose.yml` se actualizó la ruta de inicialización de la base de datos para usar el archivo:

- `backups/asistencia_db.sql`

Esto asegura que MySQL pueda cargar el esquema al iniciar.
