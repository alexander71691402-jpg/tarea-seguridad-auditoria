<?php /** Reportes: ventas por rango de fechas + productos más vendidos + exportar. */ ?>
<div class="card panel mb-3">
    <div class="card-body">
        <form id="formReporte" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">Desde</label>
                <input type="date" id="repDesde" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-auto">
                <label class="form-label">Hasta</label>
                <input type="date" id="repHasta" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary"><i class="bi bi-funnel"></i> Generar</button>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir / PDF</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3" id="resumenReporte"></div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card panel">
            <div class="card-header bg-white"><i class="bi bi-table"></i> Ventas del período</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr>
                        <th>#</th><th>Fecha</th><th>Cliente</th><th>Método</th><th class="text-end">Total</th>
                    </tr></thead>
                    <tbody id="tablaReporte"><tr><td colspan="5" class="text-center text-muted py-3">Genere un reporte…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card panel">
            <div class="card-header bg-white"><i class="bi bi-trophy"></i> Productos más vendidos</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Producto</th><th class="text-center">Uds.</th><th class="text-end">Ingresos</th></tr></thead>
                    <tbody id="tablaTop"><tr><td colspan="3" class="text-center text-muted py-3">Cargando…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
async function generarReporte(e) {
    if (e) e.preventDefault();
    const desde = document.getElementById('repDesde').value;
    const hasta = document.getElementById('repHasta').value;
    const res = await api(`/api/reportes/ventas?desde=${desde}&hasta=${hasta}`);
    if (!res.ok) return;
    const r = res.data.resumen;
    document.getElementById('resumenReporte').innerHTML = `
        <div class="col-6 col-md-3"><div class="kpi-card kpi-primary"><div class="kpi-icon"><i class="bi bi-receipt"></i></div>
            <div><div class="kpi-value">${r.num}</div><div class="kpi-label">Ventas</div></div></div></div>
        <div class="col-6 col-md-3"><div class="kpi-card kpi-success"><div class="kpi-icon"><i class="bi bi-cash"></i></div>
            <div><div class="kpi-value">${money(r.total)}</div><div class="kpi-label">Total vendido</div></div></div></div>
        <div class="col-6 col-md-3"><div class="kpi-card kpi-info"><div class="kpi-icon"><i class="bi bi-percent"></i></div>
            <div><div class="kpi-value">${money(r.iva)}</div><div class="kpi-label">IVA recaudado</div></div></div></div>
        <div class="col-6 col-md-3"><div class="kpi-card kpi-warning"><div class="kpi-icon"><i class="bi bi-tag"></i></div>
            <div><div class="kpi-value">${money(r.descuento)}</div><div class="kpi-label">Descuentos</div></div></div></div>`;

    const tbody = document.getElementById('tablaReporte');
    tbody.innerHTML = res.data.ventas.length
        ? res.data.ventas.map(v => `<tr>
            <td>#${String(v.id).padStart(6,'0')}</td><td>${escapeHtml(v.fecha)}</td>
            <td>${escapeHtml(v.cliente || 'CF')}</td><td class="text-capitalize">${escapeHtml(v.metodo_pago)}</td>
            <td class="text-end">${money(v.total)}</td></tr>`).join('')
        : '<tr><td colspan="5" class="text-center text-muted py-3">Sin ventas en el período.</td></tr>';
}

async function cargarTop() {
    const res = await api('/api/reportes/productos-vendidos');
    const tbody = document.getElementById('tablaTop');
    tbody.innerHTML = (res.ok && res.data.length)
        ? res.data.map(p => `<tr><td>${escapeHtml(p.nombre)}</td>
            <td class="text-center">${p.unidades}</td><td class="text-end">${money(p.ingresos)}</td></tr>`).join('')
        : '<tr><td colspan="3" class="text-center text-muted py-3">Sin datos.</td></tr>';
}

document.getElementById('formReporte').addEventListener('submit', generarReporte);
generarReporte(); cargarTop();
</script>
