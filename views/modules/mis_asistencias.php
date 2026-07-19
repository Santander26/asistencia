<?php
require_once "controllers/AsistenciaController.php";
require_once "models/AsistenciaModel.php";

$id_usuario = $_SESSION["id"];
$fecha_inicio = $_GET["fecha_inicio"] ?? date("Y-m-01");
$fecha_fin = $_GET["fecha_fin"] ?? date("Y-m-d");

$asistencias = AsistenciaModel::mdlListarAsistenciasReporte($fecha_inicio, $fecha_fin, null, $id_usuario);
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Mis Asistencias</h1>
            <p class="current-date" id="current-date">Cargando fecha...</p>
        </div>
    </div>

    <div class="widget">
        <div class="widget-header">
            <h2>Filtros de Búsqueda</h2>
        </div>
        <form method="GET" style="padding: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <input type="hidden" name="ruta" value="mis_asistencias">
            <div class="input-group" style="margin-bottom: 0;">
                <label>Desde</label>
                <div class="input-wrapper">
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                </div>
            </div>
            <div class="input-group" style="margin-bottom: 0;">
                <label>Hasta</label>
                <div class="input-wrapper">
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                </div>
            </div>
            <div style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary"><i class="ph ph-magnifying-glass"></i> Buscar</button>
            </div>
        </form>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header">
            <h2>Registro de Asistencias</h2>
            <span class="badge"><?php echo count($asistencias); ?> registro(s)</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Horas</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($asistencias) === 0): ?>
                    <tr><td colspan="6" style="text-align:center; padding:2rem;">No hay registros de asistencia en el rango seleccionado.</td></tr>
                    <?php else: ?>
                    <?php $i = 1; foreach ($asistencias as $a): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo date("d/m/Y", strtotime($a["fecha"])); ?></td>
                        <td><?php echo $a["hora_entrada"] ? substr($a["hora_entrada"], 0, 5) : '--:--'; ?></td>
                        <td><?php echo $a["hora_salida"] ? substr($a["hora_salida"], 0, 5) : '--:--'; ?></td>
                        <td><?php
                            if ($a["horas_trabajadas"]) {
                                printf('%02d:%02d', floor($a["horas_trabajadas"]), round(($a["horas_trabajadas"] - floor($a["horas_trabajadas"])) * 60));
                            } else {
                                echo '--:--';
                            }
                        ?></td>
                        <td>
                            <?php
                            $badge = 'status-green';
                            if ($a["estado_entrada"] == 'Tarde') $badge = 'status-warning';
                            elseif ($a["estado_entrada"] == 'Ausente') $badge = 'status-red';
                            ?>
                            <span class="status-badge <?php echo $badge; ?>"><?php echo $a["estado_entrada"]; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
