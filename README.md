# 📚 Sistema de Punto de Venta (POS) Web — Librería y Papelería Escolar

Proyecto del curso **Seguridad y Auditoría de Sistemas (PHP y MySQL)**
Universidad Mariano Gálvez de Guatemala · Ciclo 2026

Sistema de Punto de Venta web **full-stack** para una librería/papelería escolar:
inventario, ventas con carrito e IVA, factura con **código QR**, dashboard con
gráficas, autenticación con roles y una **API REST propia** documentada.

---

## 🧰 Tecnologías

| Capa | Tecnología |
|------|------------|
| Frontend | HTML5 + CSS3 + JavaScript ES6, **Bootstrap 5**, Bootstrap Icons |
| Backend | **PHP 8.3** (PDO, sin framework — "PHP puro" organizado) |
| Base de datos | **MySQL 8 / MariaDB** |
| Autenticación | Sesiones PHP + **bcrypt** (`password_hash`), roles admin/cajero |
| API REST | JSON + métodos HTTP + códigos de estado |
| APIs externas | **QR Code** (api.qrserver.com) · **Chart.js** · **Google Fonts** |
| Contenedores | Docker + Docker Compose |
| Hosting | Railway.app (PHP + MySQL) |

---

## 🚀 Cómo ejecutarlo en local (con Docker)

Requisitos: Docker + Docker Compose.

```bash
docker compose up --build
```

Luego abrir <http://localhost:8090>

La base de datos se crea y se llena automáticamente con `database/pos_libreria.sql`.

### Usuarios de prueba

| Rol | Correo | Contraseña |
|-----|--------|-----------|
| Administrador | `admin@libreria.com` | `admin123` |
| Cajero | `cajero@libreria.com` | `cajero123` |

> El **admin** puede gestionar inventario, clientes, usuarios y anular ventas.
> El **cajero** puede vender y consultar, pero no administrar el catálogo.

---

## 📁 Estructura del proyecto

```
.
├── public/                  # Document root (lo que sirve Apache)
│   ├── index.php            # Front controller / router (web + API)
│   ├── .htaccess            # Reescritura de rutas
│   ├── assets/              # CSS y JavaScript
│   └── uploads/             # Imágenes de productos subidas
├── app/
│   ├── config.php           # Configuración (lee variables de entorno)
│   ├── db.php               # Conexión PDO
│   ├── helpers.php          # JSON, auth, roles, render de vistas
│   ├── controllers/         # Lógica: auth, productos, ventas, reportes...
│   └── views/               # Vistas (Bootstrap): dashboard, pos, factura...
├── database/
│   └── pos_libreria.sql     # Script SQL: estructura + datos de prueba
├── docs/                    # Manual, diagrama ER, guía de despliegue
├── Dockerfile               # Imagen PHP 8.3 + Apache
└── docker-compose.yml       # Entorno local (web + MySQL)
```

---

## 🗄️ Base de datos

7 tablas relacionadas y normalizadas: `usuarios`, `categorias`, `productos`,
`clientes`, `ventas`, `detalle_ventas`, `pagos`.
Ver el diagrama entidad-relación en [`docs/DIAGRAMA_ER.md`](docs/DIAGRAMA_ER.md).

---

## 🔌 Documentación de la API REST propia

Todas las respuestas son **JSON** con el formato:

```json
{ "ok": true, "mensaje": "OK", "data": { } }
```

La autenticación es por **sesión (cookie)**: primero se llama a `POST /api/auth/login`
y la cookie de sesión se envía automáticamente en las siguientes peticiones.
Los endpoints marcados con 🔒 requieren sesión; los marcados con 👑 requieren rol **admin**.

### Autenticación

| Método | Endpoint | Descripción | Códigos |
|--------|----------|-------------|---------|
| `POST` | `/api/auth/login` | Inicia sesión. Body: `{correo, password}` | `200`, `401`, `422` |
| `POST` | `/api/auth/register` | Registra un usuario. Body: `{nombre, correo, password, rol}` | `201`, `409`, `422` |
| `POST` | `/api/auth/logout` | Cierra la sesión | `200` |
| `GET`  | `/api/auth/me` 🔒 | Devuelve el usuario autenticado | `200`, `401` |

### Productos (inventario)

| Método | Endpoint | Descripción | Códigos |
|--------|----------|-------------|---------|
| `GET`    | `/api/productos` 🔒 | Lista productos. Query: `?q=`, `?categoria=`, `?stock_bajo=1` | `200` |
| `GET`    | `/api/productos/{id}` 🔒 | Detalle de un producto | `200`, `404` |
| `POST`   | `/api/productos` 👑 | Crea un producto (acepta imagen multipart) | `201`, `409`, `422` |
| `PUT`    | `/api/productos/{id}` 👑 | Actualiza un producto | `200`, `404` |
| `DELETE` | `/api/productos/{id}` 👑 | Baja lógica del producto | `200`, `404` |

### Categorías

| Método | Endpoint | Descripción | Códigos |
|--------|----------|-------------|---------|
| `GET`    | `/api/categorias` 🔒 | Lista categorías con conteo de productos | `200` |
| `POST`   | `/api/categorias` 👑 | Crea categoría. Body: `{nombre, descripcion}` | `201`, `409` |
| `PUT`    | `/api/categorias/{id}` 👑 | Actualiza categoría | `200`, `404` |
| `DELETE` | `/api/categorias/{id}` 👑 | Elimina categoría | `200`, `404` |

### Clientes

| Método | Endpoint | Descripción | Códigos |
|--------|----------|-------------|---------|
| `GET`    | `/api/clientes` 🔒 | Lista/busca clientes. Query: `?q=` | `200` |
| `POST`   | `/api/clientes` 🔒 | Crea cliente | `201`, `422` |
| `PUT`    | `/api/clientes/{id}` 🔒 | Actualiza cliente | `200` |
| `DELETE` | `/api/clientes/{id}` 👑 | Elimina cliente | `200`, `404`, `409` |

### Ventas (POS)

| Método | Endpoint | Descripción | Códigos |
|--------|----------|-------------|---------|
| `GET`  | `/api/ventas` 🔒 | Lista las últimas ventas | `200` |
| `GET`  | `/api/ventas/{id}` 🔒 | Venta completa con detalle (usada por la factura) | `200`, `404` |
| `POST` | `/api/ventas` 🔒 | Registra una venta (transacción atómica) | `201`, `422` |
| `POST` | `/api/ventas/{id}/anular` 👑 | Anula la venta y devuelve el stock | `200`, `404`, `409` |

### Reportes

| Método | Endpoint | Descripción | Códigos |
|--------|----------|-------------|---------|
| `GET` | `/api/reportes/dashboard` 🔒 | KPIs + ventas por día/mes + top productos | `200` |
| `GET` | `/api/reportes/ventas` 🔒 | Reporte por rango. Query: `?desde=&hasta=` | `200` |
| `GET` | `/api/reportes/productos-vendidos` 🔒 | Ranking de productos más vendidos | `200` |

### Ejemplos de uso

**Login**
```bash
curl -c cookies.txt -X POST http://localhost:8090/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"correo":"admin@libreria.com","password":"admin123"}'
```

**Registrar una venta**
```bash
curl -b cookies.txt -X POST http://localhost:8090/api/ventas \
  -H "Content-Type: application/json" \
  -d '{
        "id_cliente": 2,
        "metodo_pago": "QR",
        "descuento": 5,
        "items": [
          { "id_producto": 4, "cantidad": 2 },
          { "id_producto": 7, "cantidad": 1 }
        ]
      }'
```

Respuesta (`201 Created`):
```json
{
  "ok": true,
  "mensaje": "Venta registrada correctamente.",
  "data": { "id": 7, "subtotal": 40.5, "iva": 4.26, "descuento": 5, "total": 39.76, "...": "..." }
}
```

---

## 🌐 Despliegue en Railway

Ver la guía paso a paso en [`docs/DESPLIEGUE_RAILWAY.md`](docs/DESPLIEGUE_RAILWAY.md).

---

## 🔐 Seguridad implementada

- Contraseñas con **bcrypt** (`password_hash` / `password_verify`), nunca en texto plano.
- **Consultas preparadas (PDO)** en todas las consultas → previene inyección SQL.
- **Escape de salida** (`htmlspecialchars` en PHP y `escapeHtml` en JS) → previene XSS.
- **Rutas protegidas**: sin sesión no se accede (web redirige a login, API responde `401`).
- **Control por roles**: acciones de administración exigen rol admin (`403` si no).
- `session_regenerate_id()` al iniciar sesión → previene fijación de sesión.
- Las imágenes subidas **no pueden ejecutarse como PHP** (`.htaccess` en `/uploads`).

---

## 👤 Autor

Proyecto académico — UMG, Seguridad y Auditoría de Sistemas, Ciclo 2026.
