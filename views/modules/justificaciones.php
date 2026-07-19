<?php
require_once "controllers/JustificacionController.php";
require_once "helpers/CsrfHelper.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once "helpers/RbacHelper.php";
$rol = RbacHelper::getRolId();

JustificacionController::ctrCrear();
JustificacionController::ctrAprobar();
JustificacionController::ctrRechazar();
JustificacionController::ctrEliminar();

$justificaciones = JustificacionController::ctrListar();
$personalActivo = JustificacionController::ctrListarPersonalActivo();
$pendientes = JustificacionController::ctrContarPendientes();

$tipos = ['medico' => 'Médico', 'personal' => 'Personal', 'permiso' => 'Permiso', 'otro' => 'Otro'];
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Justificaciones</h1>
            <p class="current-date">Gestiona las justificaciones de inasistencia y tardanzas</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaJustificacion">
                <i class="ph ph-plus"></i> Nueva Justificación
            </button>
        </div>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <h2>Listado de Justificaciones</h2>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                <input type="text" id="filtroBusqueda" placeholder="Buscar..." style="padding:6px 12px; border:1px solid var(--color-border, #e2e8f0); border-radius:6px; background:var(--bg-input); color:var(--text-main); font-size:0.85rem; min-width:160px;">
                <select id="filtroEstado" style="padding:6px 12px; border:1px solid var(--color-border, #e2e8f0); border-radius:6px; background:var(--bg-input); color:var(--text-main); font-size:0.85rem;">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="aprobado">Aprobado</option>
                    <option value="rechazado">Rechazado</option>
                </select>
                <select id="filtroTipo" style="padding:6px 12px; border:1px solid var(--color-border, #e2e8f0); border-radius:6px; background:var(--bg-input); color:var(--text-main); font-size:0.85rem;">
                    <option value="">Todos los tipos</option>
                    <?php foreach ($tipos as $k => $v): ?>
                    <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="tablaJustificaciones" style="font-size:0.85rem;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Empleado</th>
                        <th>Documento</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Adjunto</th>
                        <th>Estado</th>
                        <th>Aprobado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($justificaciones)): ?>
                    <tr><td colspan="10" style="text-align:center; padding:2rem; color:var(--text-muted);">No hay justificaciones registradas</td></tr>
                    <?php else: $i = 1; ?>
                    <?php foreach ($justificaciones as $j):
                        $estado = $j["aprobado_por"] === null ? 'pendiente' : ($j["aprobado_por"] == 0 ? 'rechazado' : 'aprobado');
                        $colorEstado = $estado === 'aprobado' ? '#16a34a' : ($estado === 'rechazado' ? '#dc2626' : '#f59e0b');
                    ?>
                    <tr class="fila-justificacion">
                        <td><?php echo $i++; ?></td>
                        <td><strong><?php echo htmlspecialchars($j["personal_nombre"] . ' ' . $j["personal_apellido"], ENT_QUOTES, 'UTF-8'); ?></strong><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($j["nombre_cargo"] ?? '', ENT_QUOTES, 'UTF-8'); ?></small></td>
                        <td><?php echo htmlspecialchars($j["documento_identidad"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($j["fecha"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span style="background:var(--bg-input); color:var(--text-main); padding:2px 8px; border-radius:8px; font-size:0.75rem;"><?php echo htmlspecialchars($tipos[$j["tipo"]] ?? $j["tipo"], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($j["motivo"], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($j["motivo"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php
                            $archivos = $j["documento_adjunto"] ? array_filter(array_map("trim", explode(",", $j["documento_adjunto"]))) : [];
                            if ($archivos): foreach ($archivos as $a):
                            ?><a href="adjuntos_justificaciones/<?php echo rawurlencode($a); ?>" target="_blank" style="color:var(--color-primary); margin-right:4px;"><i class="ph ph-paperclip"></i></a>
                            <?php endforeach; else: ?>-<?php endif; ?></td>
                        <td><span style="display:inline-block; background:<?php echo $colorEstado; ?>; color:#fff; padding:2px 10px; border-radius:12px; font-size:0.75rem; text-transform:capitalize;"><?php echo $estado; ?></span></td>
                        <td><?php echo $j["aprobado_nombre"] ? htmlspecialchars($j["aprobado_nombre"] . ' ' . $j["aprobado_apellido"], ENT_QUOTES, 'UTF-8') : ($j["aprobado_por"] == 0 ? 'Rechazado' : '-'); ?></td>
                        <td style="white-space:nowrap;">
                            <a href="index.php?ruta=exportar_justificacion_pdf&id=<?php echo $j["id"]; ?>" class="btn btn-sm" style="background:#0284c7; color:#fff; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8rem;" target="_blank"><i class="ph ph-file-pdf"></i> PDF</a>
                            <?php if ($estado === 'pendiente' && ($rol === 4 || $rol === 1)): ?>
                            <a href="index.php?ruta=justificaciones&id=<?php echo $j["id"]; ?>&action=aprobar&csrf_token=<?php echo CsrfHelper::token(); ?>" class="btn btn-sm" style="background:#16a34a; color:#fff; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8rem;" onclick="return confirm('¿Aprobar esta justificación?')">Aprobar</a>
                            <a href="index.php?ruta=justificaciones&id=<?php echo $j["id"]; ?>&action=rechazar&csrf_token=<?php echo CsrfHelper::token(); ?>" class="btn btn-sm" style="background:#dc2626; color:#fff; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8rem;" onclick="return confirm('¿Rechazar esta justificación?')">Rechazar</a>
                            <?php endif; ?>
                            <?php if ($rol === 4): ?>
                            <a href="index.php?ruta=justificaciones&id=<?php echo $j["id"]; ?>&action=eliminar&csrf_token=<?php echo CsrfHelper::token(); ?>" class="btn btn-sm" style="background:transparent; color:var(--text-muted); padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8rem;" onclick="return confirm('¿Eliminar esta justificación permanentemente?')"><i class="ph ph-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="widget-footer" style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem 1rem; border-top:1px solid var(--color-border, #e2e8f0); font-size:0.85rem; flex-wrap:wrap; gap:0.5rem;">
            <span id="infoPagina" style="color:var(--text-muted);"></span>
            <div style="display:flex; gap:4px; align-items:center;">
                <button id="btnPrev" style="padding:4px 10px; border:1px solid var(--color-border, #e2e8f0); border-radius:4px; background:var(--bg-surface); color:var(--text-main); cursor:pointer; font-size:0.85rem;" disabled>Anterior</button>
                <span id="numPaginas" style="padding:0 8px; color:var(--text-muted);"></span>
                <button id="btnNext" style="padding:4px 10px; border:1px solid var(--color-border, #e2e8f0); border-radius:4px; background:var(--bg-surface); color:var(--text-main); cursor:pointer; font-size:0.85rem;">Siguiente</button>
            </div>
        </div>
    </div>
</main>

<!-- Modal Nueva Justificación -->
<div id="modalNuevaJustificacion" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h2>Nueva Justificación</h2>
            <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?php echo CsrfHelper::field(); ?>
            <div class="modal-body">
                <div class="input-group">
                    <label>Empleado</label>
                    <div class="input-wrapper">
                        <i class="ph ph-user"></i>
                        <select name="id_personal" required style="width:100%; padding-left:2.5rem;">
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($personalActivo as $p): ?>
                            <option value="<?php echo $p["id"]; ?>"><?php echo htmlspecialchars($p["nombre"] . ' ' . $p["apellido"] . ' - ' . $p["documento_identidad"], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="input-group">
                    <label>Fecha a Justificar</label>
                    <div class="input-wrapper">
                        <i class="ph ph-calendar"></i>
                        <input type="date" name="fecha_justificacion" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="input-group">
                    <label>Tipo</label>
                    <div class="input-wrapper">
                        <i class="ph ph-tag"></i>
                        <select name="tipo_justificacion" required style="width:100%; padding-left:2.5rem;">
                            <option value="medico">Médico</option>
                            <option value="personal">Personal</option>
                            <option value="permiso">Permiso</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="input-group">
                    <label>Motivo</label>
                    <div class="input-wrapper" style="align-items:flex-start;">
                        <i class="ph ph-file-text" style="margin-top:10px;"></i>
                        <textarea name="motivo" required placeholder="Describa el motivo de la justificación" rows="4" style="width:100%; padding:10px 10px 10px 2.5rem; border:1px solid var(--color-border, #e2e8f0); border-radius:6px; background:var(--bg-input); color:var(--text-main); font-family:inherit; font-size:0.9rem; resize:vertical;"></textarea>
                    </div>
                </div>
                <div class="input-group">
                    <label>Documentos Adjuntos (opcional, JPG/PNG, varios)</label>
                    <div class="input-wrapper">
                        <i class="ph ph-paperclip"></i>
                        <input type="file" name="documento_adjunto[]" multiple accept=".jpg,.jpeg,.png" style="padding-left:2.5rem;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Justificación</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var paginaActual = 1;
    var porPagina = 10;
    var filas = Array.from(document.querySelectorAll('.fila-justificacion'));

    var busqueda = document.getElementById('filtroBusqueda');
    var filtroEstado = document.getElementById('filtroEstado');
    var filtroTipo = document.getElementById('filtroTipo');
    var btnPrev = document.getElementById('btnPrev');
    var btnNext = document.getElementById('btnNext');
    var infoPagina = document.getElementById('infoPagina');
    var numPaginas = document.getElementById('numPaginas');

    function getFiltradas() {
        var texto = busqueda.value.toLowerCase();
        var estado = filtroEstado.value;
        var tipo = filtroTipo.value;

        return filas.filter(function(fila) {
            var celdas = fila.querySelectorAll('td');
            var textoFila = fila.textContent.toLowerCase();
            var estadoFila = (celdas[7]?.textContent.trim() || '').toLowerCase();
            var tipoFila = (celdas[4]?.textContent.trim() || '').toLowerCase();

            if (texto && !textoFila.includes(texto)) return false;
            if (estado && estadoFila !== estado) return false;
            if (tipo && tipoFila !== tipo.toLowerCase()) return false;
            return true;
        });
    }

    function renderizar() {
        var filtradas = getFiltradas();
        var totalPaginas = Math.max(1, Math.ceil(filtradas.length / porPagina));
        if (paginaActual > totalPaginas) paginaActual = totalPaginas;
        var inicio = (paginaActual - 1) * porPagina;
        var fin = Math.min(inicio + porPagina, filtradas.length);
        var pagina = filtradas.slice(inicio, fin);

        filas.forEach(function(f) { f.style.display = 'none'; });
        pagina.forEach(function(f) { f.style.display = ''; });

        btnPrev.disabled = paginaActual <= 1;
        btnNext.disabled = paginaActual >= totalPaginas;

        var total = filtradas.length;
        infoPagina.textContent = total > 0 ? 'Mostrando ' + (inicio + 1) + '-' + fin + ' de ' + total : 'Sin resultados';
        numPaginas.textContent = 'Pág. ' + paginaActual + ' de ' + totalPaginas;
    }

    btnPrev.addEventListener('click', function() { if (paginaActual > 1) { paginaActual--; renderizar(); } });
    btnNext.addEventListener('click', function() {
        var t = Math.max(1, Math.ceil(getFiltradas().length / porPagina));
        if (paginaActual < t) { paginaActual++; renderizar(); }
    });

    function reset() { paginaActual = 1; renderizar(); }
    busqueda.addEventListener('input', reset);
    filtroEstado.addEventListener('change', reset);
    filtroTipo.addEventListener('change', reset);

    renderizar();
});
</script>
