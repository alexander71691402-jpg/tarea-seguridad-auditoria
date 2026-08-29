<?php /** Inventario de productos: CRUD, búsqueda, alerta de stock, imagen. */ ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div class="input-group" style="max-width: 380px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input id="buscador" class="form-control" placeholder="Buscar por nombre o código…">
    </div>
    <div class="d-flex gap-2">
        <select id="filtroCategoria" class="form-select" style="min-width: 180px;"></select>
        <button class="btn btn-primary" id="btnNuevo" data-admin-only>
            <i class="bi bi-plus-lg"></i> Nuevo producto</button>
    </div>
</div>

<div class="card panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Imagen</th><th>Código</th><th>Producto</th><th>Categoría</th>
                    <th class="text-end">Precio</th><th class="text-center">Stock</th><th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaProductos">
                <tr><td colspan="7" class="text-center text-muted py-4">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Producto -->
<div class="modal fade" id="modalProducto" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" id="formProducto" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-box-seam"></i> <span id="modalTitulo">Nuevo producto</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="prodId">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Código *</label>
            <input name="codigo" id="prodCodigo" class="form-control" required>
          </div>
          <div class="col-md-8">
            <label class="form-label">Nombre *</label>
            <input name="nombre" id="prodNombre" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" id="prodDescripcion" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Precio (Q) *</label>
            <input name="precio" id="prodPrecio" type="number" step="0.01" min="0" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Stock</label>
            <input name="stock" id="prodStock" type="number" min="0" class="form-control" value="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Categoría</label>
            <select name="id_categoria" id="prodCategoria" class="form-select"></select>
          </div>
          <div class="col-12">
            <label class="form-label">Imagen del producto</label>
            <input name="imagen" id="prodImagen" type="file" accept="image/*" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
let categorias = [];
const modal = () => bootstrap.Modal.getOrCreateInstance('#modalProducto');

async function cargarCategorias() {
    const res = await api('/api/categorias');
    categorias = res.ok ? res.data : [];
    const opts = '<option value="">— Sin categoría —</option>' +
        categorias.map(c => `<option value="${c.id}">${escapeHtml(c.nombre)}</option>`).join('');
    document.getElementById('prodCategoria').innerHTML = opts;
    document.getElementById('filtroCategoria').innerHTML =
        '<option value="">Todas las categorías</option>' +
        categorias.map(c => `<option value="${c.id}">${escapeHtml(c.nombre)}</option>`).join('');
}

async function cargarProductos() {
    const q = document.getElementById('buscador').value;
    const cat = document.getElementById('filtroCategoria').value;
    const res = await api(`/api/productos?q=${encodeURIComponent(q)}&categoria=${cat}`);
    const tbody = document.getElementById('tablaProductos');
    if (!res.ok || !res.data.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Sin productos.</td></tr>';
        return;
    }
    tbody.innerHTML = res.data.map(p => {
        const img = p.imagen_url
            ? `<img src="${BASE_URL}/${p.imagen_url}" class="prod-thumb">`
            : `<div class="prod-thumb placeholder-thumb"><i class="bi bi-image"></i></div>`;
        const badge = p.stock_bajo
            ? `<span class="badge bg-danger">${p.stock} <i class="bi bi-exclamation-triangle"></i></span>`
            : `<span class="badge bg-success-subtle text-success-emphasis">${p.stock}</span>`;
        return `<tr>
            <td>${img}</td>
            <td><code>${escapeHtml(p.codigo)}</code></td>
            <td>${escapeHtml(p.nombre)}</td>
            <td>${escapeHtml(p.categoria || '—')}</td>
            <td class="text-end">${money(p.precio)}</td>
            <td class="text-center">${badge}</td>
            <td class="text-end" data-admin-only>
                <button class="btn btn-sm btn-outline-primary" onclick='editar(${p.id})'><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick='eliminar(${p.id}, ${JSON.stringify(p.nombre)})'><i class="bi bi-trash"></i></button>
            </td></tr>`;
    }).join('');
    aplicarPermisos();
}

document.getElementById('btnNuevo').addEventListener('click', () => {
    document.getElementById('formProducto').reset();
    document.getElementById('prodId').value = '';
    document.getElementById('modalTitulo').textContent = 'Nuevo producto';
    modal().show();
});

async function editar(id) {
    const res = await api('/api/productos/' + id);
    if (!res.ok) return;
    const p = res.data;
    document.getElementById('prodId').value = p.id;
    document.getElementById('prodCodigo').value = p.codigo;
    document.getElementById('prodNombre').value = p.nombre;
    document.getElementById('prodDescripcion').value = p.descripcion || '';
    document.getElementById('prodPrecio').value = p.precio;
    document.getElementById('prodStock').value = p.stock;
    document.getElementById('prodCategoria').value = p.id_categoria || '';
    document.getElementById('modalTitulo').textContent = 'Editar producto';
    modal().show();
}

async function eliminar(id, nombre) {
    if (!confirm(`¿Eliminar el producto "${nombre}"?`)) return;
    const res = await api('/api/productos/' + id, { method: 'DELETE' });
    toast(res.mensaje, res.ok);
    if (res.ok) cargarProductos();
}

document.getElementById('formProducto').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('prodId').value;
    const fd = new FormData(e.target);
    // multipart para permitir la imagen; el backend acepta PUT vía _method o POST
    const url = id ? '/api/productos/' + id : '/api/productos';
    const res = await apiForm(url, fd, id ? 'PUT' : 'POST');
    toast(res.mensaje, res.ok);
    if (res.ok) { modal().hide(); cargarProductos(); }
});

let debounce;
document.getElementById('buscador').addEventListener('input', () => {
    clearTimeout(debounce); debounce = setTimeout(cargarProductos, 300);
});
document.getElementById('filtroCategoria').addEventListener('change', cargarProductos);

(async () => { await cargarCategorias(); await cargarProductos(); })();
</script>
