# Manual de Usuario — POS Librería Escolar

Guía básica para usar el sistema de punto de venta.

---

## 1. Ingreso al sistema

1. Abrir la URL del sistema en el navegador.
2. Escribir el **correo** y la **contraseña**.
3. Presionar **Ingresar**.

| Rol | Correo de prueba | Contraseña | Qué puede hacer |
|-----|------------------|-----------|------------------|
| Administrador | `admin@libreria.com` | `admin123` | Todo: inventario, ventas, clientes, usuarios, anular ventas |
| Cajero | `cajero@libreria.com` | `cajero123` | Vender, ver inventario, clientes y reportes |

> Si escribes mal las credenciales, verás el mensaje "Credenciales inválidas".
> Para salir, usa **Cerrar sesión** en la parte inferior del menú lateral.

---

## 2. Dashboard (inicio)

Al ingresar verás:

- **Tarjetas KPI:** ventas de hoy, ventas del mes, productos activos y alertas de stock bajo.
- **Gráficas (Chart.js):** ventas de los últimos 7 días, ventas por mes y productos más vendidos.
- **Alertas de inventario:** lista de productos con stock menor a 5 unidades.

---

## 3. Punto de Venta (POS) — realizar una venta

1. Entrar al menú **Punto de Venta**.
2. **Buscar** el producto por nombre o código en la barra superior.
3. Hacer **clic en el producto** para agregarlo al carrito (a la derecha).
4. Ajustar la **cantidad** con los botones `−` y `+`. El sistema no deja vender
   más de lo que hay en stock.
5. (Opcional) Seleccionar el **cliente** y el **método de pago** (efectivo, tarjeta o QR).
6. (Opcional) Escribir un **descuento** en Quetzales.
7. Revisar **Subtotal**, **IVA (12%)** y **TOTAL**, que se calculan automáticamente.
8. Presionar **Cobrar**.
9. Se abre la **factura imprimible** con un **código QR**. Usar **Imprimir / PDF**
   para imprimirla o guardarla como PDF.

---

## 4. Inventario (productos)

> Crear, editar y eliminar productos es exclusivo del **Administrador**.

- **Buscar:** por nombre o código.
- **Filtrar:** por categoría.
- **Nuevo producto:** botón azul → llenar código, nombre, precio, stock,
  categoría e (opcional) imagen → **Guardar**.
- **Editar:** botón del lápiz en cada fila.
- **Eliminar:** botón del bote de basura (baja lógica; la venta histórica se conserva).
- **Alerta de stock:** los productos con menos de 5 unidades se muestran en rojo.

---

## 5. Clientes

- Ver, buscar, crear y editar clientes.
- El cliente **"Consumidor Final" (NIT: CF)** viene por defecto para ventas rápidas.
- Eliminar clientes es exclusivo del Administrador (no se puede eliminar un cliente con ventas).

---

## 6. Ventas (historial)

- Lista de todas las ventas con fecha, cliente, cajero, método y total.
- Botón de **factura** para volver a ver/imprimir cualquier venta.
- El **Administrador** puede **anular** una venta; al anularla se devuelve el stock.

---

## 7. Reportes

- Elegir un rango de fechas **Desde / Hasta** y presionar **Generar**.
- Muestra: número de ventas, total vendido, IVA recaudado y descuentos.
- Tabla de ventas del período y ranking de **productos más vendidos**.
- Botón **Imprimir / PDF** para exportar el reporte.

---

## 8. Usuarios (solo Administrador)

- Ver la lista de usuarios y sus roles.
- **Nuevo usuario:** nombre, correo, contraseña (mín. 6) y rol (cajero o administrador).

---

## Preguntas frecuentes

**No puedo agregar más de X unidades al carrito.**
Llegaste al stock disponible del producto. Reabastece el inventario para vender más.

**Un cajero no ve los botones de crear/editar/eliminar.**
Es correcto: esas acciones son solo para el Administrador.

**La factura no muestra el código QR.**
El QR se genera con un servicio externo (api.qrserver.com); requiere conexión a Internet.
