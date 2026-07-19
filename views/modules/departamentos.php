<?php
require_once "controllers/DepartamentoController.php";
require_once "helpers/CsrfHelper.php";

DepartamentoController::ctrCrear();
DepartamentoController::ctrEditar();
DepartamentoController::ctrEliminar();

$departamentos = DepartamentoController::ctrListarDepartamentos();
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Departamentos</h1>
            <p class="current-date">Administra los departamentos de la instituci&oacute;n.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarDepartamento">
                <i class="ph ph-plus"></i> Nuevo Departamento
            </button>
        </div>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header">
            <h2>Listado de Departamentos</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripci&oacute;n</th>
                        <th>Acci&oacute;n</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departamentos as $key => $d): ?>
                    <tr>
                        <td><?php echo $key + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($d["nombre"], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($d["descripcion"] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button class="btn-icon btn-edit-depto" title="Editar"
                                    data-id="<?php echo $d["id"]; ?>"
                                    data-nombre="<?php echo htmlspecialchars($d["nombre"], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($d["descripcion"] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="ph ph-pencil-simple text-blue"></i>
                            </button>
                            <a href="index.php?ruta=departamentos&id=<?php echo $d["id"]; ?>&csrf_token=<?php echo CsrfHelper::token(); ?>" class="btn-icon" title="Eliminar" onclick="return confirm('&iquest;Eliminar el departamento <?php echo htmlspecialchars($d["nombre"], ENT_QUOTES, 'UTF-8'); ?>?')">
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

<!-- MODAL AGREGAR DEPARTAMENTO -->
<div id="modalAgregarDepartamento" class="modal">
    <div class="modal-content">
        <form method="post">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>Agregar Nuevo Departamento</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="input-group">
                    <label>Nombre del Departamento</label>
                    <div class="input-wrapper">
                        <i class="ph ph-buildings"></i>
                        <input type="text" name="nombre_departamento" required placeholder="Ej: Docencia">
                    </div>
                </div>
                <div class="input-group">
                    <label>Descripci&oacute;n</label>
                    <div class="input-wrapper">
                        <i class="ph ph-file-text"></i>
                        <textarea name="descripcion_departamento" rows="3" placeholder="Descripci&oacute;n del departamento (opcional)" style="width:100%; padding:0.7rem 0.7rem 0.7rem 2.5rem; border:1px solid var(--border-color); border-radius:6px; font-family:inherit; resize:vertical;"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Departamento</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR DEPARTAMENTO -->
<div id="modalEditarDepartamento" class="modal">
    <div class="modal-content">
        <form method="post">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>Editar Departamento</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="editar_id" id="editar_id">
                <div class="input-group">
                    <label>Nombre del Departamento</label>
                    <div class="input-wrapper">
                        <i class="ph ph-buildings"></i>
                        <input type="text" name="editar_nombre" id="editar_nombre" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Descripci&oacute;n</label>
                    <div class="input-wrapper">
                        <i class="ph ph-file-text"></i>
                        <textarea name="editar_descripcion" id="editar_descripcion" rows="3" style="width:100%; padding:0.7rem 0.7rem 0.7rem 2.5rem; border:1px solid var(--border-color); border-radius:6px; font-family:inherit; resize:vertical;"></textarea>
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
    document.querySelectorAll('.btn-edit-depto').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('editar_id').value = this.getAttribute('data-id');
            document.getElementById('editar_nombre').value = this.getAttribute('data-nombre');
            document.getElementById('editar_descripcion').value = this.getAttribute('data-descripcion');
            var modal = document.getElementById('modalEditarDepartamento');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    });
});
</script>
