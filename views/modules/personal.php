<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Gestión de Personal</h1>
            <p class="current-date">Administra el registro de docentes, administrativos y obreros.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarPersonal">
                <i class="ph ph-plus"></i> Nuevo Empleado
            </button>
        </div>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header">
            <h2>Personal Activo</h2>
            <div class="widget-filters">
                <input type="text" id="search-input" placeholder="Buscar" aria-label="Buscar personal">
                <select id="filter-depto" aria-label="Filtrar por departamento">
                    <option value="">Todos los departamentos</option>
                    <?php
                    require_once "models/DepartamentoModel.php";
                    $todosDeptos = DepartamentoModel::mdlMostrarDepartamentos("departamentos");
                    foreach ($todosDeptos as $d) {
                        echo '<option value="' . $d["id"] . '">' . htmlspecialchars($d["nombre"], ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre y Apellido</th>
                        <th>Documento (DNI/RUT)</th>
                        <th>Departamento / Cargo</th>
                        <th>Turno</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
                    require_once "models/UsuarioModel.php";
                    require_once "models/TurnoModel.php";

                    $old = isset($_SESSION['form_old']) ? $_SESSION['form_old'] : array();
                    $errors = isset($_SESSION['form_errors']) ? $_SESSION['form_errors'] : array();
                    $old_edit = isset($_SESSION['form_old_edit']) ? $_SESSION['form_old_edit'] : array();
                    $errors_edit = isset($_SESSION['form_errors_edit']) ? $_SESSION['form_errors_edit'] : array();

                    $todosTurnos = TurnoModel::mdlMostrarTurnos("horarios_turnos", true);
                    $personal = UsuarioModel::mdlMostrarUsuario("personal", null, null);

                    foreach ($personal as $key => $value) {
                        $esActivo = $value["id_estado"] == 1;
                        $estadoBadge = $esActivo ? '<span class="status-badge status-green">Activo</span>' : '<span class="status-badge status-red">Inactivo</span>';
                        $avatarSrc = (isset($value["foto"]) && !empty($value["foto"])) ? "foto_perfil/" . $value["foto"] : "https://ui-avatars.com/api/?name=" . urlencode($value["nombre"] . "+" . $value["apellido"]) . "&background=random";
                        $toggleTitle = $esActivo ? 'Dar de Baja' : 'Activar';
                        $toggleIcon = $esActivo ? 'ph-toggle-right text-red' : 'ph-toggle-left text-green';

                        echo '<tr data-departamento="' . $value["id_cargo"] . '">
                            <td>' . ($key + 1) . '</td>
                            <td>
                                <div class="student-cell">
                                    <img src="' . $avatarSrc . '" alt="Avatar" class="avatar-sm">
                                    <span>' . $value["nombre"] . ' ' . $value["apellido"] . '</span>
                                </div>
                            </td>
                            <td>' . $value["documento_identidad"] . '</td>
                            <td>' . (isset($value["nombre_cargo"]) ? htmlspecialchars($value["nombre_cargo"], ENT_QUOTES, 'UTF-8') : '') . '</td>
                            <td>' . (isset($value["nombre_turno"]) ? htmlspecialchars($value["nombre_turno"], ENT_QUOTES, 'UTF-8') : '') . '</td>
                            <td>' . $value["email"] . '</td>
                            <td>' . $estadoBadge . '</td>
                            <td>
                                <button class="btn-icon btn-edit-personal" title="Editar"
                                        data-id="' . $value["id"] . '"
                                        data-nombre="' . $value["nombre"] . '"
                                        data-apellido="' . $value["apellido"] . '"
                                        data-doc="' . $value["documento_identidad"] . '"
                                        data-email="' . $value["email"] . '"
                                        data-cargo="' . $value["id_cargo"] . '"
                                        data-turno="' . $value["id_turno"] . '"
                                        data-toggle="modal" data-target="#modalEditarPersonal">
                                    <i class="ph ph-pencil-simple text-blue"></i>
                                </button>
                                <button class="btn-icon" title="Cargar foto de perfil" data-toggle="modal" data-target="#modalSubirFoto" data-id="' . $value["id"] . '" data-nombre="' . $value["nombre"] . ' ' . $value["apellido"] . '">
                                    <i class="ph ph-camera text-green"></i>
                                </button>
                                <button class="btn-icon btn-registrar-huella" title="' . (!empty($value["sdk_huella"]) ? 'Huella registrada - Volver a enrolar' : 'Registrar huella') . '"
                                        data-id="' . $value["id"] . '"
                                        data-documento="' . $value["documento_identidad"] . '"
                                        data-nombre="' . $value["nombre"] . ' ' . $value["apellido"] . '">
                                    <i class="ph ' . (!empty($value["sdk_huella"]) ? 'ph-fingerprint text-purple' : 'ph-fingerprint') . '"></i>
                                </button>
                                <button class="btn-icon" title="' . $toggleTitle . '" onclick="confirmarInactivacion(' . $value["id"] . ',' . $value["id_estado"] . ')">
                                    <i class="ph ' . $toggleIcon . '"></i>
                                </button>
                            </td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
            <div class="pagination-controls" id="pagination-controls">
            </div>
        </div>
    </div>
</main>

<!-- MODAL AGREGAR PERSONAL -->
<div id="modalAgregarPersonal" class="modal">
    <div class="modal-content">
        <form method="post">
            <?php require_once "helpers/CsrfHelper.php"; echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>A&ntilde;adir Nuevo Empleado</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (!empty($errors)): ?>
                <div class="form-alert">
                    <i class="ph ph-warning-circle"></i>
                    <span><?php echo isset($errors['general']) ? htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') : 'Corrige los errores marcados en el formulario.'; ?></span>
                </div>
                <?php endif; ?>
                <div class="form-row">
                    <div class="input-group">
                        <label>Nombre</label>
                        <div class="input-wrapper">
                            <i class="ph ph-user"></i>
                            <input type="text" name="nuevoNombre" required placeholder="Ingresar Nombre" value="<?php echo isset($old['nuevoNombre']) ? htmlspecialchars($old['nuevoNombre'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <?php if (isset($errors['nuevoNombre'])) { echo '<div class="field-error">' . htmlspecialchars($errors['nuevoNombre'], ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Apellido</label>
                        <div class="input-wrapper">
                            <input type="text" name="nuevoApellido" required placeholder="Ingresar Apellido" value="<?php echo isset($old['nuevoApellido']) ? htmlspecialchars($old['nuevoApellido'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <?php if (isset($errors['nuevoApellido'])) { echo '<div class="field-error">' . htmlspecialchars($errors['nuevoApellido'], ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Documento de Identidad (DNI/RUT)</label>
                        <div class="input-wrapper">
                            <i class="ph ph-identification-card"></i>
                            <input type="text" name="nuevoDocumento" required placeholder="Ej: 12345678" value="<?php echo isset($old['nuevoDocumento']) ? htmlspecialchars($old['nuevoDocumento'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <?php if (isset($errors['nuevoDocumento'])) { echo '<div class="field-error">' . htmlspecialchars($errors['nuevoDocumento'], ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Correo Electr&oacute;nico</label>
                        <div class="input-wrapper">
                            <i class="ph ph-envelope"></i>
                            <input type="email" name="nuevoEmail" required placeholder="correo@ejemplo.com" value="<?php echo isset($old['nuevoEmail']) ? htmlspecialchars($old['nuevoEmail'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <?php if (isset($errors['nuevoEmail'])) { echo '<div class="field-error">' . htmlspecialchars($errors['nuevoEmail'], ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Departamento</label>
                        <div class="input-wrapper">
                            <i class="ph ph-buildings"></i>
                            <select name="nuevoDepartamento" id="nuevoDepartamento" required style="width:100%; padding-left: 2.5rem;">
                                <option value="">Seleccione un departamento</option>
                                <?php
                                require_once "models/DepartamentoModel.php";
                                $deptosList = DepartamentoModel::mdlMostrarDepartamentos("departamentos");
                                foreach ($deptosList as $d) {
                                    $sel = (isset($old['nuevoDepartamento']) && $old['nuevoDepartamento'] == $d['id']) ? ' selected' : '';
                                    echo '<option value="' . $d["id"] . '"' . $sel . '>' . htmlspecialchars($d["nombre"], ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Cargo / Rol</label>
                        <div class="input-wrapper">
                            <i class="ph ph-briefcase"></i>
                            <select name="nuevoCargo" id="nuevoCargo" required style="width:100%; padding-left: 2.5rem;">
                                <option value="">Seleccione el cargo</option>
                                <?php
                                if (isset($old['nuevoDepartamento']) && !empty($old['nuevoDepartamento'])) {
                                    require_once "models/CargoModel.php";
                                    $cargosFiltrados = CargoModel::mdlListarCargos();
                                    foreach ($cargosFiltrados as $c) {
                                        if ((int)$c["id_departamento"] === (int)$old['nuevoDepartamento']) {
                                            $sel = (isset($old['nuevoCargo']) && $old['nuevoCargo'] == $c['id']) ? ' selected' : '';
                                            echo '<option value="' . $c["id"] . '"' . $sel . '>' . htmlspecialchars($c["nombre"], ENT_QUOTES, 'UTF-8') . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Turno / Horario</label>
                        <div class="input-wrapper">
                            <i class="ph ph-clock"></i>
                            <select name="nuevoTurno" id="nuevoTurno" required style="width:100%; padding-left: 2.5rem;">
                                <option value="">Asignar un turno</option>
                                <?php
                                if (isset($old['nuevoCargo']) && !empty($old['nuevoCargo'])) {
                                    foreach ($todosTurnos as $t) {
                                        $cargosIds = TurnoModel::mdlListarIdsCargos($t["id"]);
                                        $mostrar = empty($cargosIds) || in_array((int)$old['nuevoCargo'], $cargosIds);
                                        if ($mostrar) {
                                            $sel = (isset($old['nuevoTurno']) && $old['nuevoTurno'] == $t['id']) ? ' selected' : '';
                                            echo '<option value="' . $t["id"] . '"' . $sel . '>' . htmlspecialchars($t["nombre_turno"], ENT_QUOTES, 'UTF-8') . ' (' . substr($t["hora_entrada"], 0, 5) . ' a ' . substr($t["hora_salida"], 0, 5) . ')</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Empleado</button>
            </div>

            <?php
            require_once "controllers/PersonalController.php";
            PersonalController::ctrCrearPersonal();
            if (!empty($errors)) {
                echo '<script>document.addEventListener("DOMContentLoaded", function(){ document.querySelector("#modalAgregarPersonal").classList.add("show"); });</script>';
                unset($_SESSION['form_old']);
                unset($_SESSION['form_errors']);
            }
            ?>
        </form>
    </div>
</div>

<!-- MODAL EDITAR PERSONAL -->
<div id="modalEditarPersonal" class="modal">
    <div class="modal-content">
        <form method="post">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>Editar Empleado</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idPersonal" id="idPersonal" value="<?php echo isset($old_edit['idPersonal']) ? htmlspecialchars($old_edit['idPersonal'], ENT_QUOTES, 'UTF-8') : ''; ?>">

                <?php if (!empty($errors_edit)): ?>
                <div class="form-alert">
                    <i class="ph ph-warning-circle"></i>
                    <span><?php echo isset($errors_edit['general']) ? htmlspecialchars($errors_edit['general'], ENT_QUOTES, 'UTF-8') : 'Corrige los errores marcados en el formulario.'; ?></span>
                </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="input-group">
                        <label>Nombre</label>
                        <div class="input-wrapper">
                            <i class="ph ph-user"></i>
                            <input type="text" name="editarNombre" id="editarNombre" required value="<?php echo isset($old_edit['editarNombre']) ? htmlspecialchars($old_edit['editarNombre'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <?php if (isset($errors_edit['editarNombre'])) { echo '<div class="field-error">' . htmlspecialchars($errors_edit['editarNombre'], ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Apellido</label>
                        <div class="input-wrapper">
                            <input type="text" name="editarApellido" id="editarApellido" required value="<?php echo isset($old_edit['editarApellido']) ? htmlspecialchars($old_edit['editarApellido'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <?php if (isset($errors_edit['editarApellido'])) { echo '<div class="field-error">' . htmlspecialchars($errors_edit['editarApellido'], ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Documento de Identidad</label>
                        <div class="input-wrapper">
                            <i class="ph ph-identification-card"></i>
                            <input type="text" name="editarDocumento" id="editarDocumento" required value="<?php echo isset($old_edit['editarDocumento']) ? htmlspecialchars($old_edit['editarDocumento'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <?php if (isset($errors_edit['editarDocumento'])) { echo '<div class="field-error">' . htmlspecialchars($errors_edit['editarDocumento'], ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Correo Electr&oacute;nico</label>
                        <div class="input-wrapper">
                            <i class="ph ph-envelope"></i>
                            <input type="email" name="editarEmail" id="editarEmail" required value="<?php echo isset($old_edit['editarEmail']) ? htmlspecialchars($old_edit['editarEmail'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <?php if (isset($errors_edit['editarEmail'])) { echo '<div class="field-error">' . htmlspecialchars($errors_edit['editarEmail'], ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Departamento</label>
                        <div class="input-wrapper">
                            <i class="ph ph-buildings"></i>
                            <select name="editarDepartamento" id="editarDepartamento" required style="width:100%; padding-left: 2.5rem;">
                                <option value="">Seleccione un departamento</option>
                                <?php
                                foreach ($deptosList as $d) {
                                    echo '<option value="' . $d["id"] . '">' . htmlspecialchars($d["nombre"], ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Cargo / Rol</label>
                        <div class="input-wrapper">
                            <i class="ph ph-briefcase"></i>
                            <select name="editarCargo" id="editarCargo" required style="width:100%; padding-left: 2.5rem;">
                                <option value="">Seleccione el cargo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Turno / Horario</label>
                        <div class="input-wrapper">
                            <i class="ph ph-clock"></i>
                            <select name="editarTurno" id="editarTurno" required style="width:100%; padding-left: 2.5rem;">
                                <option value="">Asignar un turno</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="input-group">
                    <label>Contrase&ntilde;a (Opcional - Dejar vac&iacute;o para no cambiarla)</label>
                    <div class="input-wrapper">
                        <i class="ph ph-lock"></i>
                        <input type="password" name="editarPassword" placeholder="Escribe la nueva contrase&ntilde;a">
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>

            <?php
            PersonalController::ctrEditarPersonal();
            if (!empty($errors_edit)) {
                echo '<script>document.addEventListener("DOMContentLoaded", function(){ document.querySelector("#modalEditarPersonal").classList.add("show"); });</script>';
                unset($_SESSION['form_old_edit']);
                unset($_SESSION['form_errors_edit']);
            }
            ?>
        </form>
    </div>
</div>

<!-- MODAL MOTIVO DE BAJA -->
<div id="modalMotivoBaja" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <form method="post" action="index.php?ruta=personal&idEliminar=___ID___&status=___STATUS___">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>Motivo de Baja</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color:var(--text-muted); margin-bottom:1rem;" id="baja-empleado-info">Empleado: <strong></strong></p>
                <div class="input-group">
                    <label>Motivo</label>
                    <div class="input-wrapper">
                        <i class="ph ph-warning-circle"></i>
                        <select name="motivoBaja" id="motivoBaja" required>
                            <option value="">Seleccione un motivo</option>
                            <option value="Mala conducta">Mala conducta</option>
                            <option value="Vacaciones">Vacaciones</option>
                            <option value="Permiso médico">Permiso médico</option>
                            <option value="Falleció">Falleció</option>
                            <option value="Renuncia">Renuncia</option>
                            <option value="Traslado">Traslado</option>
                            <option value="Jubilación">Jubilación</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="input-group">
                    <label>Descripci&oacute;n</label>
                    <div class="input-wrapper">
                        <i class="ph ph-file-text"></i>
                        <textarea name="descripcionBaja" id="descripcionBaja" rows="4" placeholder="Explique el motivo de la baja..." style="width:100%; padding:0.75rem 0.75rem 0.75rem 2.5rem; border:1px solid var(--border-color); border-radius:var(--border-radius-md); background:var(--bg-input); resize:vertical; font-family:inherit;"></textarea>
                    </div>
                </div>
                <div class="input-group">
                    <label>Fecha de Baja</label>
                    <div class="input-wrapper">
                        <i class="ph ph-calendar"></i>
                        <input type="date" name="fechaBaja" id="fechaBaja" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnConfirmarBaja">Confirmar Baja</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SUBIR FOTO -->
<div id="modalSubirFoto" class="modal">
    <div class="modal-content">
        <form method="post" enctype="multipart/form-data">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2>Subir Foto de Perfil</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="fotoPerfil">Seleccionar Foto:</label>
                    <input type="file" id="fotoPerfil" name="fotoPerfil" accept="image/*" required>
                    <div id="previewContainer" style="display: none; margin-top: 10px;">
                        <img id="previewImage" src="" alt="Vista previa" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd;">
                    </div>
                </div>
                <input type="hidden" name="idPersonalFoto" id="idPersonalFoto">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Subir Foto</button>
            </div>

            <?php
            $subirFoto = new PersonalController();
            $subirFoto->ctrSubirFotoPerfil();
            ?>
        </form>
    </div>
</div>

<?php
$borrarPersonal = new PersonalController();
$borrarPersonal->ctrInactivarPersonal();
?>

<script>
<?php
require_once "models/TurnoModel.php";
require_once "models/CargoModel.php";
require_once "models/DepartamentoModel.php";

$cargosJson = json_encode(array_map(function($c) {
    return ['id' => (int)$c['id'], 'nombre' => $c['nombre'], 'id_departamento' => (int)$c['id_departamento']];
}, CargoModel::mdlListarCargos()), JSON_UNESCAPED_UNICODE);

$turnosArr = [];
foreach ($todosTurnos as $t) {
    $cargosIds = TurnoModel::mdlListarIdsCargos($t["id"]);
    $turnosArr[] = [
        'id' => (int)$t["id"],
        'nombre' => $t["nombre_turno"],
        'cargos' => array_map('intval', $cargosIds),
        'hora_entrada' => substr($t["hora_entrada"], 0, 5),
        'hora_salida' => substr($t["hora_salida"], 0, 5)
    ];
}
$turnosJson = json_encode($turnosArr, JSON_UNESCAPED_UNICODE);
?>
var cargosData = <?php echo $cargosJson; ?>;
var turnosData = <?php echo $turnosJson; ?>;

function filtrarCargos(deptoId, cargoSelectId, cargoActual) {
    var select = document.getElementById(cargoSelectId);
    var val = cargoActual || select.value;
    select.innerHTML = '<option value="">Seleccione el cargo</option>';
    cargosData.forEach(function(c) {
        if (c.id_departamento == deptoId) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            if (c.id == val) opt.selected = true;
            select.appendChild(opt);
        }
    });
}

function filtrarTurnos(cargoId, turnoSelectId, turnoActual) {
    var select = document.getElementById(turnoSelectId);
    var val = turnoActual || select.value;
    select.innerHTML = '<option value="">Asignar un turno</option>';
    turnosData.forEach(function(t) {
        var mostrar = !cargoId || t.cargos.length === 0 || t.cargos.indexOf(parseInt(cargoId)) !== -1;
        if (mostrar) {
            var opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.nombre + ' (' + t.hora_entrada + ' a ' + t.hora_salida + ')';
            if (t.id == val) opt.selected = true;
            select.appendChild(opt);
        }
    });
}

function confirmarInactivacion(id, status) {
    var accion = status == 1 ? 'desactivar' : 'activar';
    if (accion == 'activar') {
        if (confirm("¿Estás seguro que deseas activar este usuario?")) {
            window.location = "index.php?ruta=personal&idEliminar=" + id + "&status=" + status + "&csrf_token=<?php echo CsrfHelper::token(); ?>";
        }
        return;
    }
    var modal = document.getElementById('modalMotivoBaja');
    var form = modal.querySelector('form');
    form.action = 'index.php?ruta=personal&idEliminar=' + id + '&status=' + status;
    var csrfInput = form.querySelector('input[name="csrf_token"]');
    if (!csrfInput) {
        csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        form.appendChild(csrfInput);
    }
    csrfInput.value = '<?php echo CsrfHelper::token(); ?>';
    document.getElementById('baja-empleado-info').innerHTML = 'Empleado: <strong>' + getNombreEmpleado(id) + '</strong>';
    document.getElementById('motivoBaja').value = '';
    document.getElementById('descripcionBaja').value = '';
    document.getElementById('fechaBaja').value = new Date().toISOString().split('T')[0];
    modal.classList.add('show');
}

function getNombreEmpleado(id) {
    var btn = document.querySelector('.btn-edit-personal[data-id="' + id + '"]');
    if (btn) return btn.getAttribute('data-nombre') + ' ' + btn.getAttribute('data-apellido');
    return '';
}

function bindCreateCascade() {
    var depto = document.getElementById('nuevoDepartamento');
    if (depto) {
        depto.addEventListener('change', function() {
            filtrarCargos(this.value, 'nuevoCargo', '');
            document.getElementById('nuevoTurno').innerHTML = '<option value="">Asignar un turno</option>';
        });
    }
    var cargo = document.getElementById('nuevoCargo');
    if (cargo) {
        cargo.addEventListener('change', function() {
            filtrarTurnos(this.value, 'nuevoTurno', '');
        });
    }
}

function bindEditCascade() {
    var depto = document.getElementById('editarDepartamento');
    if (depto) {
        depto.addEventListener('change', function() {
            filtrarCargos(this.value, 'editarCargo', '');
            document.getElementById('editarTurno').innerHTML = '<option value="">Asignar un turno</option>';
        });
    }
    var cargo = document.getElementById('editarCargo');
    if (cargo) {
        cargo.addEventListener('change', function() {
            filtrarTurnos(this.value, 'editarTurno', '');
        });
    }
}

var currentPage = 1;
var rowsPerPage = 5;
var filteredRows = [];
var searchTerm = '';
var filterValue = '';

function loadState() {
    searchTerm = localStorage.getItem('personalSearch') || '';
    filterValue = localStorage.getItem('personalFilter') || '';
    currentPage = parseInt(localStorage.getItem('personalPage')) || 1;
    var si = document.getElementById('search-input');
    if (si) si.value = searchTerm;
    var fd = document.getElementById('filter-depto');
    if (fd) fd.value = filterValue;
}
function saveState() {
    localStorage.setItem('personalSearch', searchTerm);
    localStorage.setItem('personalFilter', filterValue);
    localStorage.setItem('personalPage', currentPage);
}
function filterRows() {
    var allRows = Array.from(document.querySelectorAll('tbody tr'));
    filteredRows = allRows.filter(function(row) {
        var depto = row.dataset.departamento;
        var text = row.textContent.toLowerCase();
        var matchesFilter = filterValue === '' || depto === filterValue;
        var matchesSearch = searchTerm === '' || text.includes(searchTerm.toLowerCase());
        return matchesFilter && matchesSearch;
    });
}
function displayPage() {
    var tbody = document.querySelector('tbody');
    var allRows = Array.from(tbody.querySelectorAll('tr'));
    allRows.forEach(function(row) { row.style.display = 'none'; });
    var start = (currentPage - 1) * rowsPerPage;
    var end = start + rowsPerPage;
    var pageRows = filteredRows.slice(start, end);
    pageRows.forEach(function(row) { row.style.display = ''; });
    generatePaginationControls();
}
function generatePaginationControls() {
    var controls = document.getElementById('pagination-controls');
    controls.innerHTML = '';
    var totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    if (totalPages < 1) return;
    var prev = document.createElement('button');
    prev.textContent = 'Anterior';
    prev.className = 'btn btn-outline';
    prev.disabled = currentPage === 1;
    prev.addEventListener('click', function() { if (currentPage > 1) { currentPage--; displayPage(); saveState(); } });
    controls.appendChild(prev);
    for (var i = 1; i <= totalPages; i++) {
        (function(page) {
            var btn = document.createElement('button');
            btn.textContent = page;
            btn.className = 'page-btn';
            if (page === currentPage) btn.classList.add('active');
            btn.addEventListener('click', function() { currentPage = page; displayPage(); saveState(); });
            controls.appendChild(btn);
        })(i);
    }
    var next = document.createElement('button');
    next.textContent = 'Siguiente';
    next.className = 'btn btn-outline';
    next.disabled = currentPage === totalPages;
    next.addEventListener('click', function() { if (currentPage < totalPages) { currentPage++; displayPage(); saveState(); } });
    controls.appendChild(next);
}
function applyFilters() {
    filterRows();
    currentPage = 1;
    displayPage();
    saveState();
}

document.addEventListener('DOMContentLoaded', function() {
    loadState();
    applyFilters();

    var searchInput = document.getElementById('search-input');
    if (searchInput) searchInput.addEventListener('input', function(e) {
        searchTerm = e.target.value.trim();
        applyFilters();
    });

    var filterDepto = document.getElementById('filter-depto');
    if (filterDepto) filterDepto.addEventListener('change', function(e) {
        filterValue = e.target.value;
        applyFilters();
    });

function setFieldError(input, message) {
        var next = input.parentElement.querySelector('.field-error');
        if (!next) {
            next = document.createElement('div');
            next.className = 'field-error';
            input.parentElement.appendChild(next);
        }
        next.textContent = message;
    }
    function clearFieldError(input) {
        var next = input.parentElement.querySelector('.field-error');
        if (next) next.textContent = '';
    }
    function validateName(value) { return /^[A-Za-zÁÉÍÓÚÑáéíóúñ\s]+$/.test(value.trim()); }
    function validateDocument(value) { return /^[0-9]+$/.test(value.trim()); }
    function validateEmail(value) { return /@/.test(value) && value.indexOf('@') > 0; }

    var formCrear = document.querySelector('#modalAgregarPersonal form');
    if (formCrear) {
        formCrear.addEventListener('submit', function(e) {
            var hasError = false;
            var nombre = formCrear.querySelector('[name="nuevoNombre"]');
            var apellido = formCrear.querySelector('[name="nuevoApellido"]');
            var documento = formCrear.querySelector('[name="nuevoDocumento"]');
            var email = formCrear.querySelector('[name="nuevoEmail"]');
            [nombre, apellido, documento, email].forEach(function(el) { if (el) clearFieldError(el); });
            if (!nombre || !validateName(nombre.value)) { setFieldError(nombre, 'El nombre solo debe contener letras y espacios.'); hasError = true; }
            if (!apellido || !validateName(apellido.value)) { setFieldError(apellido, 'El apellido solo debe contener letras y espacios.'); hasError = true; }
            if (!documento || !validateDocument(documento.value)) { setFieldError(documento, 'El documento debe contener solo n&uacute;meros.'); hasError = true; }
            if (!email || !validateEmail(email.value)) { setFieldError(email, 'Introduce un correo v&aacute;lido.'); hasError = true; }
            if (hasError) { e.preventDefault(); return false; }
        });
    }

    var formEditar = document.querySelector('#modalEditarPersonal form');
    if (formEditar) {
        formEditar.addEventListener('submit', function(e) {
            var hasError = false;
            var nombre = formEditar.querySelector('[name="editarNombre"]');
            var apellido = formEditar.querySelector('[name="editarApellido"]');
            var documento = formEditar.querySelector('[name="editarDocumento"]');
            var email = formEditar.querySelector('[name="editarEmail"]');
            [nombre, apellido, documento, email].forEach(function(el) { if (el) clearFieldError(el); });
            if (!nombre || !validateName(nombre.value)) { setFieldError(nombre, 'El nombre solo debe contener letras y espacios.'); hasError = true; }
            if (!apellido || !validateName(apellido.value)) { setFieldError(apellido, 'El apellido solo debe contener letras y espacios.'); hasError = true; }
            if (!documento || !validateDocument(documento.value)) { setFieldError(documento, 'El documento debe contener solo n&uacute;meros.'); hasError = true; }
            if (!email || !validateEmail(email.value)) { setFieldError(email, 'Introduce un correo v&aacute;lido.'); hasError = true; }
            if (hasError) { e.preventDefault(); return false; }
        });
    }

    document.querySelectorAll('.btn-edit-personal').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('idPersonal').value = this.getAttribute('data-id');
            document.getElementById('editarNombre').value = this.getAttribute('data-nombre');
            document.getElementById('editarApellido').value = this.getAttribute('data-apellido');
            document.getElementById('editarDocumento').value = this.getAttribute('data-doc');
            document.getElementById('editarEmail').value = this.getAttribute('data-email');

            var cargoVal = parseInt(this.getAttribute('data-cargo'));
            var turnoVal = parseInt(this.getAttribute('data-turno'));

            var deptoVal = '';
            for (var i = 0; i < cargosData.length; i++) {
                if (cargosData[i].id === cargoVal) {
                    deptoVal = cargosData[i].id_departamento;
                    break;
                }
            }

            document.getElementById('editarDepartamento').value = deptoVal;
            filtrarCargos(deptoVal, 'editarCargo', cargoVal);
            setTimeout(function() {
                document.getElementById('editarCargo').value = cargoVal;
                filtrarTurnos(cargoVal, 'editarTurno', turnoVal);
            }, 30);
        });
    });

document.querySelectorAll('[data-target="#modalSubirFoto"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var nombre = this.getAttribute('data-nombre');
            document.getElementById('idPersonalFoto').value = id;
            document.querySelector('#modalSubirFoto h2').textContent = 'Subir Foto de Perfil - ' + nombre;
        });
    });

    var fp = document.getElementById('fotoPerfil');
    if (fp) {
        fp.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('previewContainer').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('previewContainer').style.display = 'none';
            }
        });

    }
    bindCreateCascade();
    bindEditCascade();

    if (document.querySelector('#modalAgregarPersonal.show')) {
        var savedDepto = <?php echo isset($old['nuevoDepartamento']) ? (int)$old['nuevoDepartamento'] : 0; ?>;
        var savedCargo = <?php echo isset($old['nuevoCargo']) ? (int)$old['nuevoCargo'] : 0; ?>;
        var savedTurno = <?php echo isset($old['nuevoTurno']) ? (int)$old['nuevoTurno'] : 0; ?>;
        if (savedDepto) {
            setTimeout(function() {
                filtrarCargos(savedDepto, 'nuevoCargo', savedCargo);
            }, 30);
        }
        if (savedCargo) {
            setTimeout(function() {
                filtrarTurnos(savedCargo, 'nuevoTurno', savedTurno);
            }, 60);
        }
    }

    if (document.querySelector('#modalEditarPersonal.show')) {
        var editDepto = <?php echo isset($old_edit['editarDepartamento']) ? (int)$old_edit['editarDepartamento'] : 0; ?>;
        var editCargo = <?php echo isset($old_edit['editarCargo']) ? (int)$old_edit['editarCargo'] : 0; ?>;
        var editTurno = <?php echo isset($old_edit['editarTurno']) ? (int)$old_edit['editarTurno'] : 0; ?>;
        if (editDepto) {
            setTimeout(function() {
                filtrarCargos(editDepto, 'editarCargo', editCargo);
            }, 30);
        }
        if (editCargo) {
            setTimeout(function() {
                filtrarTurnos(editCargo, 'editarTurno', editTurno);
            }, 60);
        }
    }

    // Boton registrar huella
    document.querySelectorAll('.btn-registrar-huella').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var nombre = this.getAttribute('data-nombre');
            if (!confirm('Solicitar enrolamiento de huella para: ' + nombre + '?\n\nPon el lector en modo enrolamiento y coloca el dedo 2 veces.')) return;
            var self = this;
            var formData = new FormData();
            formData.append('id_personal', id);
            fetch('index.php?ruta=api_huella_solicitar', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        alert('Solicitud enviada. Coloca el dedo en el lector.');
                        // Recargar para ver el icono actualizado
                        setTimeout(function() { location.reload(); }, 5000);
                    } else {
                        alert('Error: ' + data.mensaje);
                    }
                })
                .catch(function() { alert('Error de conexion'); });
        });
    });
});
</script>
