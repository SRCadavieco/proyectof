<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Gemini Node Backend Integration

This project includes a first test integration between Laravel and a Node.js service (Cloud Run) for AI-driven design generation.

### Env vars

Add the following to your `.env`:

```
GEMINI_BACKEND_URL=https://<your-cloud-run-url>
GEMINI_BACKEND_TOKEN=<your-shared-bearer-token>
```

### Endpoints

- POST [designs/generate](routes/web.php#L8): Protected by `auth`. Body: `{ "prompt": "..." }`.
- GET [designs](routes/web.php#L7): Simple test UI at `/designs`.

### Test flow

1. Ensure you can authenticate (e.g., install Breeze or remove `auth` middleware temporarily for testing).
2. Open `/designs`, enter a prompt and click “Generar”.
3. Laravel calls the Node backend using the configured URL and token.
4. The UI shows a loader and then renders the returned image if available, plus the raw JSON.

Note: The UI tries to display either `imageUrl`/`image_url` or a Base64 image in `imageBase64`/`image_base64`.

## Despliegue en Cloud Run (Apache + PHP)

Cloud Run ejecuta contenedores y espera que la app escuche en `PORT`. Este repo incluye un `Dockerfile` sencillo con Apache + PHP que:

- Activa `mod_rewrite` y apunta el DocumentRoot a `public/`.
- Instala dependencias con Composer.
- Ajusta Apache para escuchar en el puerto `PORT` inyectado por Cloud Run.

### Variables de entorno recomendadas

- APP_ENV=production
- APP_DEBUG=false
- APP_KEY=base64:... (genera con `php artisan key:generate` y copia valor)
- APP_URL=https://<tu-url-de-cloud-run>
- Opcional (privacidad rápida): APP_GATE_USER=team, APP_GATE_PASSWORD=strong-password
- Si usas base de datos (Cloud SQL): DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD (y conector)
- Sesiones/cache: para evitar almacenamiento de servidor en disco, usa SESSION_DRIVER=cookie y CACHE_DRIVER=array|redis.

### Comandos de despliegue

```bash
# Construir y subir la imagen (reemplaza PROJECT_ID)
gcloud builds submit --tag gcr.io/PROJECT_ID/proyectof

# Desplegar como un servicio aparte (reemplaza región)
gcloud run deploy proyectof \
	--image gcr.io/PROJECT_ID/proyectof \
	--region europe-west1 \
	--allow-unauthenticated \
	--set-env-vars APP_ENV=production,APP_DEBUG=false,APP_KEY=base64:REEMPLAZA,APP_GATE_USER=team,APP_GATE_PASSWORD=strong-password
```

Nota: `--allow-unauthenticated` expone públicamente la URL; tu candado básico (`APP_GATE_*`) mantiene las rutas web privadas. Si prefieres IAM de Google además del candado, usa `--allow-unauthenticated=false` y concede acceso a cuentas específicas.

### Consideraciones

- El sistema de archivos es efímero: no persistas subidas en `storage/` o `public/`; usa Cloud Storage.
- No ejecutes `php artisan serve` en producción: Apache del contenedor sirve la app.
- No hay tareas en background persistentes en Cloud Run; usa Cloud Tasks/Jobs para colas.

## CI/CD con GitHub (auto-despliegue)

Tienes dos opciones sencillas para que al hacer push se despliegue solo:

- **GitHub Actions**: ya existe [ .github/workflows/cloudrun-deploy.yml ](.github/workflows/cloudrun-deploy.yml). Configura los secretos y cualquier push a `main` construirá y desplegará.
- **Cloud Build Trigger**: conecta el repo GitHub con Cloud Build y usa [cloudbuild.yaml](cloudbuild.yaml) para que el despliegue ocurra automáticamente.

### Configurar Cloud Build Trigger (desde GitHub)
1. En Google Cloud Console, ve a Cloud Build → Triggers.
2. Conecta tu GitHub mediante el Cloud Build GitHub App.
3. Crea un trigger que escuche `main` y use el archivo `cloudbuild.yaml` en la raíz.
4. Ajusta sustituciones si quieres (REGION, SERVICE). Las env vars de la app se pasan en el paso `--set-env-vars`.

Recomendado: guarda `APP_KEY` y credenciales en Secret Manager y usa `gcloud run deploy --update-secrets` en lugar de `--set-env-vars` para mayor seguridad (lo podemos añadir cuando lo necesites).

### ¿Por qué no "clonar el repo" directamente en Cloud Run?
Cloud Run no ejecuta `git clone` al arrancar; siempre deploya una **imagen de contenedor**. Conectar GitHub se hace a través de **Actions** o **Cloud Build**, que construyen la imagen con tu código y la publican en Cloud Run al hacer push.

### Pasar `.env` automáticamente en GitHub Actions

- Opción sencilla: crea un secreto `ENV_FILE` en GitHub con el contenido completo de tu `.env`.
- El workflow [.github/workflows/cloudrun-deploy.yml](.github/workflows/cloudrun-deploy.yml) lo detecta y convierte ese `.env` en `--set-env-vars` al desplegar.
- Si no defines `ENV_FILE`, usa los secretos individuales (`APP_KEY`, `APP_GATE_USER`, `APP_GATE_PASSWORD`).

Aviso: incluir secretos en `ENV_FILE` es cómodo pero sensible. Para producción, considera Secret Manager.

## Actualizaciones (cómo desplegar cambios)

### Opción 1: Manual (rápida)

Cada vez que hagas cambios:

```bash
gcloud builds submit --tag gcr.io/PROJECT_ID/proyectof
gcloud run deploy proyectof \
	--image gcr.io/PROJECT_ID/proyectof \
	--region europe-west1 \
	--allow-unauthenticated \
	--set-env-vars APP_ENV=production,APP_DEBUG=false,APP_KEY=base64:REEMPLAZA,APP_GATE_USER=team,APP_GATE_PASSWORD=strong-password
```

Si cambias variables de entorno sin reconstruir la imagen:

```bash
gcloud run services update proyectof \
	--region europe-west1 \
	--set-env-vars APP_GATE_PASSWORD=nueva-contraseña
```

### Opción 2: Automática (GitHub Actions)

En `.github/workflows/cloudrun-deploy.yml` se añadió un flujo que despliega al hacer push a `main`.

Configura estos secretos en GitHub (Settings → Secrets and variables → Actions):
- `GCP_PROJECT_ID`: ID de proyecto GCP
- `GCP_REGION`: región (ej. `europe-west1`)
- `GCP_SA_KEY`: JSON del Service Account con permisos Cloud Build/Run
- `APP_KEY`: tu clave de app (usar formato `base64:...`)
- `APP_GATE_USER`, `APP_GATE_PASSWORD`: credenciales del candado básico

Con eso, al hacer push a `main`, se construye y despliega automáticamente.

### Assets (Vite)

Si editas el frontend, asegúrate de que los assets compilados existan en `public/build`.

Opciones:
- Compila localmente y commitea `public/build`.
- O agrega una etapa de build en el Dockerfile (Node) para generar `public/build` durante el build.

Para mantenerlo sencillo por ahora, puedes compilar localmente:

```bash
npm ci
npm run build
# verifica que public/build/ se actualizó
```

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
