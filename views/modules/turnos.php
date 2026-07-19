<?php
require_once "controllers/TurnoController.php";
require_once "helpers/CsrfHelper.php";

TurnoController::ctrAgregarTurno();
TurnoController::ctrEditarTurno();
TurnoController::ctrCambiarEstadoTurno();

$turnos = TurnoController::ctrListarTurnos();
$cargos = TurnoController::ctrListarCargos();

$cargosIdsPorTurno = [];
foreach ($turnos as $t) {
    $ids = TurnoModel::mdlListarIdsCargos($t["id"]);
    $cargosIdsPorTurno[$t["id"]] = $ids;
}
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Gestión de Horarios</h1>
            <p class="current-date">Administra los turnos y horarios del personal.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarTurno">
                <i class="ph ph-plus"></i> Nuevo Turno
            </button>
        </div>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header">
            <h2>Listado de Turnos</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Turno</th>
                        <th>Hora Entrada</th>
                        <th>Hora Salida</th>
                        <th>Tolerancia</th>
                        <th>Cargos Asociados</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($turnos as $key => $value): ?>
                    <tr>
                        <td><?php echo $key + 1; ?></td>
                        <td><?php echo htmlspecialchars($value["nombre_turno"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo substr($value["hora_entrada"], 0, 5); ?></td>
                        <td><?php echo substr($value["hora_salida"], 0, 5); ?></td>
                        <td><?php echo $value["tolerancia_minutos"]; ?> min</td>
                        <td>
                            <?php
                            $cargosList = $cargosIdsPorTurno[$value["id"]] ?? [];
                            if (empty($cargosList)):
                            ?>
                            <span style="color:var(--text-muted);">General</span>
                            <?php else:
                                $nombres = [];
                                foreach ($cargos as $c) {
                                    if (in_array($c["id"], $cargosList)) $nombres[] = htmlspecialchars($c["nombre"], ENT_QUOTES, 'UTF-8');
                                }
                                echo implode(', ', $nombres);
                            endif; ?>
                        </td>
                        <td>
                            <?php if ($value["estado"] == 1): ?>
                                <span class="status-badge status-green">Activo</span>
                            <?php else: ?>
                                <span class="status-badge status-red">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-icon btn-edit-turno" title="Editar"
                                    data-id="<?php echo $value["id"]; ?>"
                                    data-nombre="<?php echo htmlspecialchars($value["nombre_turno"], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-entrada="<?php echo $value["hora_entrada"]; ?>"
                                    data-salida="<?php echo $value["hora_salida"]; ?>"
                                    data-tolerancia="<?php echo $value["tolerancia_minutos"]; ?>"
                                    data-cargos="<?php echo implode(',', $cargosIdsPorTurno[$value["id"]] ?? []); ?>">
                                <i class="ph ph-pencil-simple text-blue"></i>
                            </button>
                            <a href="index.php?ruta=turnos&id=<?php echo $value["id"]; ?>&estado=<?php echo $value["estado"]; ?>&csrf_token=<?php echo CsrfHelper::token(); ?>" class="btn-icon" title="<?php echo $value["estado"] == 1 ? 'Desactivar' : 'Activar'; ?>" onclick="return confirm('¿<?php echo $value["estado"] == 1 ? 'Desactivar' : 'Activar'; ?> el turno <?php echo htmlspecialchars($value["nombre_turno"], ENT_QUOTES, 'UTF-8'); ?>?')">
                                <?php if ($value["estado"] == 1): ?>
                                    <i class="ph ph-toggle-right text-red"></i>
                                <?php else: ?>
                                    <i class="ph ph-toggle-left text-green"></i>
                                <?php endif; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- MODAL AGREGAR TURNO -->
<div id="modalAgregarTurno" class="modal">
    <div class="modal-content">
        <form method="post">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>Agregar Nuevo Turno</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="input-group">
                        <label>Nombre del Turno</label>
                        <div class="input-wrapper">
                            <i class="ph ph-clock"></i>
                            <input type="text" name="nombre_turno" required placeholder="Ej: Turno Mañana">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label>Hora de Entrada</label>
                        <div class="input-wrapper">
                            <i class="ph ph-arrow-circle-right"></i>
                            <input type="time" name="hora_entrada" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Hora de Salida</label>
                        <div class="input-wrapper">
                            <i class="ph ph-arrow-circle-left"></i>
                            <input type="time" name="hora_salida" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label>Tolerancia (minutos)</label>
                        <div class="input-wrapper">
                            <i class="ph ph-hourglass"></i>
                            <input type="number" name="tolerancia_minutos" value="15" min="0" max="120" required>
                        </div>
                    </div>
                </div>
                <div class="input-group">
                    <label>Cargos Asociados (opcional — dejar vacío para General)</label>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; padding:8px 0;">
                        <?php foreach ($cargos as $c): ?>
                        <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:0.85rem;">
                            <input type="checkbox" name="cargos[]" value="<?php echo $c["id"]; ?>">
                            <?php echo htmlspecialchars($c["nombre"], ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Turno</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR TURNO -->
<div id="modalEditarTurno" class="modal">
    <div class="modal-content">
        <form method="post">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>Editar Turno</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="editar_id" id="editar_id">
                <div class="form-row">
                    <div class="input-group">
                        <label>Nombre del Turno</label>
                        <div class="input-wrapper">
                            <i class="ph ph-clock"></i>
                            <input type="text" name="editar_nombre_turno" id="editar_nombre_turno" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label>Hora de Entrada</label>
                        <div class="input-wrapper">
                            <i class="ph ph-arrow-circle-right"></i>
                            <input type="time" name="editar_hora_entrada" id="editar_hora_entrada" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Hora de Salida</label>
                        <div class="input-wrapper">
                            <i class="ph ph-arrow-circle-left"></i>
                            <input type="time" name="editar_hora_salida" id="editar_hora_salida" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label>Tolerancia (minutos)</label>
                        <div class="input-wrapper">
                            <i class="ph ph-hourglass"></i>
                            <input type="number" name="editar_tolerancia_minutos" id="editar_tolerancia_minutos" min="0" max="120" required>
                        </div>
                    </div>
                </div>
                <div class="input-group">
                    <label>Cargos Asociados (opcional — dejar vacío para General)</label>
                    <div id="editarCargosContainer" style="display:flex; flex-wrap:wrap; gap:8px; padding:8px 0;">
                        <?php foreach ($cargos as $c): ?>
                        <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:0.85rem;">
                            <input type="checkbox" name="editar_cargos[]" value="<?php echo $c["id"]; ?>" class="edit-cargo-check">
                            <?php echo htmlspecialchars($c["nombre"], ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit-turno').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('editar_id').value = this.getAttribute('data-id');
            document.getElementById('editar_nombre_turno').value = this.getAttribute('data-nombre');
            document.getElementById('editar_hora_entrada').value = this.getAttribute('data-entrada');
            document.getElementById('editar_hora_salida').value = this.getAttribute('data-salida');
            document.getElementById('editar_tolerancia_minutos').value = this.getAttribute('data-tolerancia');

            var cargos = (this.getAttribute('data-cargos') || '').split(',').filter(Boolean);
            document.querySelectorAll('.edit-cargo-check').forEach(function(cb) {
                cb.checked = cargos.indexOf(cb.value) !== -1;
            });

            var modal = document.getElementById('modalEditarTurno');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    });
});
</script>
