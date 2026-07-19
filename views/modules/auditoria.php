<?php
require_once "helpers/AuditoriaHelper.php";
$stmt = Conexion::conectar()->prepare("
    SELECT a.*, p.nombre as usuario_nombre, p.apellido as usuario_apellido
    FROM auditoria a
    LEFT JOIN personal p ON a.id_usuario = p.id
    ORDER BY a.fecha DESC
    LIMIT 500
");
$stmt->execute();
$logs = $stmt->fetchAll();

$acciones = [];
$entidades = [];
foreach ($logs as $l) {
    $acciones[$l["accion"]] = true;
    $entidades[$l["entidad"]] = true;
}
ksort($acciones);
ksort($entidades);
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Historial de Movimientos</h1>
            <p class="current-date">Últimos 500 registros de actividad del sistema</p>
        </div>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <h2>Registros</h2>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                <input type="text" id="filtroBusqueda" placeholder="Buscar..." style="padding:6px 12px; border:1px solid var(--color-border, #e2e8f0); border-radius:6px; background:var(--bg-input); color:var(--text-main); font-size:0.85rem; min-width:180px;">
                <select id="filtroAccion" style="padding:6px 12px; border:1px solid var(--color-border, #e2e8f0); border-radius:6px; background:var(--bg-input); color:var(--text-main); font-size:0.85rem;">
                    <option value="">Todas las acciones</option>
                    <?php foreach ($acciones as $a => $_): ?>
                    <option value="<?php echo htmlspecialchars($a, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($a, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filtroEntidad" style="padding:6px 12px; border:1px solid var(--color-border, #e2e8f0); border-radius:6px; background:var(--bg-input); color:var(--text-main); font-size:0.85rem;">
                    <option value="">Todas las entidades</option>
                    <?php foreach ($entidades as $e => $_): ?>
                    <option value="<?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" id="filtroFecha" style="padding:6px 12px; border:1px solid var(--color-border, #e2e8f0); border-radius:6px; background:var(--bg-input); color:var(--text-main); font-size:0.85rem;">
            </div>
        </div>
        <div class="table-responsive" style="max-height:600px; overflow-y:auto;">
            <table class="data-table" id="tablaAuditoria" style="font-size:0.85rem;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Entidad</th>
                        <th>ID</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody id="tbodyAuditoria">
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">No hay registros de actividad</td></tr>
                    <?php else: $i = 1; ?>
                    <?php foreach ($logs as $log):
                        $acc = $log["accion"];
                        if (strpos($acc, 'sesion') !== false) $colorBadge = '#0284c7';
                        elseif (strpos($acc, 'crear') !== false || $acc === 'activar') $colorBadge = '#16a34a';
                        elseif (strpos($acc, 'eliminar') !== false || strpos($acc, 'desactivar') !== false || $acc === 'cierre_sesion') $colorBadge = '#dc2626';
                        elseif (strpos($acc, 'editar') !== false || $acc === 'asignar_rol') $colorBadge = '#f59e0b';
                        else $colorBadge = '#6b7280';
                    ?>
                    <tr class="fila-log">
                        <td class="col-num"><?php echo $i++; ?></td>
                        <td class="col-fecha" style="white-space:nowrap;"><?php echo htmlspecialchars($log["fecha"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="col-usuario"><?php echo htmlspecialchars(($log["usuario_nombre"] ?? 'Sistema') . ' ' . ($log["usuario_apellido"] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="col-accion"><span style="display:inline-block; background:<?php echo $colorBadge; ?>; color:#fff; padding:2px 10px; border-radius:12px; font-size:0.75rem; white-space:nowrap;"><?php echo htmlspecialchars($acc, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td class="col-entidad"><?php echo htmlspecialchars($log["entidad"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="col-id"><?php echo $log["id_entidad"] ? htmlspecialchars($log["id_entidad"], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                        <td class="col-detalle"><?php echo htmlspecialchars($log["detalle"] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    var paginaActual = 1;
    var porPagina = 10;
    var filas = Array.from(document.querySelectorAll('.fila-log'));

    var busqueda = document.getElementById('filtroBusqueda');
    var filtroAccion = document.getElementById('filtroAccion');
    var filtroEntidad = document.getElementById('filtroEntidad');
    var filtroFecha = document.getElementById('filtroFecha');
    var tbody = document.getElementById('tbodyAuditoria');
    var btnPrev = document.getElementById('btnPrev');
    var btnNext = document.getElementById('btnNext');
    var infoPagina = document.getElementById('infoPagina');
    var numPaginas = document.getElementById('numPaginas');

    function getFiltradas() {
        var texto = busqueda.value.toLowerCase();
        var accion = filtroAccion.value;
        var entidad = filtroEntidad.value;
        var fecha = filtroFecha.value;

        return filas.filter(function(fila) {
            var celdas = fila.querySelectorAll('td');
            var textoFila = fila.textContent.toLowerCase();
            var accionFila = (celdas[3]?.textContent.trim() || '').toLowerCase();
            var entidadFila = (celdas[4]?.textContent.trim() || '').toLowerCase();
            var fechaFila = (celdas[1]?.textContent.trim().split(' ')[0] || '');

            if (texto && !textoFila.includes(texto)) return false;
            if (accion && accionFila !== accion.toLowerCase()) return false;
            if (entidad && entidadFila !== entidad.toLowerCase()) return false;
            if (fecha && fechaFila !== fecha) return false;
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

        btnPrev.disabled = (paginaActual <= 1);
        btnNext.disabled = (paginaActual >= totalPaginas);

        var totalFiltradas = filtradas.length;
        infoPagina.textContent = totalFiltradas > 0
            ? 'Mostrando ' + (inicio + 1) + '-' + fin + ' de ' + totalFiltradas
            : 'Sin resultados';

        numPaginas.textContent = 'Pág. ' + paginaActual + ' de ' + totalPaginas;
    }

    function irPagina(n) {
        paginaActual = n;
        renderizar();
    }

    btnPrev.addEventListener('click', function() {
        if (paginaActual > 1) irPagina(paginaActual - 1);
    });

    btnNext.addEventListener('click', function() {
        var filtradas = getFiltradas();
        var totalPaginas = Math.max(1, Math.ceil(filtradas.length / porPagina));
        if (paginaActual < totalPaginas) irPagina(paginaActual + 1);
    });

    function filtrarYResetear() {
        paginaActual = 1;
        renderizar();
    }

    busqueda.addEventListener('input', filtrarYResetear);
    filtroAccion.addEventListener('change', filtrarYResetear);
    filtroEntidad.addEventListener('change', filtrarYResetear);
    filtroFecha.addEventListener('change', filtrarYResetear);

    renderizar();
});
</script>
