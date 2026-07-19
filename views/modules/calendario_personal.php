<?php
$anio = isset($_GET["anio"]) ? (int)$_GET["anio"] : (int)date("Y");
$mes = isset($_GET["mes"]) ? (int)$_GET["mes"] : (int)date("n");

require_once "models/CalendarioModel.php";
$diasConfig = CalendarioModel::mdlObtenerDiasPorMes($anio, $mes);

$diasConfigMap = [];
$tareasPorDia = [];
foreach ($diasConfig as $dc) {
    $diasConfigMap[$dc["dia"]] = $dc;
    $tareasDia = CalendarioModel::mdlObtenerTareasPorCalendario($dc["id"]);
    if ($tareasDia) {
        $tareasPorDia[$dc["dia"]] = $tareasDia;
    }
}

$primerDia = (int)date("w", mktime(0, 0, 0, $mes, 1, $anio));
$ultimoDia = (int)date("t", mktime(0, 0, 0, $mes, 1, $anio));
$nombreMes = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"][$mes - 1];

$coloresPorTipo = ["laboral" => "#22c55e", "feriado" => "#4169E1", "vacaciones" => "#f59e0b", "no_laborable" => "#a855f7"];
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Calendario Escolar</h1>
            <p class="current-date">Visualice los días laborables, feriados, vacaciones y tareas del calendario escolar.</p>
        </div>
        <div class="header-actions">
            <a href="index.php?ruta=calendario_personal&anio=<?php echo $anio; ?>&mes=<?php echo max(1, $mes - 1); ?>" class="btn btn-outline"><i class="ph ph-caret-left"></i> Mes Ant.</a>
            <a href="index.php?ruta=calendario_personal&anio=<?php echo date("Y"); ?>&mes=<?php echo (int)date("n"); ?>" class="btn btn-outline"><i class="ph ph-calendar"></i> Hoy</a>
            <a href="index.php?ruta=calendario_personal&anio=<?php echo $anio; ?>&mes=<?php echo min(12, $mes + 1); ?>" class="btn btn-outline">Mes Sig. <i class="ph ph-caret-right"></i></a>
        </div>
    </div>

    <div class="cal-selector">
        <form method="get" class="cal-selector-form" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="ruta" value="calendario_personal">
            <label style="font-weight:500;">Año:
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

    <div class="widget" style="margin-top:1rem;">
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
                            $tooltip = htmlspecialchars(ucfirst($config["tipo"]) . ($config["descripcion"] ? ": " . $config["descripcion"] : ""), ENT_QUOTES, "UTF-8");
                        } elseif ($esFinde) {
                            $tipoCls = "cal-finde";
                            $bgColor = "#ef4444";
                            $tooltip = "Fin de semana";
                        } else {
                            $tooltip = "Día sin configurar";
                        }
                    ?>
                    <div class="cal-dia <?php echo $tipoCls . ($esHoy ? " cal-hoy" : ""); ?>"
                         data-dia="<?php echo $d; ?>"
                         title="<?php echo $tooltip; ?>"
                         style="<?php echo $bgColor ? "background:{$bgColor};color:#fff;" : ""; ?>">
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

    <?php if (!empty($tareasPorDia)): ?>
    <div class="widget" style="margin-top:1rem;">
        <div class="widget-header">
            <h2>Tareas del Mes</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Hora</th>
                        <th>Título</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tareasPorDia as $dia => $tareas):
                        foreach ($tareas as $t): ?>
                    <tr>
                        <td><strong><?php echo $dia; ?></strong></td>
                        <td><?php echo $t["hora"] ? substr($t["hora"], 0, 5) : "--:--"; ?></td>
                        <td><?php echo htmlspecialchars($t["titulo"], ENT_QUOTES, "UTF-8"); ?></td>
                        <td><?php echo htmlspecialchars($t["descripcion"] ?? "", ENT_QUOTES, "UTF-8"); ?></td>
                    </tr>
                    <?php endforeach;
                    endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>
