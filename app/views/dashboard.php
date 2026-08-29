<?php /** Dashboard con KPIs y gráficas (Chart.js). */ ?>
<div class="row g-3 mb-4" id="kpiCards">
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
            <div><div class="kpi-value" id="kpiVentasHoy">Q 0.00</div>
                 <div class="kpi-label">Ventas de hoy</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-success">
            <div class="kpi-icon"><i class="bi bi-calendar-check"></i></div>
            <div><div class="kpi-value" id="kpiVentasMes">Q 0.00</div>
                 <div class="kpi-label">Ventas del mes</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-info">
            <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
            <div><div class="kpi-value" id="kpiProductos">0</div>
                 <div class="kpi-label">Productos activos</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div><div class="kpi-value" id="kpiStockBajo">0</div>
                 <div class="kpi-label">Stock bajo (&lt; 5)</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card panel">
            <div class="card-header bg-white"><i class="bi bi-graph-up-arrow"></i> Ventas de los últimos 7 días</div>
            <div class="card-body"><canvas id="chartDias" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card panel">
            <div class="card-header bg-white"><i class="bi bi-trophy"></i> Productos más vendidos</div>
            <div class="card-body"><canvas id="chartTop" height="180"></canvas></div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card panel">
            <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Ventas por mes (últimos 6 meses)</div>
            <div class="card-body"><canvas id="chartMeses" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card panel">
            <div class="card-header bg-white text-warning"><i class="bi bi-bell"></i> Alertas de inventario</div>
            <ul class="list-group list-group-flush" id="listaStockBajo">
                <li class="list-group-item text-muted">Cargando…</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const res = await api('/api/reportes/dashboard');
    if (!res.ok) return;
    const d = res.data;

    // KPIs
    document.getElementById('kpiVentasHoy').textContent = money(d.kpis.ventas_hoy);
    document.getElementById('kpiVentasMes').textContent = money(d.kpis.ventas_mes);
    document.getElementById('kpiProductos').textContent = d.kpis.total_productos;
    document.getElementById('kpiStockBajo').textContent = d.kpis.stock_bajo;

    // Gráfica: ventas por día
    new Chart(document.getElementById('chartDias'), {
        type: 'line',
        data: {
            labels: d.ventas_por_dia.map(x => x.dia),
            datasets: [{ label: 'Ventas (Q)', data: d.ventas_por_dia.map(x => +x.total),
                borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.15)', fill: true, tension: .35 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // Gráfica: ventas por mes
    new Chart(document.getElementById('chartMeses'), {
        type: 'bar',
        data: {
            labels: d.ventas_por_mes.map(x => x.mes),
            datasets: [{ label: 'Ventas (Q)', data: d.ventas_por_mes.map(x => +x.total),
                backgroundColor: '#10b981' }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // Gráfica: top productos
    new Chart(document.getElementById('chartTop'), {
        type: 'doughnut',
        data: {
            labels: d.top_productos.map(x => x.nombre),
            datasets: [{ data: d.top_productos.map(x => +x.unidades),
                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'] }]
        },
        options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
    });

    // Lista de stock bajo
    const bajos = await api('/api/productos?stock_bajo=1');
    const ul = document.getElementById('listaStockBajo');
    ul.innerHTML = '';
    if (bajos.ok && bajos.data.length) {
        bajos.data.forEach(p => {
            ul.innerHTML += `<li class="list-group-item d-flex justify-content-between align-items-center">
                <span>${escapeHtml(p.nombre)}</span>
                <span class="badge bg-danger rounded-pill">${p.stock} u.</span></li>`;
        });
    } else {
        ul.innerHTML = '<li class="list-group-item text-success"><i class="bi bi-check-circle"></i> Sin alertas de stock.</li>';
    }
});
</script>
