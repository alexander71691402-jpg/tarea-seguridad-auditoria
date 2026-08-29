<?php /** Gestión de usuarios (solo admin). Espera: $usuarios. */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Usuarios con acceso al sistema y sus roles.</p>
    <button class="btn btn-primary" id="btnNuevoUsuario"><i class="bi bi-person-plus"></i> Nuevo usuario</button>
</div>

<div class="card panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                <th>#</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Registrado</th>
            </tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= e($u['nombre']) ?></td>
                    <td><?= e($u['correo']) ?></td>
                    <td>
                        <?php if ($u['rol'] === 'admin'): ?>
                            <span class="badge bg-primary"><i class="bi bi-shield-lock"></i> Administrador</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="bi bi-person"></i> Cajero</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?></td>
                    <td class="text-muted"><?= e($u['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="formUsuario">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre *</label><input id="usNombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Correo *</label><input id="usCorreo" type="email" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Contraseña * (mín. 6)</label><input id="usPass" type="password" class="form-control" minlength="6" required></div>
        <div class="mb-2"><label class="form-label">Rol</label>
            <select id="usRol" class="form-select"><option value="cajero">Cajero</option><option value="admin">Administrador</option></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Crear usuario</button>
      </div>
    </form>
  </div>
</div>

<script>
const modalUs = () => bootstrap.Modal.getOrCreateInstance('#modalUsuario');
document.getElementById('btnNuevoUsuario').addEventListener('click', () => {
    document.getElementById('formUsuario').reset(); modalUs().show();
});
document.getElementById('formUsuario').addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = {
        nombre: document.getElementById('usNombre').value,
        correo: document.getElementById('usCorreo').value,
        password: document.getElementById('usPass').value,
        rol: document.getElementById('usRol').value,
    };
    const res = await api('/api/auth/register', { method: 'POST', body });
    toast(res.mensaje, res.ok);
    if (res.ok) setTimeout(() => location.reload(), 800);
});
</script>
