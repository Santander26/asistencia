<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Historial de Bajas</h1>
            <p class="current-date">Registro de desactivaciones y reactivaciones del personal.</p>
        </div>
    </div>

    <div class="widget widget-lg table-widget">
        <div class="widget-header">
            <h2>Todas las Bajas</h2>
            <div class="widget-filters">
                <input type="text" id="search-input" placeholder="Buscar por nombre o documento" aria-label="Buscar">
                <select id="filter-estado" aria-label="Filtrar por estado">
                    <option value="">Todos</option>
                    <option value="activo">Reactivados</option>
                    <option value="inactivo">A&uacute;n inactivos</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Empleado</th>
                        <th>Documento</th>
                        <th>Cargo</th>
                        <th>Motivo</th>
                        <th>Descripci&oacute;n</th>
                        <th>Fecha Baja</th>
                        <th>Registrado por</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    require_once "models/HistorialBajaModel.php";
                    $bajas = HistorialBajaModel::mdlListar();

                    foreach ($bajas as $key => $b) {
                        $reactivado = $b["fecha_reactivacion"] !== null;
                        $estadoBadge = $reactivado
                            ? '<span class="status-badge status-green">Reactivado</span>'
                            : '<span class="status-badge status-red">Inactivo</span>';
                        $fechaReactivacion = $reactivado
                            ? '<br><small style="color:var(--text-muted);">Reactivado: ' . date("d/m/Y", strtotime($b["fecha_reactivacion"])) . '</small>'
                            : '';

                        echo '<tr data-estado="' . ($reactivado ? 'activo' : 'inactivo') . '">
                            <td>' . ($key + 1) . '</td>
                            <td><strong>' . htmlspecialchars($b["nombre"] . ' ' . $b["apellido"], ENT_QUOTES, 'UTF-8') . '</strong></td>
                            <td>' . htmlspecialchars($b["documento_identidad"], ENT_QUOTES, 'UTF-8') . '</td>
                            <td>' . htmlspecialchars($b["nombre_cargo"] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            <td><span class="status-badge" style="background:var(--clr-yellow-light);color:var(--clr-yellow-dark);">' . htmlspecialchars($b["motivo"], ENT_QUOTES, 'UTF-8') . '</span></td>
                            <td style="max-width:250px;">' . nl2br(htmlspecialchars($b["descripcion"] ?? '', ENT_QUOTES, 'UTF-8')) . '</td>
                            <td>' . date("d/m/Y", strtotime($b["fecha_baja"])) . $fechaReactivacion . '</td>
                            <td>' . htmlspecialchars($b["usuario_nombre"] . ' ' . $b["usuario_apellido"], ENT_QUOTES, 'UTF-8') . '</td>
                            <td>' . $estadoBadge . '</td>
                        </tr>';
                    }

                    if (empty($bajas)) {
                        echo '<tr><td colspan="9" style="text-align:center; padding:2rem; color:var(--text-muted);">No hay registros de bajas.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            <div class="pagination-controls" id="pagination-controls"></div>
        </div>
    </div>
</main>

<script>
var currentPage = 1;
var rowsPerPage = 10;
var filteredRows = [];

function filterRows() {
    var allRows = Array.from(document.querySelectorAll('tbody tr'));
    var searchTerm = (document.getElementById('search-input')?.value || '').toLowerCase();
    var filterEstado = document.getElementById('filter-estado')?.value || '';
    if (allRows.length === 1 && allRows[0].querySelector('td[colspan]')) {
        filteredRows = [];
        return;
    }
    filteredRows = allRows.filter(function(row) {
        var text = row.textContent.toLowerCase();
        var estado = row.dataset.estado || '';
        var matchesSearch = searchTerm === '' || text.includes(searchTerm);
        var matchesFilter = filterEstado === '' || estado === filterEstado;
        return matchesSearch && matchesFilter;
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
    if (!controls) return;
    controls.innerHTML = '';
    var totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    if (totalPages < 1) return;
    var prev = document.createElement('button');
    prev.textContent = 'Anterior';
    prev.className = 'btn btn-outline';
    prev.disabled = currentPage === 1;
    prev.addEventListener('click', function() { if (currentPage > 1) { currentPage--; displayPage(); } });
    controls.appendChild(prev);
    for (var i = 1; i <= totalPages; i++) {
        (function(page) {
            var btn = document.createElement('button');
            btn.textContent = page;
            btn.className = 'page-btn';
            if (page === currentPage) btn.classList.add('active');
            btn.addEventListener('click', function() { currentPage = page; displayPage(); });
            controls.appendChild(btn);
        })(i);
    }
    var next = document.createElement('button');
    next.textContent = 'Siguiente';
    next.className = 'btn btn-outline';
    next.disabled = currentPage === totalPages;
    next.addEventListener('click', function() { if (currentPage < totalPages) { currentPage++; displayPage(); } });
    controls.appendChild(next);
}

function applyFilters() {
    filterRows();
    currentPage = 1;
    displayPage();
}

document.addEventListener('DOMContentLoaded', function() {
    applyFilters();
    document.getElementById('search-input')?.addEventListener('input', applyFilters);
    document.getElementById('filter-estado')?.addEventListener('change', applyFilters);
});
</script>
