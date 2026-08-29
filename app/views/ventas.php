<?php /** Historial de ventas con acceso a la factura y anulación (admin). */ ?>
<div class="card panel">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt"></i> Ventas registradas</span>
        <a href="<?= base_url('/pos') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nueva venta</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                <th>#</th><th>Fecha</th><th>Cliente</th><th>Cajero</th>
                <th>Método</th><th class="text-end">Total</th><th>Estado</th><th class="text-end">Acciones</th>
            </tr></thead>
            <tbody id="tablaVentas">
                <tr><td colspan="8" class="text-center text-muted py-4">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function cargarVentas() {
    const res = await api('/api/ventas');
    const tbody = document.getElementById('tablaVentas');
    if (!res.ok || !res.data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Sin ventas.</td></tr>';
        return;
    }
    tbody.innerHTML = res.data.map(v => {
        const estado = v.estado === 'anulada'
            ? '<span class="badge bg-danger">Anulada</span>'
            : '<span class="badge bg-success">Completada</span>';
        const anular = (v.estado !== 'anulada')
            ? `<button class="btn btn-sm btn-outline-danger" data-admin-only onclick="anular(${v.id})"><i class="bi bi-x-circle"></i></button>`
            : '';
        return `<tr>
            <td>#${String(v.id).padStart(6,'0')}</td>
            <td>${escapeHtml(v.fecha)}</td>
            <td>${escapeHtml(v.cliente || 'Consumidor Final')}</td>
            <td>${escapeHtml(v.cajero)}</td>
            <td class="text-capitalize">${escapeHtml(v.metodo_pago)}</td>
            <td class="text-end fw-semibold">${money(v.total)}</td>
            <td>${estado}</td>
            <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="${BASE_URL}/ventas/${v.id}" target="_blank"><i class="bi bi-receipt"></i></a>
                ${anular}
            </td></tr>`;
    }).join('');
    aplicarPermisos();
}

async function anular(id) {
    if (!confirm('¿Anular esta venta? Se devolverá el stock.')) return;
    const res = await api('/api/ventas/' + id + '/anular', { method: 'POST' });
    toast(res.mensaje, res.ok);
    if (res.ok) cargarVentas();
}

cargarVentas();
</script>
