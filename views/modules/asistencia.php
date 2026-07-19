<?php
require_once "controllers/AsistenciaController.php";
require_once "models/MovimientoModel.php";

$ultimoMensaje = null;
$ultimoPersonal = null;
$movimientosHoy = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["codigoAsistencia"])) {
    $codigo = trim($_POST["codigoAsistencia"]);
    AsistenciaController::ctrMarcarAsistencia();
}
?>
<main class="main-content"
    style="display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="widget"
        style="width: 100%; max-width: 500px; padding: 3rem; text-align: center; box-shadow: var(--shadow-xl);">

        <div style="margin-bottom: 2rem;">
            <i class="ph ph-clock text-blue" style="font-size: 4rem; margin-bottom: 1rem;"></i>
            <h1 style="font-size: 2.5rem; color: var(--clr-text-title); font-weight: 700;">Registro de Asistencia</h1>
            <p id="kiosko-time"
                style="font-size: 1.5rem; color: var(--clr-text-muted); font-family: monospace; letter-spacing: 2px;">
                00:00:00</p>
            <p id="kiosko-date" style="color: var(--clr-text-muted); text-transform: capitalize;"></p>
        </div>

        <form method="post" id="formMarcaje">
            <?php require_once "helpers/CsrfHelper.php"; echo CsrfHelper::field(); ?>
            <div class="input-group">
                <div class="input-wrapper" style="border-radius: 50px; overflow: hidden; box-shadow: var(--shadow-md);">
                    <i class="ph ph-identification-card" style="font-size: 1.5rem; margin-left: 10px;"></i>
                    <input type="text" name="codigoAsistencia" id="codigoAsistencia" required autofocus
                        placeholder="Ingresa tu DNI / RUT..."
                        style="border: none; padding: 1.5rem 1.5rem 1.5rem 3.5rem; font-size: 1.25rem; text-align: center;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block"
                style="font-size: 1.25rem; padding: 1rem; border-radius: 50px; margin-top: 1.5rem;">
                <i class="ph ph-check-circle"></i> Marcar Asistencia
            </button>
        </form>

        <div id="mensajeAsistencia" style="margin-top: 1.5rem;"></div>

    </div>
</main>

<script>
    function startKioskClock() {
        const timeDisplay = document.getElementById('kiosko-time');
        const dateDisplay = document.getElementById('kiosko-date');
        if (!timeDisplay) return;

        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const today = new Date();
        dateDisplay.textContent = today.toLocaleDateString('es-ES', options);

        setInterval(() => {
            const now = new Date();
            timeDisplay.textContent = now.toLocaleTimeString('es-ES', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }, 1000);
    }
    startKioskClock();
</script>
