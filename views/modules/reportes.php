<?php
require_once "controllers/AsistenciaController.php";

$fecha_inicio = $_GET["fecha_inicio"] ?? date('Y-m-01');
$fecha_fin = $_GET["fecha_fin"] ?? date('Y-m-d');
$id_cargo = $_GET["id_cargo"] ?? "";

$_GET["fecha_inicio"] = $fecha_inicio;
$_GET["fecha_fin"] = $fecha_fin;
$_GET["id_cargo"] = $id_cargo;
$asistencias = AsistenciaController::ctrListarAsistenciasReporte();
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Informes y Reportes</h1>
            <p class="current-date">Genera exportaciones de asistencia diaria, semanal y mensual.</p>
        </div>
        <div class="header-actions">
            <a href="index.php?ruta=exportar_reporte&formato=xls&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&id_cargo=<?php echo $id_cargo; ?>" class="btn btn-outline" style="color: var(--clr-green); border-color: var(--clr-green);">
                <i class="ph ph-file-xls"></i> Excel
            </a>
            <a href="index.php?ruta=exportar_reporte&formato=csv&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&id_cargo=<?php echo $id_cargo; ?>" class="btn btn-outline" style="color: var(--clr-blue); border-color: var(--clr-blue);">
                <i class="ph ph-file-csv"></i> CSV
            </a>
            <a href="index.php?ruta=exportar_reporte&formato=pdf&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&id_cargo=<?php echo $id_cargo; ?>" class="btn btn-outline" style="color: var(--clr-red); border-color: var(--clr-red);">
                <i class="ph ph-file-pdf"></i> PDF
            </a>
        </div>
    </div>

    <!-- Filtros de Reporte -->
    <div class="widget">
        <div class="widget-header">
            <h2>Filtros de Búsqueda</h2>
        </div>
        <form method="get" style="padding: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <input type="hidden" name="ruta" value="reportes">
            <div class="input-group" style="margin-bottom: 0;">
                <label>Fecha Inicio</label>
                <div class="input-wrapper">
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                </div>
            </div>
            <div class="input-group" style="margin-bottom: 0;">
                <label>Fecha Fin</label>
                <div class="input-wrapper">
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                </div>
            </div>

            <div style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary"><i class="ph ph-magnifying-glass"></i> Buscar</button>
            </div>
        </form>
    </div>

    <!-- Tabla de Resultados -->
    <div class="widget widget-lg table-widget">
        <div class="widget-header">
            <h2>Registros de Asistencia</h2>
            <div class="widget-filters">
                <span style="color: var(--clr-text-title);"><?php echo count($asistencias); ?> registro(s)</span>
            </div>
        </div>
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="data-table" id="reporte-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Empleado</th>
                        <th>Documento</th>
                        <th>Cargo</th>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Horas</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($asistencias) === 0): ?>
                    <tr><td colspan="9" style="text-align:center; padding:2rem;">No hay registros para este período.</td></tr>
                    <?php else: $i = 1; ?>
                    <?php foreach ($asistencias as $a): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($a['nombre'] . ' ' . $a['apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($a['documento_identidad'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($a['nombre_cargo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $a['fecha']; ?></td>
                        <td><?php echo $a['hora_entrada'] ? substr($a['hora_entrada'], 0, 5) : '--:--'; ?></td>
                        <td><?php echo $a['hora_salida'] ? substr($a['hora_salida'], 0, 5) : '--:--'; ?></td>
                        <td><?php
    if ($a['horas_trabajadas']) {
        $h = floor($a['horas_trabajadas']);
        $m = round(($a['horas_trabajadas'] - $h) * 60);
        echo sprintf('%02d:%02d', $h, $m);
    } else { echo '--'; }
?></td>
                        <td>
                            <?php
                            $badge = 'status-green';
                            $texto = $a['estado_entrada'];
                            if ($texto == 'Tarde') $badge = 'status-warning';
                            elseif ($texto == 'Ausente') $badge = 'status-red';
                            ?>
                            <span class="status-badge <?php echo $badge; ?>"><?php echo $texto; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
(function() {
    var pollTimer = null;
    var isPolling = false;
    var tbody = document.querySelector('#reporte-table tbody');

    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(fetchReportes, 60000);
    }

    function fetchReportes() {
        if (isPolling || !tbody) return;
        isPolling = true;
        var params = 'fecha_desde=' + encodeURIComponent('<?php echo $fecha_inicio; ?>') +
                     '&fecha_hasta=' + encodeURIComponent('<?php echo $fecha_fin; ?>') +
                     '&id_cargo=' + encodeURIComponent('<?php echo $id_cargo; ?>');
        fetch('index.php?ruta=api_realtime&type=reportes&' + params + '&_=' + Date.now())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error || !data.tabla) return;
                var html = '', i = 1;
                if (data.tabla.length === 0) {
                    html = '<tr><td colspan="9" style="text-align:center; padding:2rem;">No hay registros para este periodo.</td></tr>';
                } else {
                    for (var j = 0; j < data.tabla.length; j++) {
                        var a = data.tabla[j];
                        var nombre = (a.nombre + ' ' + a.apellido).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        var doc = (a.documento_identidad || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        var cargo = (a.nombre_cargo || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        var entrada = a.hora_entrada ? a.hora_entrada.substring(0, 5) : '--:--';
                        var salida = a.hora_salida ? a.hora_salida.substring(0, 5) : '--:--';
                        var horas = '--';
                        if (a.horas_trabajadas) {
                            var h = Math.floor(a.horas_trabajadas);
                            var m = Math.round((a.horas_trabajadas - h) * 60);
                            horas = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                        }
                        var estado = a.estado_entrada;
                        var badge = estado === 'Tarde' ? 'status-warning' : (estado === 'Ausente' ? 'status-red' : 'status-green');
                        html += '<tr><td>' + (i++) + '</td>' +
                            '<td>' + nombre + '</td>' +
                            '<td>' + doc + '</td>' +
                            '<td>' + cargo + '</td>' +
                            '<td>' + a.fecha + '</td>' +
                            '<td>' + entrada + '</td>' +
                            '<td>' + salida + '</td>' +
                            '<td>' + horas + '</td>' +
                            '<td><span class="status-badge ' + badge + '">' + estado + '</span></td></tr>';
                    }
                }
                tbody.innerHTML = html;
            })
            .catch(function() {})
            .finally(function() { isPolling = false; });
    }

    startPolling();
})();
</script>

