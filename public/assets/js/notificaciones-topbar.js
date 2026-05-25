(function () {
    'use strict';

    var wrap = document.querySelector('[data-fb-notif]');
    if (!wrap) {
        return;
    }

    var bell = document.getElementById('fbNotifBell');
    var panel = document.getElementById('fbNotifPanel');
    if (!bell || !panel) {
        return;
    }

    function cerrarPanel() {
        panel.setAttribute('hidden', 'hidden');
        bell.setAttribute('aria-expanded', 'false');
    }

    function abrirPanel() {
        panel.removeAttribute('hidden');
        bell.setAttribute('aria-expanded', 'true');
    }

    function panelAbierto() {
        return !panel.hasAttribute('hidden');
    }

    bell.addEventListener('click', function (event) {
        event.stopPropagation();
        if (panelAbierto()) {
            cerrarPanel();
        } else {
            abrirPanel();
        }
    });

    panel.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function () {
        if (panelAbierto()) {
            cerrarPanel();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panelAbierto()) {
            cerrarPanel();
            bell.focus();
        }
    });
})();
