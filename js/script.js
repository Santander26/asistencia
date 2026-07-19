/**
 * Sistema de Asistencia - Lógica Base (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    // Toggle Password Visibility en Login
    const togglePasswordBtn = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            // Cambiar icono
            const icon = togglePasswordBtn.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('ph-eye');
                icon.classList.add('ph-eye-slash');
            } else {
                icon.classList.remove('ph-eye-slash');
                icon.classList.add('ph-eye');
            }
        });
    }


    // --- 2. Funcionalidad del Theme (Modo Oscuro/Claro) ---
    // Make sure we select all possible toggle buttons if there are multiple, but use the first one for logic
    const themeToggleBtn = document.getElementById('theme-toggle');
    const body = document.body;

    // Revisar preferencia guardada
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        body.classList.add('dark-theme');
        updateThemeIcon(true);
    } else if (currentTheme === 'light') {
        body.classList.remove('dark-theme');
        updateThemeIcon(false);
    } else {
        // Revisar preferencia del sistema operativo si no hay elección guardada
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            body.classList.add('dark-theme');
            updateThemeIcon(true);
        }
    }

    if (themeToggleBtn) {
        // Remove old listener if any and add a clean new one
        themeToggleBtn.addEventListener('click', () => {
            body.classList.toggle('dark-theme');
            const isDark = body.classList.contains('dark-theme');

            // Guardar preferencia
            localStorage.setItem('theme', isDark ? 'dark' : 'light');

            // Actualizar icono
            updateThemeIcon(isDark);
        });
    }

    function updateThemeIcon(isDark) {
        if (!themeToggleBtn) return;
        const icon = themeToggleBtn.querySelector('i');
        if (!icon) return;

        if (isDark) {
            icon.classList.remove('ph-moon');
            icon.classList.add('ph-sun');
        } else {
            icon.classList.remove('ph-sun');
            icon.classList.add('ph-moon');
        }
    }


    // --- 3. Funcionalidad del Sidebar (Mobile) ---
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const toggleSidebarBtn = document.getElementById('toggle-sidebar-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');

    function showSidebar() {
        sidebar.classList.add('show-sidebar');
        if (sidebarOverlay) sidebarOverlay.classList.add('show');
    }

    function hideSidebar() {
        sidebar.classList.remove('show-sidebar');
        if (sidebarOverlay) sidebarOverlay.classList.remove('show');
    }

    if (toggleSidebarBtn && sidebar) {
        toggleSidebarBtn.addEventListener('click', showSidebar);
    }

    if (closeSidebarBtn && sidebar) {
        closeSidebarBtn.addEventListener('click', hideSidebar);
    }

    // Cerrar sidebar al hacer clic fuera en móvil
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('show-sidebar')) {
            if (!sidebar.contains(e.target) && !toggleSidebarBtn.contains(e.target)) {
                hideSidebar();
            }
        }
    });

    // --- 3.5. Toggle Sidebar Desktop ---
    const toggleSidebarDesktopBtn = document.getElementById('toggle-sidebar-btn');
    const mainWrapper = document.querySelector('.main-wrapper');

    if (toggleSidebarDesktopBtn && sidebar && mainWrapper) {
        // Revisar estado guardado
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainWrapper.classList.add('collapsed');
            toggleSidebarDesktopBtn.setAttribute('title', 'Mostrar menú');
            // keep the same icon
            toggleSidebarDesktopBtn.querySelector('i').className = 'ph ph-list';
        }

        toggleSidebarDesktopBtn.addEventListener('click', () => {
            const isCollapsed = sidebar.classList.contains('collapsed');
            
            if (isCollapsed) {
                // Expandir
                sidebar.classList.remove('collapsed');
                mainWrapper.classList.remove('collapsed');
                toggleSidebarDesktopBtn.setAttribute('title', 'Ocultar menú');
                toggleSidebarDesktopBtn.querySelector('i').className = 'ph ph-list';
                localStorage.setItem('sidebarCollapsed', 'false');
            } else {
                // Colapsar
                sidebar.classList.add('collapsed');
                mainWrapper.classList.add('collapsed');
                toggleSidebarDesktopBtn.setAttribute('title', 'Mostrar menú');
                toggleSidebarDesktopBtn.querySelector('i').className = 'ph ph-list';
                localStorage.setItem('sidebarCollapsed', 'true');
            }
        });
    }


    // --- 4. Configurar Fecha Actual ---
    const dateElement = document.getElementById('current-date');
    if (dateElement) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const today = new Date();
        // Capitalizamos primera letra
        let dateString = today.toLocaleDateString('es-ES', options);
        dateString = dateString.charAt(0).toUpperCase() + dateString.slice(1);
        dateElement.textContent = dateString;
    }
});

// --- Notification Dropdown Toggle ---
const notifBtn = document.getElementById('notification-btn');
const notifDropdown = document.getElementById('notification-dropdown');
if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        notifDropdown.classList.toggle('show');
    });
    document.addEventListener('click', (e) => {
        if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
            notifDropdown.classList.remove('show');
        }
    });
}

// Formatear fechas
const dateElement = document.getElementById('current-date');
if (dateElement) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const today = new Date();
    dateElement.textContent = today.toLocaleDateString('es-ES', options).replace(/^\w/, (c) => c.toUpperCase());
}

// ==========================================
// MODAL LOGIC 
// ==========================================
// Botones para abrir modal: deben tener data-toggle="modal" y data-target="#miModal"
const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
const modals = document.querySelectorAll('.modal');
const closeButtons = document.querySelectorAll('.close-modal, [data-dismiss="modal"]');

// Abrir modal
modalTriggers.forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = trigger.getAttribute('data-target');
        const targetModal = document.querySelector(targetId);
        if (targetModal) {
            targetModal.classList.add('show');
            document.body.style.overflow = 'hidden'; // prevent background scrolling
        }
    });
});

// Cerrar modal con botón
closeButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
        const modal = btn.closest('.modal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
});

// Cerrar modal pinchando fuera (backdrop)
modals.forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
});


