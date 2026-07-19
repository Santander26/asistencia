<?php
require_once "controllers/JustificacionController.php";

JustificacionController::ctrCrearPersonal();

$id_usuario = $_SESSION["id"];
$justificaciones = JustificacionController::ctrListarPorPersonal($id_usuario);

$tipos = ['medico' => 'Médico', 'personal' => 'Personal', 'permiso' => 'Permiso', 'otro' => 'Otro'];
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Mis Justificaciones</h1>
            <p class="current-date">Presenta y consulta tus justificaciones de inasistencia</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaJustificacion">
                <i class="ph ph-plus"></i> Nueva Justificación
            </button>
        </div>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header">
            <h2>Mis Justificaciones</h2>
            <span class="badge"><?php echo count($justificaciones); ?> registro(s)</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Adjunto</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($justificaciones)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">No has creado ninguna justificación aún.</td></tr>
                    <?php else: $i = 1; ?>
                    <?php foreach ($justificaciones as $j):
                        $estado = $j["aprobado_por"] === null ? 'pendiente' : ($j["aprobado_por"] == 0 ? 'rechazado' : 'aprobado');
                        $colorEstado = $estado === 'aprobado' ? '#16a34a' : ($estado === 'rechazado' ? '#dc2626' : '#f59e0b');
                    ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($j["fecha"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span style="background:var(--bg-input); color:var(--text-main); padding:2px 8px; border-radius:8px; font-size:0.75rem;"><?php echo htmlspecialchars($tipos[$j["tipo"]] ?? $j["tipo"], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($j["motivo"], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($j["motivo"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php
                            $archivos = $j["documento_adjunto"] ? array_filter(array_map("trim", explode(",", $j["documento_adjunto"]))) : [];
                            if ($archivos): foreach ($archivos as $a):
                            ?><a href="adjuntos_justificaciones/<?php echo rawurlencode($a); ?>" target="_blank" style="color:var(--color-primary); margin-right:4px;"><i class="ph ph-paperclip"></i></a>
                            <?php endforeach; else: ?>-<?php endif; ?></td>
                        <td><span style="display:inline-block; background:<?php echo $colorEstado; ?>; color:#fff; padding:2px 10px; border-radius:12px; font-size:0.75rem; text-transform:capitalize;"><?php echo $estado; ?></span></td>
                        <td>
                            <a href="index.php?ruta=exportar_justificacion_pdf&id=<?php echo $j["id"]; ?>" class="btn btn-sm" style="background:#0284c7; color:#fff; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8rem;" target="_blank"><i class="ph ph-file-pdf"></i> PDF</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Nueva Justificación (Personal) -->
<div id="modalNuevaJustificacion" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h2>Nueva Justificación</h2>
            <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?php require_once "helpers/CsrfHelper.php"; echo CsrfHelper::field(); ?>
            <div class="modal-body">
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


