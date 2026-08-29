# Guía de Despliegue en Railway.app

Objetivo: dejar la aplicación accesible públicamente con base de datos en la nube.
Railway detecta automáticamente el `Dockerfile` del proyecto.

---

## Requisitos previos

1. El código subido a un repositorio **público de GitHub**.
2. Una cuenta en <https://railway.app> (se puede crear con la cuenta de GitHub).

---

## Paso 1 — Crear el proyecto

1. Entrar a Railway → **New Project**.
2. Elegir **Deploy from GitHub repo** y seleccionar el repositorio del POS.
3. Railway detectará el `Dockerfile` y comenzará a construir la imagen.

## Paso 2 — Agregar la base de datos MySQL

1. Dentro del proyecto: **New** → **Database** → **Add MySQL**.
2. Railway crea el servicio MySQL y expone estas variables automáticamente:
   `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQL_URL`.

## Paso 3 — Conectar la app con la base de datos

La aplicación **ya lee esas variables** (ver `app/config.php`). Solo hay que
asegurarse de que el servicio web pueda verlas:

1. Abrir el servicio **web** → pestaña **Variables**.
2. Usar **Add Reference / Shared Variable** para enlazar las variables `MYSQL*`
   del servicio de base de datos (o copiar `MYSQL_URL`).

> Railway también inyecta la variable `PORT`; el `Dockerfile` ya configura
> Apache para escuchar en ese puerto.

## Paso 4 — Cargar el esquema y los datos

La base de datos de Railway arranca vacía. Cargar `database/pos_libreria.sql`
de una de estas formas:

**Opción A — Cliente MySQL local**
```bash
mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> \
      <MYSQLDATABASE> < database/pos_libreria.sql
```
(Los valores están en la pestaña **Variables** o **Connect** del servicio MySQL.)

**Opción B — Railway CLI**
```bash
railway login
railway link          # seleccionar el proyecto
railway connect MySQL # abre una consola mysql; luego: source database/pos_libreria.sql
```

**Opción C — Interfaz web**
Usar la pestaña **Data** del servicio MySQL en Railway y pegar el contenido del `.sql`.

## Paso 5 — Generar el dominio público

1. Servicio **web** → **Settings** → **Networking** → **Generate Domain**.
2. Railway entrega una URL como `https://tu-proyecto.up.railway.app`.
3. Abrir esa URL e iniciar sesión con `admin@libreria.com` / `admin123`.

---

## Verificación

- [ ] La URL pública abre la pantalla de login.
- [ ] Se puede iniciar sesión.
- [ ] El dashboard muestra datos (KPIs y gráficas).
- [ ] Se puede registrar una venta y ver la factura con QR.

---

## Alternativa: Railway sin Docker

Si prefieres no usar Docker, Railway también soporta PHP con Nixpacks, pero el
`Dockerfile` incluido es la forma más predecible y es la recomendada para esta entrega.

## Nota sobre las imágenes de productos

En hosting con sistema de archivos efímero (como Railway), las imágenes subidas
pueden perderse al reiniciar el contenedor. Para la demo esto no afecta la
calificación; si se quiere persistencia real, se puede montar un volumen de
Railway en `/var/www/html/public/uploads` o usar un servicio externo (Cloudinary).
