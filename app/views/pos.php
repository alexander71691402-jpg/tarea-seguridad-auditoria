<?php /** Punto de Venta (POS): búsqueda, carrito, IVA 12%, descuento, cobro. */ ?>
<div class="row g-3 pos-layout">
    <!-- Panel productos -->
    <div class="col-lg-7">
        <div class="card panel h-100">
            <div class="card-header bg-white">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                    <input id="posBuscar" class="form-control form-control-lg"
                           placeholder="Buscar producto por nombre o código…" autofocus>
                </div>
            </div>
            <div class="card-body pos-grid" id="posProductos">
                <div class="text-muted">Cargando productos…</div>
            </div>
        </div>
    </div>

    <!-- Panel carrito -->
    <div class="col-lg-5">
        <div class="card panel h-100 d-flex flex-column">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart3"></i> Carrito</span>
                <button class="btn btn-sm btn-outline-danger" id="btnVaciar"><i class="bi bi-trash"></i> Vaciar</button>
            </div>
            <div class="card-body p-0 flex-grow-1" style="overflow:auto;">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>Producto</th><th class="text-center">Cant.</th>
                        <th class="text-end">Subtotal</th><th></th>
                    </tr></thead>
                    <tbody id="carritoBody">
                        <tr><td colspan="4" class="text-center text-muted py-4">Carrito vacío</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">
                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <select id="posCliente" class="form-select form-select-sm"></select>
                    </div>
                    <div class="col-5">
                        <select id="posMetodo" class="form-select form-select-sm">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="QR">Pago QR</option>
                        </select>
                    </div>
                </div>
                <div class="totales">
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span id="tSubtotal">Q 0.00</span></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Descuento</span>
                        <div class="input-group input-group-sm" style="width:130px;">
                            <span class="input-group-text">Q</span>
                            <input id="tDescuento" type="number" min="0" step="0.01" value="0" class="form-control text-end">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between"><span>IVA (12%)</span><span id="tIva">Q 0.00</span></div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between total-grande"><span>TOTAL</span><span id="tTotal">Q 0.00</span></div>
                </div>
                <button class="btn btn-success btn-lg w-100 mt-2" id="btnCobrar" disabled>
                    <i class="bi bi-check2-circle"></i> Cobrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const IVA = 0.12;
let carrito = [];   // { id, nombre, precio, cantidad, stock }
let productos = [];

async function posCargarProductos() {
    const q = document.getElementById('posBuscar').value;
    const res = await api('/api/productos?q=' + encodeURIComponent(q));
    productos = res.ok ? res.data : [];
    const cont = document.getElementById('posProductos');
    if (!productos.length) { cont.innerHTML = '<div class="text-muted">Sin resultados.</div>'; return; }
    cont.innerHTML = productos.map(p => `
        <button class="pos-item ${p.stock <= 0 ? 'agotado' : ''}" ${p.stock <= 0 ? 'disabled' : ''}
                onclick="agregar(${p.id})">
            <div class="pos-item-nombre">${escapeHtml(p.nombre)}</div>
            <div class="pos-item-precio">${money(p.precio)}</div>
            <div class="pos-item-stock ${p.stock_bajo ? 'text-danger' : 'text-muted'}">Stock: ${p.stock}</div>
        </button>`).join('');
}

async function cargarClientesPOS() {
    const res = await api('/api/clientes');
    const sel = document.getElementById('posCliente');
    sel.innerHTML = (res.ok ? res.data : []).map(c =>
        `<option value="${c.id}">${escapeHtml(c.nombre)} (${escapeHtml(c.nit)})</option>`).join('');
}

function agregar(id) {
    const p = productos.find(x => x.id === id);
    if (!p) return;
    const item = carrito.find(x => x.id === id);
    const enCarrito = item ? item.cantidad : 0;
    if (enCarrito + 1 > p.stock) { toast('No hay más stock disponible.', false); return; }
    if (item) item.cantidad++;
    else carrito.push({ id: p.id, nombre: p.nombre, precio: p.precio, cantidad: 1, stock: p.stock });
    renderCarrito();
}

function cambiarCantidad(id, delta) {
    const item = carrito.find(x => x.id === id);
    if (!item) return;
    const nueva = item.cantidad + delta;
    if (nueva <= 0) { carrito = carrito.filter(x => x.id !== id); }
    else if (nueva > item.stock) { toast('Cantidad supera el stock.', false); return; }
    else item.cantidad = nueva;
    renderCarrito();
}

function renderCarrito() {
    const tbody = document.getElementById('carritoBody');
    if (!carrito.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Carrito vacío</td></tr>';
    } else {
        tbody.innerHTML = carrito.map(i => `
            <tr>
                <td><div>${escapeHtml(i.nombre)}</div><small class="text-muted">${money(i.precio)} c/u</small></td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" onclick="cambiarCantidad(${i.id},-1)">−</button>
                        <span class="btn btn-light disabled">${i.cantidad}</span>
                        <button class="btn btn-outline-secondary" onclick="cambiarCantidad(${i.id},1)">+</button>
                    </div>
                </td>
                <td class="text-end">${money(i.precio * i.cantidad)}</td>
                <td><button class="btn btn-sm btn-link text-danger" onclick="cambiarCantidad(${i.id},-999)"><i class="bi bi-x-lg"></i></button></td>
            </tr>`).join('');
    }
    recalcular();
}

function recalcular() {
    const subtotal = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
    let desc = parseFloat(document.getElementById('tDescuento').value) || 0;
    if (desc > subtotal) { desc = subtotal; document.getElementById('tDescuento').value = desc.toFixed(2); }
    const base = subtotal - desc;
    const iva = base * IVA;
    const total = base + iva;
    document.getElementById('tSubtotal').textContent = money(subtotal);
    document.getElementById('tIva').textContent = money(iva);
    document.getElementById('tTotal').textContent = money(total);
    document.getElementById('btnCobrar').disabled = carrito.length === 0;
}

document.getElementById('tDescuento').addEventListener('input', recalcular);
document.getElementById('btnVaciar').addEventListener('click', () => { carrito = []; renderCarrito(); });

document.getElementById('btnCobrar').addEventListener('click', async () => {
    if (!carrito.length) return;
    const payload = {
        id_cliente: document.getElementById('posCliente').value || null,
        metodo_pago: document.getElementById('posMetodo').value,
        descuento: parseFloat(document.getElementById('tDescuento').value) || 0,
        items: carrito.map(i => ({ id_producto: i.id, cantidad: i.cantidad }))
    };
    const btn = document.getElementById('btnCobrar');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando…';
    const res = await api('/api/ventas', { method: 'POST', body: payload });
    btn.innerHTML = '<i class="bi bi-check2-circle"></i> Cobrar';
    if (res.ok) {
        carrito = []; renderCarrito();
        document.getElementById('tDescuento').value = 0; recalcular();
        posCargarProductos();
        // Abrir factura imprimible (contiene el código QR - API externa)
        window.open(`${BASE_URL}/ventas/${res.data.id}`, '_blank');
        toast('Venta registrada #' + res.data.id, true);
    } else {
        toast(res.mensaje, false);
    }
});

let posDebounce;
document.getElementById('posBuscar').addEventListener('input', () => {
    clearTimeout(posDebounce); posDebounce = setTimeout(posCargarProductos, 300);
});

(async () => { await posCargarProductos(); await cargarClientesPOS(); })();
</script>
