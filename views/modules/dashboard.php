<?php
require_once "models/PersonalModel.php";
require_once "controllers/AsistenciaController.php";

$queryPersonal = PersonalModel::mdlContarPersonalActivo("personal");
$totalPersonalActivo = $queryPersonal ? $queryPersonal["total"] : 0;

$presentesHoy = AsistenciaController::ctrContarPresentesHoy();
if (!$presentesHoy)
    $presentesHoy = 0;

$porcentajeAsistencia = AsistenciaController::ctrCalcularPorcentajeAsistencia($presentesHoy);
$ausentesHoy = $totalPersonalActivo - $presentesHoy;
if ($ausentesHoy < 0)
    $ausentesHoy = 0;

$porcentajeAusencia = 100 - $porcentajeAsistencia;
if ($porcentajeAusencia < 0 || $totalPersonalActivo == 0)
    $porcentajeAusencia = 0;

$estadoHoy = AsistenciaController::ctrEstadoAsistenciaHoy();
$tardeHoy = AsistenciaController::ctrContarTardeHoy();
$chartSemanal = AsistenciaController::ctrAsistenciaUltimosDias(7);

$chartLabels = array();
$chartPresentes = array();
$chartAusentes = array();
foreach ($chartSemanal as $fecha => $d) {
    $chartLabels[] = date('d/m', strtotime($fecha));
    $chartPresentes[] = $d['presentes'];
    $chartAusentes[] = $totalPersonalActivo - $d['presentes'];
}

require_once "config/Conexion.php";
try {
    $stmt = Conexion::conectar()->prepare("SELECT fecha, descripcion FROM feriados WHERE YEAR(fecha) = :anio OR recurrente = 1");
    $anio = date('Y');
    $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
    $stmt->execute();
    $feriados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $feriados = array();
}
$feriadosJson = json_encode($feriados);

$calEscolar = array();
try {
    $stmtCal = Conexion::conectar()->prepare("SELECT anio, mes, dia, tipo, color FROM calendario_escolar WHERE anio = :anio ORDER BY mes, dia");
    $stmtCal->bindParam(":anio", $anio, PDO::PARAM_INT);
    $stmtCal->execute();
    $calEscolar = $stmtCal->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $calEscolar = array();
}
$calEscolarJson = json_encode($calEscolar);
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <p class="current-date" id="current-date">Cargando fecha...</p>
        </div>
    </div>

    <!-- Widgets de Métricas (Grid) -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon bg-blue-light">
                <i class="ph ph-users text-blue"></i>
            </div>
            <div class="metric-data">
                <h3>Total Personal</h3>
                <div class="value">
                    <?php echo $totalPersonalActivo; ?>
                </div>
                <div class="trend positive"><i class="ph ph-trend-up"></i> Empleados Registrados</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon bg-green-light">
                <i class="ph ph-check-circle text-green"></i>
            </div>
            <div class="metric-data">
                <h3>Presentes Hoy</h3>
                <div class="value">
                    <?php echo $presentesHoy; ?>
                </div>
                <div class="trend text-green">
                    <?php echo $porcentajeAsistencia; ?>% de asistencia
                </div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon bg-red-light">
                <i class="ph ph-x-circle text-red"></i>
            </div>
            <div class="metric-data">
                <h3>Ausentes Hoy</h3>
                <div class="value">
                    <?php echo $ausentesHoy; ?>
                </div>
                <div class="trend text-red">
                    <?php echo round($porcentajeAusencia, 1); ?>% de ausencia
                </div>
            </div>
        </div>

        <a href="index.php?ruta=justificaciones" style="text-decoration:none; color:inherit;">
        <div class="metric-card" style="cursor:pointer;">
            <div class="metric-icon bg-yellow-light">
                <i class="ph ph-clock-countdown text-yellow"></i>
            </div>
            <div class="metric-data">
                <h3>Justificaciones Pendientes</h3>
                <div class="value"><?php
                    require_once "controllers/JustificacionController.php";
                    echo JustificacionController::ctrContarPendientes();
                ?></div>
                <div class="trend text-yellow">Requiere revisión</div>
            </div>
        </div>
        </a>
    </div>

    <!-- Sección Inferior del Dashboard -->
    <div class="dashboard-widgets">

        <!-- Calendario -->
        <div class="widget calendar-widget" style="grid-column: 1 / -1;">
            <div class="widget-header">
                <h2>Calendario</h2>
                <div class="calendar-nav" style="display:flex; gap:0.5rem; align-items:center;">
                    <button class="btn btn-sm" id="calendar-prev" title="Mes anterior"><i class="ph ph-caret-left"></i></button>
                    <span id="calendar-titulo" style="font-weight:600; min-width:140px; text-align:center;"></span>
                    <button class="btn btn-sm" id="calendar-next" title="Mes siguiente"><i class="ph ph-caret-right"></i></button>
                </div>
            </div>
            <div class="calendar-body">
                <div class="calendar-leyenda" style="display:flex; gap:1rem; margin-bottom:0.75rem; font-size:0.75rem; flex-wrap:wrap;">
                    <span><span class="cal-legend" style="background:#10b981;"></span> Laboral</span>
                    <span><span class="cal-legend" style="background:#ef4444;"></span> Fin de Semana</span>
                    <span><span class="cal-legend" style="background:#4169E1;"></span> Feriado</span>
                    <span><span class="cal-legend" style="background:#f59e0b;"></span> Vacaciones</span>
                    <span><span class="cal-legend" style="background:#a855f7;"></span> No Laborable</span>
                </div>
                <div class="calendar-grid" id="calendar-grid"></div>
            </div>
        </div>

        <!-- Tabla de Ausentes y Registro Reciente -->
        <div class="widget widget-lg table-widget">
            <div class="widget-header">
                <h2>Estado de Asistencia en Tiempo Real</h2>
                <div class="widget-filters">
                    <select id="filter-estado" aria-label="Filtrar por estado">
                        <option value="">Todos los estados</option>
                        <option value="Presente">Presente</option>
                        <option value="Tarde">Tarde</option>
                        <option value="Ausente">Ausente</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table" id="tabla-tiempo-real">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Cargo/Departamento</th>
                            <th>Hora de Registro</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($estadoHoy) === 0): ?>
                        <tr><td colspan="4" style="text-align:center; padding:2rem;">No hay empleados activos registrados.</td></tr>
                        <?php else: ?>
                        <?php foreach ($estadoHoy as $e):
                            $avatarSrc = (!empty($e['foto'])) ? "foto_perfil/" . $e['foto'] : "https://ui-avatars.com/api/?name=" . urlencode($e['nombre'] . "+" . $e['apellido']) . "&background=random";
                            $esAusente = is_null($e['hora_entrada']);
                            $estado = $esAusente ? 'Ausente' : $e['estado_entrada'];
                            $badge = 'status-green';
                            if ($estado == 'Tarde') $badge = 'status-warning';
                            elseif ($estado == 'Ausente') $badge = 'status-red';
                        ?>
                        <tr data-estado="<?php echo $estado; ?>">
                            <td>
                                <div class="student-cell">
                                    <img src="<?php echo $avatarSrc; ?>" alt="" class="avatar-sm">
                                    <span><?php echo htmlspecialchars($e['nombre'] . ' ' . $e['apellido'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($e['nombre_cargo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $esAusente ? '--:--' : substr($e['hora_entrada'], 0, 5); ?></td>
                            <td><span class="status-badge <?php echo $badge; ?>"><?php echo $estado; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="widget-footer" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                <div id="pagination-dashboard" class="pagination-controls" style="display:flex; gap:0.25rem; align-items:center;"></div>
                <a href="index.php?ruta=reportes" class="view-all">Ver reporte completo <i class="ph ph-arrow-right"></i></a>
            </div>
        </div>

        <!-- Gráficos -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="widget">
                <div class="widget-header">
                    <h2>Hoy</h2>
                </div>
                <div style="padding: 1rem; position: relative; height: 220px;">
                    <canvas id="chartHoy"></canvas>
                </div>
            </div>
            <div class="widget">
                <div class="widget-header">
                    <h2>Últimos 7 Días</h2>
                </div>
                <div style="padding: 1rem; position: relative; height: 220px;">
                    <canvas id="chartSemanal"></canvas>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
var dashboardPage = 1;
var dashboardRows = [];
var dashboardFiltered = [];
var rowsPerPage = 5;

function filterDashboard() {
    var filter = document.getElementById('filter-estado').value;
    var tbody = document.querySelector('#tabla-tiempo-real tbody');
    var allRows = Array.from(tbody.querySelectorAll('tr'));
    dashboardRows = allRows.filter(function(r) { return r.hasAttribute('data-estado'); });
    dashboardFiltered = dashboardRows.filter(function(r) {
        return filter === '' || r.getAttribute('data-estado') === filter;
    });
    dashboardPage = 1;
    renderDashboard();
}

function renderDashboard() {
    var tbody = document.querySelector('#tabla-tiempo-real tbody');
    dashboardRows.forEach(function(r) { r.style.display = 'none'; });
    var start = (dashboardPage - 1) * rowsPerPage;
    var page = dashboardFiltered.slice(start, start + rowsPerPage);
    page.forEach(function(r) { r.style.display = ''; });
    renderPagination();
}

function renderPagination() {
    var ctrl = document.getElementById('pagination-dashboard');
    ctrl.innerHTML = '';
    var total = Math.ceil(dashboardFiltered.length / rowsPerPage);
    if (total <= 1) return;
    var prev = document.createElement('button');
    prev.textContent = '‹';
    prev.className = 'page-btn';
    prev.disabled = dashboardPage === 1;
    prev.style.fontSize = '1rem';
    prev.addEventListener('click', function() { if (dashboardPage > 1) { dashboardPage--; renderDashboard(); } });
    ctrl.appendChild(prev);
    for (var i = 1; i <= total; i++) {
        var btn = document.createElement('button');
        btn.textContent = i;
        btn.className = 'page-btn';
        if (i === dashboardPage) btn.classList.add('active');
        btn.addEventListener('click', function(p) { return function() { dashboardPage = p; renderDashboard(); }; }(i));
        ctrl.appendChild(btn);
    }
    var next = document.createElement('button');
    next.textContent = '›';
    next.className = 'page-btn';
    next.disabled = dashboardPage === total;
    next.style.fontSize = '1rem';
    next.addEventListener('click', function() { if (dashboardPage < total) { dashboardPage++; renderDashboard(); } });
    ctrl.appendChild(next);
}

var feriados = <?php echo $feriadosJson; ?>;
var calEscolar = <?php echo $calEscolarJson; ?>;
var mesActual = new Date().getMonth();
var anioActual = new Date().getFullYear();

function esFeriado(dia, mes, anio) {
    var f = (anio) + '-' + String(mes+1).padStart(2,'0') + '-' + String(dia).padStart(2,'0');
    return feriados.some(function(h) {
        if (h.recurrente == 1) {
            var parts = h.fecha.split('-');
            return parseInt(parts[1]) === mes+1 && parseInt(parts[2]) === dia;
        }
        return h.fecha === f;
    });
}

function getCalEscolar(dia, mes, anio) {
    mes = mes + 1; // JS mes is 0-indexed
    return calEscolar.find(function(c) {
        return c.anio == anio && c.mes == mes && c.dia == dia;
    });
}

function renderCalendar(mes, anio) {
    var grid = document.getElementById('calendar-grid');
    var titulo = document.getElementById('calendar-titulo');
    if (!grid || !titulo) return;
    var meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    titulo.textContent = meses[mes] + ' ' + anio;

    var html = '<div class="cal-dias-semana">';
    var diasSem = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
    for (var i = 0; i < 7; i++) html += '<div class="cal-dia-header">' + diasSem[i] + '</div>';
    html += '</div><div class="cal-dias">';

    var primerDia = new Date(anio, mes, 1).getDay();
    var ultimoDia = new Date(anio, mes + 1, 0).getDate();
    var hoy = new Date();

    for (var i = 0; i < primerDia; i++) html += '<div class="cal-dia cal-vacio"></div>';

    for (var d = 1; d <= ultimoDia; d++) {
        var fechaObj = new Date(anio, mes, d);
        var diaSem = fechaObj.getDay();
        var esHoy = (hoy.getDate() === d && hoy.getMonth() === mes && hoy.getFullYear() === anio);
        var esFinde = (diaSem === 0 || diaSem === 6);
        var esFeri = esFeriado(d, mes, anio);

        var cal = getCalEscolar(d, mes, anio);
        var color = '';
        var bgStyle = '';

        if (cal) {
            var tipoMap = {laboral: 'cal-laboral', feriado: 'cal-feriado', vacaciones: 'cal-vacaciones', no_laborable: 'cal-no-laborable'};
            color = tipoMap[cal.tipo] || '';
            if (cal.color) bgStyle = ' background:' + cal.color + ';';
        } else if (esFeri) {
            color = 'cal-feriado';
        } else if (esFinde) {
            color = 'cal-finde';
        } else {
            color = 'cal-laboral';
        }

        var cls = 'cal-dia ' + color + (esHoy ? ' cal-hoy' : '');
        html += '<div class="' + cls + '" style="' + bgStyle + '">' + d + '</div>';
    }

    html += '</div>';
    grid.innerHTML = html;
}

function cambiarMes(delta) {
    mesActual += delta;
    if (mesActual > 11) { mesActual = 0; anioActual++; }
    if (mesActual < 0) { mesActual = 11; anioActual--; }
    renderCalendar(mesActual, anioActual);
}

function initCalendar() {
    renderCalendar(mesActual, anioActual);
    var prev = document.getElementById('calendar-prev');
    var next = document.getElementById('calendar-next');
    if (prev) prev.addEventListener('click', function() { cambiarMes(-1); });
    if (next) next.addEventListener('click', function() { cambiarMes(1); });
}
document.addEventListener('DOMContentLoaded', function() {
    var total = <?php echo $totalPersonalActivo ?: 0; ?>;
    var presentes = <?php echo $presentesHoy ?: 0; ?>;
    var tarde = <?php echo $tardeHoy ?: 0; ?>;
    var ausentes = Math.max(0, total - presentes);

    var ctxHoy = document.getElementById('chartHoy');
    if (ctxHoy) {
        new Chart(ctxHoy, {
            type: 'doughnut',
            data: {
                labels: ['Presentes', 'Tarde', 'Ausentes'],
                datasets: [{
                    data: [presentes, tarde, ausentes],
                    backgroundColor: ['#4169E1', '#10b981', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
                }
            }
        });
    }

    initCalendar();

    var filterSelect = document.getElementById('filter-estado');
    if (filterSelect) {
        filterSelect.addEventListener('change', filterDashboard);
        filterDashboard();
    }

    var ctxSemanal = document.getElementById('chartSemanal');
    if (ctxSemanal) {
        new Chart(ctxSemanal, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Presentes',
                    data: <?php echo json_encode($chartPresentes); ?>,
                    backgroundColor: '#4169E1',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>