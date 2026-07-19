<?php
require_once "controllers/RolController.php";
$usuarios = RolController::ctrListarUsuariosConRol();
$totalDirector = RolController::ctrContarPorRol(1);
$totalSecretaria = RolController::ctrContarPorRol(2);
$tieneDirector = $totalDirector > 0;
$tieneSecretaria = $totalSecretaria > 0;
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Asignar Roles del Sistema</h1>
            <p class="current-date">Administra los permisos de acceso de los usuarios.</p>
        </div>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
            <h2>Usuarios del Sistema</h2>
            <div style="display:flex; gap:1rem; font-size:0.85rem; flex-wrap:wrap;">
                <span style="display:flex; align-items:center; gap:4px;">
                    <span class="status-badge" style="background:#0284c7; color:#fff; padding:2px 8px; font-size:0.75rem;">Director</span>
                    <strong><?php echo $totalDirector; ?></strong>
                </span>
                <span style="display:flex; align-items:center; gap:4px;">
                    <span class="status-badge" style="background:#7c3aed; color:#fff; padding:2px 8px; font-size:0.75rem;">Secretaria</span>
                    <strong><?php echo $totalSecretaria; ?></strong>
                </span>
                <span style="display:flex; align-items:center; gap:4px;">
                    <span class="status-badge" style="background:#6b7280; color:#fff; padding:2px 8px; font-size:0.75rem;">Personal</span>
                    <strong><?php echo count($usuarios) - $totalDirector - $totalSecretaria; ?></strong>
                </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="tabla-roles">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre y Apellido</th>
                        <th>Documento</th>
                        <th>Cargo</th>
                        <th>Rol Actual</th>
                        <th>Cambiar Rol</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($usuarios) === 0): ?>
                    <tr><td colspan="6" style="text-align:center; padding:2rem;">No hay usuarios registrados.</td></tr>
                    <?php else: $i=1; ?>
                    <?php foreach ($usuarios as $u): ?>
                    <?php
                        $uid = (int)$u['id'];
                        $urol = (int)$u['id_rol'];
                    ?>
                    <tr id="row-<?php echo $uid; ?>">
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['documento_identidad'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['nombre_cargo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php
                            $badge = match ($urol) {
                                1 => 'style="background:#0284c7; color:#fff; padding:2px 10px; border-radius:12px; font-size:0.8rem;"',
                                2 => 'style="background:#7c3aed; color:#fff; padding:2px 10px; border-radius:12px; font-size:0.8rem;"',
                                default => 'style="background:#6b7280; color:#fff; padding:2px 10px; border-radius:12px; font-size:0.8rem;"'
                            };
                            ?>
                            <span <?php echo $badge; ?>><?php echo htmlspecialchars($u['rol_nombre'] ?? 'Personal', ENT_QUOTES, 'UTF-8'); ?></span>
                        </td>
                        <td>
                            <div class="btn-group" style="display:flex; gap:4px; flex-wrap:wrap;">
                                <!-- Director button -->
                                <?php if ($urol === 1): ?>
                                    <button class="btn btn-sm btn-primary btn-rol" data-id="<?php echo $uid; ?>" data-rol="3" data-action="quitar" title="Quitar Director (pasará a Personal)" style="background:#0284c7; border-color:#0284c7;">
                                        Director
                                    </button>
                                <?php elseif ($tieneDirector): ?>
                                    <button class="btn btn-sm btn-outline" disabled title="Ya hay un Director asignado">
                                        Director
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline btn-rol" data-id="<?php echo $uid; ?>" data-rol="1" data-action="asignar" title="Asignar como Director">
                                        Director
                                    </button>
                                <?php endif; ?>

                                <!-- Secretaria button -->
                                <?php if ($urol === 2): ?>
                                    <button class="btn btn-sm btn-primary btn-rol" data-id="<?php echo $uid; ?>" data-rol="3" data-action="quitar" title="Quitar Secretaria (pasará a Personal)" style="background:#7c3aed; border-color:#7c3aed;">
                                        Secretaria
                                    </button>
                                <?php elseif ($tieneSecretaria): ?>
                                    <button class="btn btn-sm btn-outline" disabled title="Ya hay una Secretaria asignada">
                                        Secretaria
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline btn-rol" data-id="<?php echo $uid; ?>" data-rol="2" data-action="asignar" title="Asignar como Secretaria">
                                        Secretaria
                                    </button>
                                <?php endif; ?>

                                <!-- Personal button -->
                                <?php if ($urol === 3): ?>
                                    <button class="btn btn-sm btn-primary btn-rol" data-id="<?php echo $uid; ?>" data-rol="3" data-action="ninguna" disabled style="background:#6b7280; border-color:#6b7280;">
                                        Personal
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline btn-rol" data-id="<?php echo $uid; ?>" data-rol="3" data-action="asignar" title="Asignar como Personal">
                                        Personal
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="modalRolConfirm" class="modal">
    <div class="modal-content" style="max-width: 420px;">
        <div class="modal-header">
            <h2>Confirmar Cambio de Rol</h2>
            <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body" style="text-align:center; padding:2rem;">
            <p id="rolConfirmMsg" style="font-size:1.1rem; margin-bottom:1.5rem;"></p>
            <div style="display:flex; gap:0.75rem; justify-content:center;">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarRol">Sí, Cambiar Rol</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var pendingId = null;
    var pendingRol = null;
    var rolNames = {1: 'Director', 2: 'Secretaria', 3: 'Personal'};

    document.querySelectorAll('.btn-rol:not([disabled])').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var rol = parseInt(this.getAttribute('data-rol'));
            var nombre = this.closest('tr').querySelector('td:nth-child(2)').textContent.trim();
            var action = this.getAttribute('data-action');
            pendingId = id;
            pendingRol = rol;

            var msg = document.getElementById('rolConfirmMsg');
            if (action === 'quitar') {
                msg.textContent = '¿Quitar rol especial a ' + nombre + '? Pasará a ser Personal.';
            } else {
                msg.textContent = '¿Asignar rol "' + rolNames[rol] + '" a ' + nombre + '?';
            }

            document.getElementById('modalRolConfirm').classList.add('show');
        });
    });

    document.getElementById('btnConfirmarRol').addEventListener('click', function() {
        if (!pendingId || !pendingRol) return;

        var formData = new FormData();
        formData.append('id_personal', pendingId);
        formData.append('id_rol', pendingRol);

        fetch('index.php?ruta=asignar_rol_ajax', { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(function(err) {
            alert('Error de comunicación: ' + err);
        });
    });

    document.querySelectorAll('[data-dismiss="modal"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var modal = this.closest('.modal');
            if (modal) modal.classList.remove('show');
        });
    });
});
</script>
