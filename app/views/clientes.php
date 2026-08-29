<?php /** CRUD de clientes. */ ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div class="input-group" style="max-width: 380px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input id="buscarCliente" class="form-control" placeholder="Buscar por nombre o NIT…">
    </div>
    <button class="btn btn-primary" id="btnNuevoCliente"><i class="bi bi-person-plus"></i> Nuevo cliente</button>
</div>

<div class="card panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                <th>Nombre</th><th>NIT</th><th>Correo</th><th>Teléfono</th><th>Dirección</th><th class="text-end">Acciones</th>
            </tr></thead>
            <tbody id="tablaClientes"><tr><td colspan="6" class="text-center text-muted py-4">Cargando…</td></tr></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="formCliente">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person"></i> <span id="tituloCliente">Nuevo cliente</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="cliId">
        <div class="mb-2"><label class="form-label">Nombre *</label><input id="cliNombre" class="form-control" required></div>
        <div class="row g-2">
          <div class="col-6 mb-2"><label class="form-label">NIT</label><input id="cliNit" class="form-control" value="CF"></div>
          <div class="col-6 mb-2"><label class="form-label">Teléfono</label><input id="cliTelefono" class="form-control"></div>
        </div>
        <div class="mb-2"><label class="form-label">Correo</label><input id="cliCorreo" type="email" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Dirección</label><input id="cliDireccion" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
const modalCli = () => bootstrap.Modal.getOrCreateInstance('#modalCliente');
let clientes = [];

async function cargarClientes() {
    const q = document.getElementById('buscarCliente').value;
    const res = await api('/api/clientes?q=' + encodeURIComponent(q));
    clientes = res.ok ? res.data : [];
    const tbody = document.getElementById('tablaClientes');
    if (!clientes.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Sin clientes.</td></tr>'; return; }
    tbody.innerHTML = clientes.map(c => `<tr>
        <td>${escapeHtml(c.nombre)}</td><td>${escapeHtml(c.nit)}</td>
        <td>${escapeHtml(c.correo || '—')}</td><td>${escapeHtml(c.telefono || '—')}</td>
        <td>${escapeHtml(c.direccion || '—')}</td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" onclick="editarCliente(${c.id})"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-outline-danger" data-admin-only onclick='eliminarCliente(${c.id}, ${JSON.stringify(c.nombre)})'><i class="bi bi-trash"></i></button>
        </td></tr>`).join('');
    aplicarPermisos();
}

document.getElementById('btnNuevoCliente').addEventListener('click', () => {
    document.getElementById('formCliente').reset();
    document.getElementById('cliId').value = '';
    document.getElementById('cliNit').value = 'CF';
    document.getElementById('tituloCliente').textContent = 'Nuevo cliente';
    modalCli().show();
});

function editarCliente(id) {
    const c = clientes.find(x => x.id === id); if (!c) return;
    document.getElementById('cliId').value = c.id;
    document.getElementById('cliNombre').value = c.nombre;
    document.getElementById('cliNit').value = c.nit;
    document.getElementById('cliCorreo').value = c.correo || '';
    document.getElementById('cliTelefono').value = c.telefono || '';
    document.getElementById('cliDireccion').value = c.direccion || '';
    document.getElementById('tituloCliente').textContent = 'Editar cliente';
    modalCli().show();
}

async function eliminarCliente(id, nombre) {
    if (!confirm(`¿Eliminar al cliente "${nombre}"?`)) return;
    const res = await api('/api/clientes/' + id, { method: 'DELETE' });
    toast(res.mensaje, res.ok);
    if (res.ok) cargarClientes();
}

document.getElementById('formCliente').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('cliId').value;
    const body = {
        nombre: document.getElementById('cliNombre').value,
        nit: document.getElementById('cliNit').value,
        correo: document.getElementById('cliCorreo').value,
        telefono: document.getElementById('cliTelefono').value,
        direccion: document.getElementById('cliDireccion').value,
    };
    const res = await api(id ? '/api/clientes/' + id : '/api/clientes', { method: id ? 'PUT' : 'POST', body });
    toast(res.mensaje, res.ok);
    if (res.ok) { modalCli().hide(); cargarClientes(); }
});

let dCli;
document.getElementById('buscarCliente').addEventListener('input', () => { clearTimeout(dCli); dCli = setTimeout(cargarClientes, 300); });
cargarClientes();
</script>
