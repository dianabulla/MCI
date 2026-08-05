/**
 * Archivo JavaScript principal
 * MCI Madrid Colombia - Sistema de Gestión
 */

// Confirmación para eliminaciones
document.addEventListener('DOMContentLoaded', function() {
    const appShell = document.querySelector('.app-shell');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarArrowToggle = document.getElementById('sidebarArrowToggle');
    const quickAccessToggle = document.getElementById('quickAccessToggle');
    const quickAccessModules = document.getElementById('quickAccessModules');
    const accountMenuToggle = document.getElementById('accountMenuToggle');
    const accountSwitchMenu = document.getElementById('accountSwitchMenu');
    const sidebarStateKey = 'mci.sidebarCollapsed';
    const quickAccessStateKey = 'mci.quickAccessCollapsed';

    function syncSidebarToggleUi(collapsed) {
        if (!sidebarArrowToggle) {
            return;
        }

        const icon = sidebarArrowToggle.querySelector('i');
        const isMobileTopMenu = window.matchMedia('(max-width: 1024px)').matches;
        if (icon) {
            if (isMobileTopMenu) {
                icon.className = collapsed ? 'bi bi-list' : 'bi bi-x-lg';
            } else {
                icon.className = collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
            }
        }

        if (isMobileTopMenu) {
            sidebarArrowToggle.setAttribute('aria-label', collapsed ? 'Abrir menú' : 'Cerrar menú');
            sidebarArrowToggle.setAttribute('title', collapsed ? 'Abrir menú' : 'Cerrar menú');
        } else {
            sidebarArrowToggle.setAttribute('aria-label', collapsed ? 'Mostrar menú lateral' : 'Ocultar menú lateral');
            sidebarArrowToggle.setAttribute('title', collapsed ? 'Mostrar menú lateral' : 'Ocultar menú lateral');
        }
    }

    function setSidebarCollapsed(collapsed) {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        if (appShell) {
            appShell.classList.toggle('sidebar-collapsed', collapsed);
        }
        syncSidebarToggleUi(collapsed);
    }

    function getStoredSidebarState() {
        try {
            return localStorage.getItem(sidebarStateKey) === '1';
        } catch (error) {
            return false;
        }
    }

    function saveSidebarState(collapsed) {
        try {
            localStorage.setItem(sidebarStateKey, collapsed ? '1' : '0');
        } catch (error) {
            // Ignorar fallos de almacenamiento
        }
    }

    function setQuickAccessCollapsed(collapsed) {
        if (!quickAccessToggle || !quickAccessModules) {
            return;
        }

        quickAccessModules.hidden = collapsed;
        quickAccessToggle.classList.toggle('is-open', !collapsed);
        quickAccessToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    }

    function getStoredQuickAccessState() {
        try {
            const value = localStorage.getItem(quickAccessStateKey);
            if (value === null) {
                return true;
            }
            return value === '1';
        } catch (error) {
            return true;
        }
    }

    function saveQuickAccessState(collapsed) {
        try {
            localStorage.setItem(quickAccessStateKey, collapsed ? '1' : '0');
        } catch (error) {
            // Ignorar fallos de almacenamiento
        }
    }

    function isMobileTopMenu() {
        return window.matchMedia('(max-width: 1024px)').matches;
    }

    const initialCollapsed = getStoredSidebarState();
    if (isMobileTopMenu() && !initialCollapsed) {
        setSidebarCollapsed(true);
        saveSidebarState(true);
    } else {
        setSidebarCollapsed(initialCollapsed);
    }

    if (quickAccessToggle && quickAccessModules) {
        const quickAccessCollapsed = getStoredQuickAccessState();
        setQuickAccessCollapsed(quickAccessCollapsed);

        quickAccessToggle.addEventListener('click', function() {
            const collapsed = !quickAccessModules.hidden;
            setQuickAccessCollapsed(collapsed);
            saveQuickAccessState(collapsed);
        });
    }

    window.addEventListener('resize', function() {
        const collapsed = document.body.classList.contains('sidebar-collapsed');
        if (isMobileTopMenu() && !collapsed) {
            setSidebarCollapsed(true);
            saveSidebarState(true);
            return;
        }
        syncSidebarToggleUi(collapsed);
    });

    document.addEventListener('click', function(event) {
        if (!isMobileTopMenu() || document.body.classList.contains('sidebar-collapsed')) {
            return;
        }
        const sidebar = document.getElementById('appSidebar');
        const target = event.target;
        if (!sidebar || !sidebarArrowToggle) {
            return;
        }
        if (sidebar.contains(target) || sidebarArrowToggle.contains(target)) {
            return;
        }
        setSidebarCollapsed(true);
        saveSidebarState(true);
    });

    const sidebarLinks = document.querySelectorAll('.sidebar-nav .sidebar-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (!isMobileTopMenu()) {
                return;
            }

            setSidebarCollapsed(true);
            saveSidebarState(true);
        });
    });

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const collapsed = !document.body.classList.contains('sidebar-collapsed');
            setSidebarCollapsed(collapsed);
            saveSidebarState(collapsed);
        });
    }

    if (sidebarArrowToggle) {
        if (!appShell) {
            sidebarArrowToggle.style.display = 'none';
        } else {
            sidebarArrowToggle.addEventListener('click', function() {
                const collapsed = !document.body.classList.contains('sidebar-collapsed');
                setSidebarCollapsed(collapsed);
                saveSidebarState(collapsed);
            });
        }
    }

    if (accountMenuToggle && accountSwitchMenu) {
        accountMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const willOpen = !accountSwitchMenu.classList.contains('open');
            accountSwitchMenu.classList.toggle('open', willOpen);
            accountSwitchMenu.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
        });

        accountSwitchMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        document.addEventListener('click', function() {
            accountSwitchMenu.classList.remove('open');
            accountSwitchMenu.setAttribute('aria-hidden', 'true');
        });
    }

    // Confirmación de eliminación
    const deleteLinks = document.querySelectorAll('a[href*="eliminar"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este registro?')) {
                e.preventDefault();
            }
        });
    });

    // Validación básica de formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#ced4da';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos requeridos.');
            }
        });
    });

    // Resaltar link activo en navegación
    const currentUrl = window.location.href;
    const navLinks = document.querySelectorAll('.main-nav a');
    navLinks.forEach(link => {
        if (currentUrl.includes(link.getAttribute('href'))) {
            link.style.background = 'rgba(255,255,255,0.3)';
        }
    });

    // Convertir tablas a formato tarjeta en móvil usando data-label
    const tables = document.querySelectorAll('.data-table, .table');
    tables.forEach(table => {
        const headerCells = table.querySelectorAll('thead th');
        if (!headerCells.length) {
            return;
        }

        const headers = Array.from(headerCells).map(th => th.textContent.trim());
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (!cell.hasAttribute('data-label')) {
                    cell.setAttribute('data-label', headers[index] || 'Dato');
                }
            });
        });
    });

    // Vista compacta en móvil para listas de Personas/Ganar (Nombre + Ver + Flecha)
    if (window.matchMedia('(max-width: 1024px)').matches) {
        const personaTables = document.querySelectorAll('.mobile-persona-accordion');

        personaTables.forEach(table => {
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                if (row.querySelector('.mobile-persona-summary')) {
                    return;
                }

                const cells = row.querySelectorAll('td');
                if (!cells.length || cells.length === 1 && cells[0].hasAttribute('colspan')) {
                    return;
                }

                const nameCell = cells[0];
                const summary = document.createElement('div');
                summary.className = 'mobile-persona-summary';

                const name = document.createElement('div');
                name.className = 'mobile-persona-name';
                name.textContent = (nameCell.textContent || '').trim();
                summary.appendChild(name);

                const actionsCell = cells[cells.length - 1];
                const actionBtn = actionsCell ? actionsCell.querySelector('.btn-info, .btn') : null;
                if (actionBtn) {
                    const actionClone = actionBtn.cloneNode(true);
                    actionClone.classList.add('mobile-persona-action');
                    actionClone.removeAttribute('id');
                    summary.appendChild(actionClone);
                }

                const toggleBtn = document.createElement('button');
                toggleBtn.type = 'button';
                toggleBtn.className = 'mobile-persona-toggle';
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.setAttribute('title', 'Mostrar detalles');
                toggleBtn.innerHTML = '<i class="bi bi-chevron-down"></i>';
                summary.appendChild(toggleBtn);

                row.classList.add('mobile-persona-row', 'mobile-persona-collapsed');
                cells.forEach(cell => cell.classList.add('mobile-persona-detail'));
                row.prepend(summary);

                toggleBtn.addEventListener('click', function() {
                    const collapsed = row.classList.toggle('mobile-persona-collapsed');
                    toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    toggleBtn.setAttribute('title', collapsed ? 'Mostrar detalles' : 'Ocultar detalles');
                });
            });
        });
    }

    // Removido: lógica de interacción de la campana de notificaciones (ganar-alert)
});

// Función para búsqueda en tablas
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let txtValue = tr[i].textContent || tr[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = '';
        } else {
            tr[i].style.display = 'none';
        }
    }
}

// Función para formatear fechas
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
}

// Función para validar email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Función para validar teléfono (Colombia)
function validatePhone(phone) {
    const re = /^[0-9]{10}$/;
    return re.test(phone.replace(/\s/g, ''));
}

// Dropdown de botón en page-actions
function toggleBtnDropdown(id) {
    const wrap = document.getElementById(id);
    const isOpen = wrap.classList.contains('open');
    document.querySelectorAll('.btn-dropdown-wrap.open').forEach(function(el) { el.classList.remove('open'); });
    if (!isOpen) wrap.classList.add('open');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.btn-dropdown-wrap')) {
        document.querySelectorAll('.btn-dropdown-wrap.open').forEach(function(el) { el.classList.remove('open'); });
    }
});

/**
 * Sesión: redirigir si el servidor responde session_expired (inactividad > 30 min).
 */
(function () {
    'use strict';

    function loginUrlSesionExpirada() {
        var body = document.body;
        if (body && body.getAttribute('data-login-expired-url')) {
            return body.getAttribute('data-login-expired-url');
        }
        return null;
    }

    function irALoginSesionExpirada() {
        var url = loginUrlSesionExpirada();
        if (url) {
            window.location.href = url;
        }
    }

    if (typeof window.fetch === 'function') {
        var fetchOriginal = window.fetch;
        window.fetch = function () {
            return fetchOriginal.apply(this, arguments).then(function (response) {
                if (response.status === 401) {
                    var clone = response.clone();
                    clone.json().then(function (data) {
                        if (data && data.session_expired) {
                            irALoginSesionExpirada();
                        }
                    }).catch(function () { /* no JSON */ });
                }
                return response;
            });
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        var body = document.body;
        if (!body) {
            return;
        }
        var timeoutSec = parseInt(body.getAttribute('data-session-timeout') || '0', 10);
        var loginUrl = body.getAttribute('data-login-expired-url');
        if (!timeoutSec || !loginUrl) {
            return;
        }

        var avisoMs = Math.max(60000, (timeoutSec - 120) * 1000);
        var cierreMs = timeoutSec * 1000;
        var timerAviso = null;
        var timerCierre = null;

        function reiniciarTimers() {
            clearTimeout(timerAviso);
            clearTimeout(timerCierre);
            timerAviso = setTimeout(function () {
                if (window.confirm('Tu sesión expirará en 2 minutos por inactividad. ¿Deseas continuar?')) {
                    reiniciarTimers();
                }
            }, avisoMs);
            timerCierre = setTimeout(function () {
                window.location.href = loginUrl;
            }, cierreMs);
        }

        ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach(function (evt) {
            document.addEventListener(evt, reiniciarTimers, { passive: true });
        });
        reiniciarTimers();
    });
})();
