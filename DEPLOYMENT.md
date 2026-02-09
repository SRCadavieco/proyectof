# Configuración de Despliegue Automático desde GitHub

## Paso 1: Crear Secrets en Google Secret Manager

```bash
# APP_KEY de Laravel
echo -n "base64:4ZO8qcHIzKh1hJ4GaostSEMwI/P2XqphnZ49yXcFPB0=" | gcloud secrets create APP_KEY --data-file=-

# URL del backend de Gemini
echo -n "https://fabricai-278322460825.europe-west1.run.app/" | gcloud secrets create GEMINI_BACKEND_URL --data-file=-

# Token del backend (si lo tienes, sino déjalo vacío)
echo -n "" | gcloud secrets create GEMINI_BACKEND_TOKEN --data-file=-

# Dar permisos al servicio de Cloud Build para acceder a los secrets
PROJECT_NUMBER=$(gcloud projects describe gen-lang-client-0352261941 --format="value(projectNumber)")
gcloud secrets add-iam-policy-binding APP_KEY \
  --member=serviceAccount:${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com \
  --role=roles/secretmanager.secretAccessor

gcloud secrets add-iam-policy-binding GEMINI_BACKEND_URL \
  --member=serviceAccount:${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com \
  --role=roles/secretmanager.secretAccessor

gcloud secrets add-iam-policy-binding GEMINI_BACKEND_TOKEN \
  --member=serviceAccount:${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com \
  --role=roles/secretmanager.secretAccessor
```

## Paso 2: Conectar GitHub con Cloud Build

### Opción A: Desde la Consola de Google Cloud (Recomendado)

1. Ve a [Cloud Build Triggers](https://console.cloud.google.com/cloud-build/triggers?project=gen-lang-client-0352261941)
2. Haz clic en **"CONNECT REPOSITORY"** o **"CONECTAR REPOSITORIO"**
3. Selecciona **"GitHub"** como fuente
4. Autoriza a Google Cloud Build a acceder a tu cuenta de GitHub
5. Selecciona tu repositorio de Laravel
6. Completa la conexión

### Opción B: Desde la Línea de Comandos

```bash
# Instalar la aplicación de GitHub para Cloud Build
gcloud alpha builds connections create github proyectof-connection \
  --region=europe-west1
```

## Paso 3: Crear el Trigger Automático

### Desde la Consola (Más Fácil):

1. En [Cloud Build Triggers](https://console.cloud.google.com/cloud-build/triggers?project=gen-lang-client-0352261941)
2. Haz clic en **"CREATE TRIGGER"** o **"CREAR ACTIVADOR"**
3. Configura:
   - **Nombre**: `deploy-on-push`
   - **Región**: `global` o `europe-west1`
   - **Evento**: `Push to a branch`
   - **Repositorio**: Tu repositorio conectado
   - **Rama**: `^main$` o `^master$` (según tu rama principal)
   - **Archivo de configuración**: `Cloud Build configuration file (yaml or json)`
   - **Ubicación**: `/cloudbuild.yaml`
4. Haz clic en **"CREATE"** o **"CREAR"**

### Desde la Línea de Comandos:

```bash
gcloud builds triggers create github \
  --name=deploy-on-push \
  --repo-name=NOMBRE_DE_TU_REPO \
  --repo-owner=TU_USUARIO_GITHUB \
  --branch-pattern="^main$" \
  --build-config=cloudbuild.yaml \
  --region=europe-west1
```

## Paso 4: Commit y Push

```bash
cd ~/proyectof
git add .
git commit -m "Configure automatic deployment from GitHub"
git push origin main
```

## Verificar el Despliegue

1. Ve a [Cloud Build History](https://console.cloud.google.com/cloud-build/builds?project=gen-lang-client-0352261941)
2. Deberías ver un nuevo build iniciándose automáticamente
3. El build tomará entre 2-5 minutos
4. Una vez completado, tu servicio estará actualizado automáticamente en Cloud Run

## Mantenimiento

### Ver Secrets:
```bash
gcloud secrets list
```

### Actualizar un Secret:
```bash
echo -n "nuevo-valor" | gcloud secrets versions add NOMBRE_SECRET --data-file=-
```

### Ver Triggers:
```bash
gcloud builds triggers list
```

### Ejecutar un Trigger manualmente:
```bash
gcloud builds triggers run deploy-on-push --branch=main
```

## Troubleshooting

Si el build falla:
1. Verifica los logs en Cloud Build History
2. Asegúrate de que los secrets existen: `gcloud secrets list`
3. Verifica permisos: `gcloud secrets get-iam-policy APP_KEY`
4. Verifica que el cloudbuild.yaml esté en la raíz del repo
