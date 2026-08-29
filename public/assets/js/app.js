/* =====================================================================
 *  app.js  -  Utilidades compartidas del frontend
 *  - Wrapper de la API REST (fetch con JSON)
 *  - Formato de moneda, escape de HTML, notificaciones (toast)
 *  - Control de UI por rol (elementos [data-admin-only])
 * ===================================================================== */

const BASE_URL = window.BASE_URL || '';

/**
 * Cliente de la API REST propia. Devuelve el JSON {ok, mensaje, data}.
 * Envía y recibe JSON, e incluye las cookies de sesión.
 */
async function api(path, { method = 'GET', body = null } = {}) {
    const opts = {
        method,
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
    };
    if (body !== null) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }
    try {
        const res = await fetch(BASE_URL + path, opts);
        if (res.status === 401) {   // sesión expirada
            window.location.href = BASE_URL + '/login';
            return { ok: false, mensaje: 'Sesión expirada' };
        }
        return await res.json();
    } catch (err) {
        return { ok: false, mensaje: 'Error de red: ' + err.message };
    }
}

/**
 * Envío de formularios con archivos (multipart). Usa POST con override
 * de método (_method) para PUT, ya que PHP solo procesa $_FILES en POST.
 */
async function apiForm(path, formData, method = 'POST') {
    if (method !== 'POST') formData.append('_method', method);
    try {
        const res = await fetch(BASE_URL + path, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        if (res.status === 401) { window.location.href = BASE_URL + '/login'; return { ok: false }; }
        return await res.json();
    } catch (err) {
        return { ok: false, mensaje: 'Error de red: ' + err.message };
    }
}

/** Formatea un número como Quetzales. */
function money(n) {
    return 'Q ' + Number(n || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/** Escapa texto para insertarlo en HTML (previene XSS). */
function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, s =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s]));
}

/** Muestra una notificación flotante. */
function toast(mensaje, ok = true) {
    let cont = document.getElementById('toastContainer');
    if (!cont) {
        cont = document.createElement('div');
        cont.id = 'toastContainer';
        cont.className = 'toast-container position-fixed top-0 end-0 p-3';
        cont.style.zIndex = 1090;
        document.body.appendChild(cont);
    }
    const el = document.createElement('div');
    el.className = `toast align-items-center text-white border-0 show ${ok ? 'bg-success' : 'bg-danger'}`;
    el.innerHTML = `<div class="d-flex"><div class="toast-body">
        <i class="bi bi-${ok ? 'check-circle' : 'exclamation-triangle'}"></i> ${escapeHtml(mensaje)}
        </div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    cont.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

/** Oculta los elementos [data-admin-only] si el usuario no es admin. */
function aplicarPermisos() {
    const esAdmin = window.USER_ROL === 'admin';
    document.querySelectorAll('[data-admin-only]').forEach(el => {
        el.style.display = esAdmin ? '' : 'none';
    });
}

/* Reloj del topbar */
function actualizarReloj() {
    const el = document.getElementById('clock');
    if (el) el.textContent = new Date().toLocaleString('es-GT', { dateStyle: 'medium', timeStyle: 'short' });
}

/* Sidebar responsive */
document.addEventListener('DOMContentLoaded', () => {
    aplicarPermisos();
    actualizarReloj();
    setInterval(actualizarReloj, 30000);

    const btn = document.getElementById('btnSidebar');
    if (btn) btn.addEventListener('click', () => document.querySelector('.sidebar')?.classList.toggle('open'));
});
