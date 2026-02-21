/**
 * Archivo JavaScript principal
 * MCI Madrid Colombia - Sistema de Gestión
 */

// Confirmación para eliminaciones
document.addEventListener('DOMContentLoaded', function() {
    const appShell = document.querySelector('.app-shell');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarStateKey = 'mci.sidebarCollapsed';

    function setSidebarCollapsed(collapsed) {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        if (appShell) {
            appShell.classList.toggle('sidebar-collapsed', collapsed);
        }
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

    setSidebarCollapsed(getStoredSidebarState());

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const collapsed = !document.body.classList.contains('sidebar-collapsed');
            setSidebarCollapsed(collapsed);
            saveSidebarState(collapsed);
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
