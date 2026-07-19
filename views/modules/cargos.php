<?php
require_once "controllers/CargoController.php";
require_once "helpers/CsrfHelper.php";

CargoController::ctrCrear();
CargoController::ctrEditar();
CargoController::ctrEliminar();

$cargos = CargoController::ctrListarCargos();
$departamentos = CargoController::ctrListarDepartamentos();
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Cargos / Roles</h1>
            <p class="current-date">Administra los cargos y sus departamentos asociados.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarCargo">
                <i class="ph ph-plus"></i> Nuevo Cargo
            </button>
        </div>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header">
            <h2>Listado de Cargos</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Departamento</th>
                        <th>Personal Asignado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cargos as $key => $c): ?>
                    <tr>
                        <td><?php echo $key + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($c["nombre"], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($c["nombre_departamento"] ?? 'Sin departamento', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ($c["total_personal"] > 0): ?>
                            <span style="display:inline-block; background:#0284c7; color:#fff; padding:2px 10px; border-radius:12px; font-size:0.75rem;"><?php echo $c["total_personal"]; ?> persona(s)</span>
                            <?php else: ?>
                            <span style="color:var(--text-muted); font-size:0.85rem;">Vacío</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-icon btn-edit-cargo" title="Editar"
                                    data-id="<?php echo $c["id"]; ?>"
                                    data-nombre="<?php echo htmlspecialchars($c["nombre"], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-departamento="<?php echo $c["id_departamento"]; ?>">
                                <i class="ph ph-pencil-simple text-blue"></i>
                            </button>
                            <a href="index.php?ruta=cargos&id=<?php echo $c["id"]; ?>&csrf_token=<?php echo CsrfHelper::token(); ?>" class="btn-icon" title="Eliminar" onclick="return confirm('¿Eliminar el cargo <?php echo htmlspecialchars($c["nombre"], ENT_QUOTES, 'UTF-8'); ?>?')">
                                <i class="ph ph-trash text-red"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- MODAL AGREGAR CARGO -->
<div id="modalAgregarCargo" class="modal">
    <div class="modal-content">
        <form method="post">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>Agregar Nuevo Cargo</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="input-group">
                    <label>Nombre del Cargo</label>
                    <div class="input-wrapper">
                        <i class="ph ph-briefcase"></i>
                        <input type="text" name="nombre_cargo" required placeholder="Ej: Profesor Titular">
                    </div>
                </div>
                <div class="input-group">
                    <label>Departamento</label>
                    <div class="input-wrapper">
                        <i class="ph ph-buildings"></i>
                        <select name="id_departamento" required style="width:100%; padding-left:2.5rem;">
                            <option value="">Seleccione un departamento</option>
                            <?php foreach ($departamentos as $d): ?>
                            <option value="<?php echo $d["id"]; ?>"><?php echo htmlspecialchars($d["nombre"], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cargo</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR CARGO -->
<div id="modalEditarCargo" class="modal">
    <div class="modal-content">
        <form method="post">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>Editar Cargo</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="editar_id" id="editar_id">
                <div class="input-group">
                    <label>Nombre del Cargo</label>
                    <div class="input-wrapper">
                        <i class="ph ph-briefcase"></i>
                        <input type="text" name="editar_nombre" id="editar_nombre" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Departamento</label>
                    <div class="input-wrapper">
                        <i class="ph ph-buildings"></i>
                        <select name="editar_id_departamento" id="editar_id_departamento" required style="width:100%; padding-left:2.5rem;">
                            <?php foreach ($departamentos as $d): ?>
                            <option value="<?php echo $d["id"]; ?>"><?php echo htmlspecialchars($d["nombre"], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
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
    document.querySelectorAll('.btn-edit-cargo').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('editar_id').value = this.getAttribute('data-id');
            document.getElementById('editar_nombre').value = this.getAttribute('data-nombre');
            document.getElementById('editar_id_departamento').value = this.getAttribute('data-departamento');
            var modal = document.getElementById('modalEditarCargo');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    });
});
</script>
