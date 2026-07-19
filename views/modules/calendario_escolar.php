<?php
require_once "controllers/CalendarioController.php";
require_once "helpers/CsrfHelper.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$esPersonalCal = ($_SESSION["id_rol"] ?? 0) === 3;

if (!$esPersonalCal) {
        CalendarioController::ctrGuardarDias();
    CalendarioController::ctrGuardarVacaciones();
    CalendarioController::ctrEliminarDia();
    CalendarioController::ctrEliminarDias();
    CalendarioController::ctrGuardarTarea();
    CalendarioController::ctrEditarTarea();
    CalendarioController::ctrEliminarTarea();
}

$anio = isset($_GET["anio"]) ? (int)$_GET["anio"] : (int)date("Y");
$mes = isset($_GET["mes"]) ? (int)$_GET["mes"] : (int)date("n");
$diasConfig = CalendarioController::ctrListarDias($anio, $mes);

$diasConfigMap = [];
$tareasPorDia = [];
foreach ($diasConfig as $dc) {
    $diasConfigMap[$dc["dia"]] = $dc;
    $tareasDia = CalendarioModel::mdlObtenerTareasPorCalendario($dc["id"]);
    if ($tareasDia) {
        $tareasPorDia[$dc["dia"]] = $tareasDia;
    }
}

$tareasDelMes = CalendarioModel::mdlListarTareasPorMes($anio, $mes);

$primerDia = (int)date("w", mktime(0, 0, 0, $mes, 1, $anio));
$ultimoDia = (int)date("t", mktime(0, 0, 0, $mes, 1, $anio));
$nombreMes = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"][$mes - 1];

$coloresPorTipo = ["laboral" => "#22c55e", "feriado" => "#4169E1", "vacaciones" => "#f59e0b", "no_laborable" => "#a855f7"];
?>

<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Calendario Escolar</h1>
            <p class="current-date"><?php echo $esPersonalCal ? "Visualice los días laborables, feriados, vacaciones y tareas del calendario escolar." : "Configure los días laborables, feriados, vacaciones y no laborables del calendario escolar."; ?></p>
        </div>
        <div class="header-actions">
            <a href="index.php?ruta=gestion_calendario&anio=<?php echo $anio; ?>&mes=<?php echo max(1, $mes - 1); ?>" class="btn btn-outline"><i class="ph ph-caret-left"></i> Mes Ant.</a>
            <a href="index.php?ruta=gestion_calendario&anio=<?php echo date("Y"); ?>&mes=<?php echo (int)date("n"); ?>" class="btn btn-outline"><i class="ph ph-calendar"></i> Hoy</a>
            <a href="index.php?ruta=gestion_calendario&anio=<?php echo $anio; ?>&mes=<?php echo min(12, $mes + 1); ?>" class="btn btn-outline">Mes Sig. <i class="ph ph-caret-right"></i></a>
        </div>
    </div>

    <div class="cal-grid-container">
        <div class="cal-selector">
            <form method="get" class="cal-selector-form" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                <input type="hidden" name="ruta" value="gestion_calendario">
                <label style="font-weight:500;">Anio:
                    <select name="anio" style="padding:0.4rem 0.6rem; border:1px solid var(--border-color); border-radius:var(--border-radius-sm); background:var(--bg-input); color:var(--text-main);">
                        <?php for ($y = date("Y") - 2; $y <= date("Y") + 2; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $anio ? "selected" : ""; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label style="font-weight:500;">Mes:
                    <select name="mes" style="padding:0.4rem 0.6rem; border:1px solid var(--border-color); border-radius:var(--border-radius-sm); background:var(--bg-input); color:var(--text-main);">
                        <?php foreach (["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"] as $i => $nm): ?>
                        <option value="<?php echo $i + 1; ?>" <?php echo $i + 1 === $mes ? "selected" : ""; ?>><?php echo $nm; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="btn btn-primary" style="padding:0.4rem 1rem;">Cargar</button>
            </form>
        </div>

        <div class="cal-manage-grid">
            <div class="widget cal-visual">
                <div class="widget-header">
                    <h2><?php echo $nombreMes . " " . $anio; ?></h2>
                    <div style="display:flex; gap:0.75rem; font-size:0.75rem; flex-wrap:wrap;">
                        <span><span class="cal-legend" style="background:#10b981;"></span> Laboral</span>
                        <span><span class="cal-legend" style="background:#4169E1;"></span> Feriado</span>
                        <span><span class="cal-legend" style="background:#f59e0b;"></span> Vacaciones</span>
                        <span><span class="cal-legend" style="background:#a855f7;"></span> No Laborable</span>
                    </div>
                </div>
                <div class="calendar-body">
                    <div class="cal-grid">
                        <div class="cal-dias-semana">
                            <?php foreach (["Dom","Lun","Mar","Mie","Jue","Vie","Sab"] as $ds): ?>
                            <div class="cal-dia-header"><?php echo $ds; ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="cal-dias" id="cal-dias-container">
                            <?php for ($i = 0; $i < $primerDia; $i++): ?>
                            <div class="cal-dia cal-vacio"></div>
                            <?php endfor; ?>
                            <?php for ($d = 1; $d <= $ultimoDia; $d++):
                                $diaSem = (int)date("w", mktime(0, 0, 0, $mes, $d, $anio));
                                $esFinde = ($diaSem === 0 || $diaSem === 6);
                                $config = $diasConfigMap[$d] ?? null;
                                $esHoy = ($d === (int)date("j") && $mes === (int)date("n") && $anio === (int)date("Y"));

                                $tipoCls = "cal-sin-config";
                                $bgColor = "";
                                $tooltip = "";
                                if ($config) {
                                    $tipoCls = "cal-" . $config["tipo"];
                                    $bgColor = $config["color"] ?? $coloresPorTipo[$config["tipo"]];
                                    $tooltip = htmlspecialchars($config["descripcion"] ? ucfirst($config["tipo"]) . ": " . $config["descripcion"] : ucfirst($config["tipo"]), ENT_QUOTES, "UTF-8");
                                } elseif ($esFinde) {
                                    $tipoCls = "cal-finde";
                                    $bgColor = "#ef4444";
                                    $tooltip = "Fin de semana";
                                } else {
                                    $tooltip = "Dia sin configurar";
                                }
                            ?>
                            <div class="cal-dia <?php echo $tipoCls . ($esHoy ? " cal-hoy" : ""); ?>"
                                 data-dia="<?php echo $d; ?>"
                                 title="<?php echo $tooltip; ?>"
                                 style="<?php echo $bgColor ? "background:{$bgColor};color:#fff;" : ""; ?>"
                                 <?php if (!$esPersonalCal): ?>onclick="toggleDia(<?php echo $d; ?>)"<?php endif; ?>>
                                <?php echo $d; ?>
                                <?php if ($config && isset($tareasPorDia[$d])): ?>
                                <span class="cal-tarea-mark" title="<?php echo count($tareasPorDia[$d]) . " tarea(s)"; ?>"><i class="ph ph-check-circle"></i></span>
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$esPersonalCal): ?>
            <div class="cal-actions-panel">
                <div class="widget">
                    <div class="widget-header"><h2>Acciones Masivas</h2></div>
                    <div class="cal-actions-body">
                        <p class="cal-hint">Seleccione dias en el calendario o escriba los numeros abajo.</p>

                        <div class="input-group">
                            <label>Dias seleccionados</label>
                            <div class="input-wrapper">
                                <i class="ph ph-hash"></i>
                                <input type="text" id="dias-seleccionados" value="" placeholder="Ej: 1,2,3,4,5" readonly style="background:var(--bg-input); cursor:default;">
                            </div>
                            <small style="color:var(--text-muted); font-size:0.7rem;">Haga clic en los dias del calendario para seleccionar/deseleccionar</small>
                        </div>

                        <form method="post" id="form-guardar-dias" style="margin-top:0.5rem;">
                            <?php echo CsrfHelper::field(); ?>
                            <input type="hidden" name="guardar_dias" value="1">
                            <input type="hidden" name="anio" value="<?php echo $anio; ?>">
                            <input type="hidden" name="mes" value="<?php echo $mes; ?>">
                            <input type="hidden" name="dias" id="input-dias" value="">
                            <input type="hidden" name="descripcion" id="input-descripcion" value="">

                            <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; margin-bottom:0.75rem;">
                                <button type="submit" class="btn btn-sm" name="tipo" value="laboral" style="background:#22c55e; color:#fff;" onclick="return confirmarAccion('laboral')">Laboral</button>
                                <button type="submit" class="btn btn-sm" name="tipo" value="feriado" style="background:#4169E1; color:#fff;" onclick="return confirmarAccion('feriado')">Feriado</button>
                                <button type="submit" class="btn btn-sm" name="tipo" value="no_laborable" style="background:#a855f7; color:#fff;" onclick="return confirmarAccion('no laborable')">No Laborable</button>
                            </div>

                            <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                                <label style="font-size:0.75rem;">Color:</label>
                                <input type="color" name="color" id="picker-color" value="#22c55e" style="width:40px; height:30px; border:none; cursor:pointer;">
                                <label style="font-size:0.75rem;">Descripcion:</label>
                                <input type="text" name="desc_rapida" id="desc-rapida" placeholder="Opcional" style="flex:1; min-width:120px; padding:0.3rem 0.5rem; border:1px solid var(--border-color); border-radius:var(--border-radius-sm); background:var(--bg-input); color:var(--text-main);">
                            </div>
                        </form>
                    </div>
                </div>

                <div class="widget" style="margin-top:1rem;">
                    <div class="widget-header"><h2>Vacaciones</h2></div>
                    <div class="cal-actions-body">
                        <form method="post">
                            <?php echo CsrfHelper::field(); ?>
                            <input type="hidden" name="guardar_vacaciones" value="1">
                            <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                                <label style="font-size:0.75rem;">Inicio:</label>
                                <input type="date" name="fecha_inicio" id="vac-inicio" required style="padding:0.3rem 0.5rem; border:1px solid var(--border-color); border-radius:var(--border-radius-sm); background:var(--bg-input); color:var(--text-main);" onchange="document.getElementById('vac-fin').min=this.value">
                                <label style="font-size:0.75rem;">Fin:</label>
                                <input type="date" name="fecha_fin" id="vac-fin" required style="padding:0.3rem 0.5rem; border:1px solid var(--border-color); border-radius:var(--border-radius-sm); background:var(--bg-input); color:var(--text-main);">
                                <label style="font-size:0.75rem;">Color:</label>
                                <input type="color" name="color_vac" value="#f59e0b" style="width:40px; height:30px; border:none; cursor:pointer;">
                            </div>
                            <div style="display:flex; gap:0.5rem; margin-top:0.5rem; align-items:center;">
                                <input type="text" name="desc_vacaciones" placeholder="Descripcion (ej: Vacaciones de invierno)" style="flex:1; padding:0.3rem 0.5rem; border:1px solid var(--border-color); border-radius:var(--border-radius-sm); background:var(--bg-input); color:var(--text-main);">
                                <button type="submit" class="btn btn-sm" style="background:#f59e0b; color:#fff;">Guardar Vacaciones</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="cal-bottom-grid">
        <div class="widget widget-lg table-widget">
            <div class="widget-header">
                <h2>Dias Configurados - <?php echo $nombreMes . " " . $anio; ?></h2>
                <?php if (!$esPersonalCal): ?>
                <form method="post" id="form-eliminar-dias" onsubmit="return eliminarSeleccionados()">
                    <?php echo CsrfHelper::field(); ?>
                    <input type="hidden" name="eliminar_dias" value="1">
                    <input type="hidden" name="anio" value="<?php echo $anio; ?>">
                    <input type="hidden" name="mes" value="<?php echo $mes; ?>">
                    <button type="submit" class="btn btn-sm btn-outline text-red" style="border-color:var(--clr-red-light);">
                        <i class="ph ph-trash"></i> Eliminar seleccionados
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if (!$esPersonalCal): ?>
                            <th style="width:40px;"><input type="checkbox" id="check-todos" onchange="toggleTodos(this)"></th>
                            <?php endif; ?>
                            <th>Dia</th>
                            <th>Tipo</th>
                            <th>Color</th>
                            <th>Descripcion</th>
                            <?php if (!$esPersonalCal): ?>
                            <th>Accion</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($diasConfig)): ?>
                        <tr><td colspan="<?php echo $esPersonalCal ? 4 : 6; ?>" style="text-align:center; padding:2rem; color:var(--text-muted);">No hay dias configurados para este mes.</td></tr>
                        <?php else: ?>
                        <?php foreach ($diasConfig as $dc):
                            $etiqueta = ["laboral" => "Laboral", "feriado" => "Feriado", "vacaciones" => "Vacaciones", "no_laborable" => "No Laborable"];
                        ?>
                        <tr>
                            <?php if (!$esPersonalCal): ?>
                            <td><input type="checkbox" name="ids[]" value="<?php echo $dc["id"]; ?>" class="check-dia"></td>
                            <?php endif; ?>
                            <td><strong><?php echo $dc["dia"]; ?></strong></td>
                            <td><?php echo $etiqueta[$dc["tipo"]] ?? $dc["tipo"]; ?></td>
                            <td><span style="display:inline-block; width:24px; height:16px; border-radius:3px; background:<?php echo $dc["color"] ?? "#ccc"; ?>; vertical-align:middle;"></span></td>
                            <td><?php echo htmlspecialchars($dc["descripcion"] ?? "", ENT_QUOTES, "UTF-8"); ?></td>
                            <?php if (!$esPersonalCal): ?>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Eliminar configuracion del dia <?php echo $dc["dia"]; ?>?')">
                                    <?php echo CsrfHelper::field(); ?>
                                    <input type="hidden" name="eliminar_dia" value="1">
                                    <input type="hidden" name="id" value="<?php echo $dc["id"]; ?>">
                                    <input type="hidden" name="anio" value="<?php echo $anio; ?>">
                                    <input type="hidden" name="mes" value="<?php echo $mes; ?>">
                                    <button type="submit" class="btn-icon" title="Eliminar"><i class="ph ph-trash text-red"></i></button>
                                </form>
                                <button class="btn-icon" title="Agregar tarea" onclick="abrirTarea(<?php echo $dc["id"]; ?>, <?php echo $dc["dia"]; ?>)"><i class="ph ph-plus-circle text-blue"></i></button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="widget widget-lg table-widget">
            <div class="widget-header"><h2>Tareas del Mes</h2></div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Hora</th>
                            <th>Titulo</th>
                            <th>Descripcion</th>
                            <th>Creado por</th>
                            <?php if (!$esPersonalCal): ?>
                            <th>Accion</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tareasDelMes)): ?>
                        <tr><td colspan="<?php echo $esPersonalCal ? 5 : 6; ?>" style="text-align:center; padding:2rem; color:var(--text-muted);">No hay tareas para este mes.</td></tr>
                        <?php else: ?>
                        <?php foreach ($tareasDelMes as $t): ?>
                        <tr>
                            <td><strong><?php echo $t["dia"]; ?></strong></td>
                            <td><?php echo $t["hora"] ? substr($t["hora"], 0, 5) : "--:--"; ?></td>
                            <td><?php echo htmlspecialchars($t["titulo"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td><?php echo htmlspecialchars($t["descripcion"] ?? "", ENT_QUOTES, "UTF-8"); ?></td>
                            <td><?php echo htmlspecialchars(($t["creador_nombre"] ?? "") . " " . ($t["creador_apellido"] ?? ""), ENT_QUOTES, "UTF-8"); ?></td>
                            <?php if (!$esPersonalCal): ?>
                            <td>
                                <button class="btn-icon" title="Editar tarea" onclick="editarTarea(<?php echo $t["id"]; ?>, '<?php echo htmlspecialchars($t["titulo"], ENT_QUOTES, "UTF-8"); ?>', '<?php echo htmlspecialchars($t["descripcion"] ?? "", ENT_QUOTES, "UTF-8"); ?>', '<?php echo $t["hora"] ? substr($t["hora"], 0, 5) : ""; ?>')"><i class="ph ph-pencil text-blue"></i></button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Eliminar tarea?')">
                                    <?php echo CsrfHelper::field(); ?>
                                    <input type="hidden" name="eliminar_tarea" value="1">
                                    <input type="hidden" name="id" value="<?php echo $t["id"]; ?>">
                                    <input type="hidden" name="anio" value="<?php echo $anio; ?>">
                                    <input type="hidden" name="mes" value="<?php echo $mes; ?>">
                                    <button type="submit" class="btn-icon" title="Eliminar"><i class="ph ph-trash text-red"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php if (!$esPersonalCal): ?>
<!-- Modal Agregar/Editar Tarea -->
<div id="modalTarea" class="modal">
    <div class="modal-content" style="max-width:450px;">
        <form method="post" id="form-tarea">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-header">
                <h2 id="modal-tarea-title">Agregar Tarea</h2>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="guardar_tarea" id="modal-action-guardar" value="1">
                <input type="hidden" name="editar_tarea" id="modal-action-editar" value="">
                <input type="hidden" name="id_tarea" id="modal-id-tarea" value="">
                <input type="hidden" name="id_calendario" id="modal-id-calendario" value="">
                <input type="hidden" name="anio" value="<?php echo $anio; ?>">
                <input type="hidden" name="mes" value="<?php echo $mes; ?>">
                <p id="modal-tarea-dia" style="font-weight:600; margin-bottom:1rem;"></p>
                <div class="input-group">
                    <label>Titulo</label>
                    <div class="input-wrapper">
                        <i class="ph ph-tag"></i>
                        <input type="text" name="titulo_tarea" id="modal-tarea-titulo" required placeholder="Ej: Reunion de personal">
                    </div>
                </div>
                <div class="input-group">
                    <label>Descripcion</label>
                    <div class="input-wrapper">
                        <i class="ph ph-notepad"></i>
                        <textarea name="desc_tarea" id="modal-tarea-desc" placeholder="Opcional" style="width:100%; padding:0.75rem 1rem 0.75rem 2.75rem; border:1px solid var(--border-color); border-radius:var(--border-radius-md); background:var(--bg-input); color:var(--text-main); min-height:70px; resize:vertical;"></textarea>
                    </div>
                </div>
                <div class="input-group">
                    <label>Hora (opcional)</label>
                    <div class="input-wrapper">
                        <i class="ph ph-clock"></i>
                        <input type="time" name="hora_tarea" id="modal-tarea-hora">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="modal-tarea-btn">Guardar Tarea</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
var seleccionados = new Set();

function toggleDia(dia) {
    var el = document.querySelector('.cal-dia[data-dia="' + dia + '"]');
    if (!el) return;
    if (el.classList.contains('cal-finde') || el.classList.contains('cal-vacio')) return;
    if (seleccionados.has(dia)) {
        seleccionados.delete(dia);
        el.classList.remove('cal-selected');
    } else {
        seleccionados.add(dia);
        el.classList.add('cal-selected');
    }
    actualizarInput();
}

function actualizarInput() {
    var arr = Array.from(seleccionados).sort(function(a,b) { return a - b; });
    document.getElementById('dias-seleccionados').value = arr.join(',');
    document.getElementById('input-dias').value = arr.join(',');
}

function confirmarAccion(tipo) {
    var dias = document.getElementById('input-dias').value;
    if (!dias) {
        alert('Seleccione o escriba los dias primero');
        return false;
    }
    var desc = document.getElementById('desc-rapida').value;
    document.getElementById('input-descripcion').value = desc;

    var colorPicker = document.getElementById('picker-color');
    if (colorPicker) {
        document.querySelector('#form-guardar-dias input[name="color"]').value = colorPicker.value;
    }

    return true;
}

function toggleTodos(el) {
    document.querySelectorAll('.check-dia').forEach(function(cb) { cb.checked = el.checked; });
}

function eliminarSeleccionados() {
    var checks = document.querySelectorAll('.check-dia:checked');
    if (checks.length === 0) {
        alert('Seleccione al menos un dia');
        return false;
    }
    if (!confirm('Eliminar ' + checks.length + ' dia(s) seleccionados?')) return false;
    var form = document.getElementById('form-eliminar-dias');
    checks.forEach(function(cb) {
        var h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'ids[]';
        h.value = cb.value;
        form.appendChild(h);
    });
    return true;
}

function abrirTarea(idCalendario, dia) {
    document.getElementById('modal-id-calendario').value = idCalendario;
    document.getElementById('modal-id-tarea').value = '';
    document.getElementById('modal-action-guardar').value = '1';
    document.getElementById('modal-action-editar').value = '';
    document.getElementById('modal-tarea-titulo').value = '';
    document.getElementById('modal-tarea-desc').value = '';
    document.getElementById('modal-tarea-hora').value = '';
    document.getElementById('modal-tarea-title').textContent = 'Agregar Tarea';
    document.getElementById('modal-tarea-btn').textContent = 'Guardar Tarea';
    document.getElementById('modal-tarea-dia').textContent = 'Agregar tarea al dia ' + dia;
    var modal = document.getElementById('modalTarea');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function editarTarea(id, titulo, descripcion, hora) {
    document.getElementById('modal-id-calendario').value = '';
    document.getElementById('modal-id-tarea').value = id;
    document.getElementById('modal-action-guardar').value = '';
    document.getElementById('modal-action-editar').value = '1';
    document.getElementById('modal-tarea-titulo').value = titulo;
    document.getElementById('modal-tarea-desc').value = descripcion;
    document.getElementById('modal-tarea-hora').value = hora;
    document.getElementById('modal-tarea-title').textContent = 'Editar Tarea';
    document.getElementById('modal-tarea-btn').textContent = 'Actualizar Tarea';
    document.getElementById('modal-tarea-dia').textContent = 'Editando tarea #' + id;
    var modal = document.getElementById('modalTarea');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

document.addEventListener('DOMContentLoaded', function() {
    var colorPicker = document.getElementById('picker-color');
    var btns = document.querySelectorAll('#form-guardar-dias button[name="tipo"]');
    if (colorPicker && btns.length) {
        btns.forEach(function(btn) {
            btn.addEventListener('mouseenter', function() {
                var colores = {'laboral':'#22c55e','feriado':'#4169E1','no_laborable':'#a855f7'};
                var t = this.getAttribute('value');
                if (colores[t]) colorPicker.value = colores[t];
            });
        });
    }
});
</script>
