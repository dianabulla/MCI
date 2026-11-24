(function () {
    'use strict';

    var enabled = document.body && document.body.getAttribute('data-product-tour') === '1';
    if (!enabled || typeof window.driver === 'undefined' || !window.driver.js || !window.driver.js.driver) {
        return;
    }

    var STORAGE_PREFIX = 'mci_product_tour_';
    var ACTIVE_KEY = 'mci_tour_curriculum_active';
    var profile = (document.body.getAttribute('data-product-tour-profile') || '').trim();
    var urlSearch = String(window.location.search || '');
    var activeDriver = null;
    var clickCleanup = null;

    function qs(selector) {
        return document.querySelector(selector);
    }

    function exists(selector) {
        return !!qs(selector);
    }

    function hashString(str) {
        var h = 5381;
        for (var i = 0; i < str.length; i++) {
            h = ((h << 5) + h) ^ str.charCodeAt(i);
        }
        return (h >>> 0).toString(36);
    }

    function markDone(key) {
        try { localStorage.setItem(key, '1'); } catch (e) { /* ignore */ }
    }

    function wasDone(key) {
        try { return localStorage.getItem(key) === '1'; } catch (e) { return false; }
    }

    function readActive() {
        try {
            var raw = sessionStorage.getItem(ACTIVE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function writeActive(state) {
        try { sessionStorage.setItem(ACTIVE_KEY, JSON.stringify(state)); } catch (e) { /* ignore */ }
    }

    function clearActive() {
        try { sessionStorage.removeItem(ACTIVE_KEY); } catch (e) { /* ignore */ }
    }

    /**
     * Módulos del menú visible → guía interactiva.
     * enterSel: enlace del sidebar (obligatorio pulsar para entrar).
     * route: detecta si ya estamos dentro.
     * explore: pasos dentro del módulo (acciones reales).
     */
    var MODULES = [
        {
            id: 'home',
            label: 'Inicio',
            enterSel: '[data-tour="sidebar-home"]',
            route: /[?&]url=home(?:&|$)/,
            explore: [
                {
                    element: '[data-tour="home-dashboard"]',
                    title: 'Panel de inicio',
                    description: 'Estas tarjetas resumen lo que puedes gestionar. Revisa las que aparecen según tu rol.',
                    side: 'bottom'
                },
                {
                    element: '[data-tour="home-dashboard"] .home-dashboard-card-link, [data-tour="home-dashboard"] a.dashboard-card',
                    title: 'Abrir un módulo desde Inicio',
                    description: 'Pulsa una tarjeta para entrar a ese módulo. (Solo señalamos la acción; el recorrido completo sigue por el menú.)',
                    side: 'top',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'personas',
            label: 'Escalera del éxito',
            enterSel: '[data-tour="sidebar-ganar"]',
            route: /[?&]url=personas(?:&|\/|$|%2F)/,
            explore: [
                {
                    element: '[data-tour="page-ganar"], [data-tour="page-personas"]',
                    title: 'Escalera / Almas ganadas',
                    description: 'Aquí ves personas nuevas, filtros por ministerio/líder/semana y el seguimiento de consolidación.',
                    side: 'bottom'
                },
                {
                    element: '[data-tour="personas-acciones"]',
                    title: 'Cambiar de vista',
                    description: 'Pulsa «Escalera del éxito» o «Almas ganadas» para cambiar la vista. También puedes abrir reportes si tienes permiso.',
                    side: 'left',
                    requireClick: true,
                    preventNav: true
                },
                {
                    element: '#filtro_ministerio, [data-tour="personas-filtros"] select, .form-container select',
                    title: 'Usar un filtro',
                    description: 'Abre este filtro (por ejemplo ministerio) y elige una opción. Así practicas cómo buscar personas.',
                    side: 'top',
                    requireClick: true,
                    preventNav: true
                },
                {
                    element: '[data-tour="personas-filtrar"]',
                    title: 'Aplicar filtros',
                    description: 'Pulsa Filtrar para aplicar. La página se recargará y seguiremos la guía.',
                    side: 'top',
                    requireClick: true,
                    preventNav: false
                }
            ]
        },
        {
            id: 'celulas',
            label: 'Células',
            enterSel: '[data-tour="sidebar-celulas"]',
            route: /[?&]url=celulas(?:&|\/|$|%2F)/,
            explore: [
                {
                    element: '[data-tour="page-celulas"]',
                    title: 'Listado de células',
                    description: 'Según tu rol verás tus células, tu red o todas. Desde aquí filtras, reportas y abres el celugrama.',
                    side: 'bottom'
                },
                {
                    element: '[data-tour="celulas-filtros"] #filtro_ministerio, [data-tour="celulas-filtros"] #filtro_lider',
                    title: 'Filtrar células',
                    description: 'Pulsa el filtro de ministerio o de líder y elige una opción.',
                    side: 'top',
                    requireClick: true,
                    preventNav: true
                },
                {
                    element: '[data-tour="celulas-filtrar"]',
                    title: 'Aplicar filtro',
                    description: 'Pulsa Filtrar para ver el resultado. Luego te mostramos las acciones de cada célula.',
                    side: 'top',
                    requireClick: true,
                    preventNav: false
                },
                {
                    element: '[data-tour="celulas-acciones"]',
                    title: 'Acciones del módulo',
                    description: 'Aquí están «Reporte de Células» y «+ Nueva célula» (si tu rol lo permite). Revísalos.',
                    side: 'left'
                },
                {
                    element: '[data-tour="celulas-celugrama"]',
                    title: 'Abrir celugrama',
                    description: 'Pulsa el icono de celugrama de una célula para ver miembros y seguimiento. (Señalamos la acción sin salir del recorrido.)',
                    side: 'left',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'programas',
            label: 'Programas',
            enterSel: '[data-tour="sidebar-programas"]',
            route: /[?&]url=programas(?:&|\/|$|%2F)/,
            explore: [
                {
                    element: '[data-tour="page-programas"], .programas-nav',
                    title: 'Hub de Programas',
                    description: 'Elige Universidad de la Vida o Cap. Destino y luego asistencias, pagos o evaluaciones.',
                    side: 'bottom'
                },
                {
                    element: '.programas-nav a, [data-tour="page-programas"] a',
                    title: 'Navegar en Programas',
                    description: 'Pulsa un enlace o tarjeta del módulo para practicar la navegación.',
                    side: 'bottom',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'discipular',
            label: 'Discipular',
            enterSel: '[data-tour="sidebar-discipular"]',
            route: /[?&]url=discipular(?:&|\/|$|%2F)/,
            explore: [
                {
                    element: '[data-tour="page-discipular"]',
                    title: 'Ministerios / redes',
                    description: 'Cada ministerio es una red. Desde aquí abres el equipo de 12 y las metas.',
                    side: 'bottom'
                },
                {
                    element: '[data-tour="discipular-acciones"], [data-tour="discipular-nuevo"]',
                    title: 'Acciones de Discipular',
                    description: 'Usa Volver o «+ Nuevo Ministerio» según tu permiso. Pulsa uno para reconocerlo.',
                    side: 'left',
                    requireClick: true,
                    preventNav: true
                },
                {
                    element: '.ministerios-lista-table a, .data-table a',
                    title: 'Abrir un ministerio',
                    description: 'Pulsa el nombre o enlace de un ministerio en la tabla para ver cómo se entra al detalle.',
                    side: 'top',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'teen',
            label: 'Teens',
            enterSel: '[data-tour="sidebar-teen"]',
            route: /[?&]url=teen(?:&|\/|$|%2F)/,
            explore: [
                {
                    element: '[data-tour="page-teen"]',
                    title: 'Teens',
                    description: 'Registro de menores y material. Revisa las acciones y formularios de esta pantalla.',
                    side: 'bottom'
                },
                {
                    element: '[data-tour="page-teen"] a, [data-tour="page-teen"] button, .teen-upload-btn',
                    title: 'Interactuar en Teens',
                    description: 'Pulsa un botón o enlace principal de esta pantalla para reconocer las acciones del módulo.',
                    side: 'bottom',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'nehemias',
            label: 'Nehemias',
            enterSel: '[data-tour="sidebar-nehemias"]',
            route: /[?&]url=nehemias(?:&|\/|$|%2F)/,
            explore: [
                {
                    element: '[data-tour="page-nehemias"]',
                    title: 'Nehemias',
                    description: 'Listado y filtros del módulo. Desde aquí entras a campañas o edición según permiso.',
                    side: 'bottom'
                },
                {
                    element: '[data-tour="page-nehemias"] a.btn, [data-tour="page-nehemias"] .btn',
                    title: 'Acciones Nehemias',
                    description: 'Pulsa un botón de acción (por ejemplo WhatsApp Campañas) para ubicarlo. No saldremos del recorrido.',
                    side: 'left',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'talleres',
            label: 'Talleres',
            enterSel: '[data-tour="sidebar-talleres"]',
            route: /[?&]url=talleres(?:&|\/|$|%2F)/,
            explore: [
                {
                    element: '[data-tour="page-talleres"]',
                    title: 'Talleres',
                    description: 'Un formulario por tipo de actividad. El enlace/QR público se mantiene al abrir nuevas fechas.',
                    side: 'bottom'
                },
                {
                    element: '[data-tour="talleres-acciones"] a, [data-tour="talleres-acciones"]',
                    title: 'Acciones de Talleres',
                    description: 'Pulsa Premiación, Servicio social o Nuevo formulario (si aparece) para reconocer las funciones.',
                    side: 'left',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'reportes',
            label: 'Dashboard Ganar',
            enterSel: '[data-tour="sidebar-reportes"]',
            route: /[?&]url=reportes(?:&|\/|$|%2F)/,
            explore: [
                {
                    element: '[data-tour="page-reportes"]',
                    title: 'Dashboard',
                    description: 'Indicadores y gráficas del área ganar/consolidar. Revisa KPIs y filtros de año.',
                    side: 'bottom'
                },
                {
                    element: '.dash-header-actions select, .dash-anio-form select, [data-tour="page-reportes"] select',
                    title: 'Cambiar periodo',
                    description: 'Pulsa el selector de año u otro filtro del dashboard.',
                    side: 'left',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'administracion',
            label: 'Administración',
            enterSel: '[data-tour="sidebar-admin"]',
            route: /[?&]url=(?:cuentas|roles|permisos|auditoria)(?:&|\/|$|%2F)/,
            explore: [
                {
                    element: '[data-tour="page-admin"]',
                    title: 'Administración',
                    description: 'Cuentas, roles y permisos. Los cambios afectan a toda la plataforma.',
                    side: 'bottom'
                },
                {
                    element: '[data-tour="page-admin"] a.btn, [data-tour="page-admin"] .btn',
                    title: 'Acciones de administración',
                    description: 'Pulsa un botón (por ejemplo Nuevo Rol o Permisos) para ubicarlo. No ejecutaremos el alta completa en la guía.',
                    side: 'left',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'cap_destino',
            label: 'Cap. Destino',
            enterSel: '[data-tour="sidebar-cap-destino"]',
            route: /[?&]url=(?:home\/material\/capacitacion-destino|home%2Fmaterial%2Fcapacitacion-destino|programas\/evaluaciones|programas\/tareas)/,
            explore: null
        },
        {
            id: 'material',
            label: 'Material',
            enterSel: '[data-tour="sidebar-material"]',
            route: /[?&]url=home\/material(?:&|$|%2F)(?!capacitacion-destino)/,
            explore: [
                {
                    element: 'main .page-header, main h2, .dashboard-grid',
                    title: 'Centro de material',
                    description: 'Bibliotecas de material por área. Entra a la que necesites desde las tarjetas o enlaces.',
                    side: 'bottom'
                },
                {
                    element: 'main a.dashboard-card, main .card a, main a.btn',
                    title: 'Abrir una biblioteca',
                    description: 'Pulsa un enlace o tarjeta de material para reconocer cómo se entra.',
                    side: 'top',
                    requireClick: true,
                    preventNav: true
                }
            ]
        },
        {
            id: 'servicio_social',
            label: 'Servicio Social',
            enterSel: '[data-tour="sidebar-servicio-social"]',
            route: /[?&]url=talleres\/servicio-social/,
            explore: [
                {
                    element: 'main .page-header, main h2',
                    title: 'Servicio Social',
                    description: 'Citas y atención. Revisa el listado y las acciones disponibles.',
                    side: 'bottom'
                },
                {
                    element: 'main a.btn, main button',
                    title: 'Acciones',
                    description: 'Pulsa un botón principal de esta pantalla.',
                    side: 'bottom',
                    requireClick: true,
                    preventNav: true
                }
            ]
        }
    ];

    function capDestinoExplore() {
        if (profile === 'maestro') {
            return [
                { element: '[data-tour="maestro-bienvenida"], [data-tour="page-header-cap"], #cap-level-selector', title: 'Cap. Destino (profesor)', description: 'Gestionas niveles, módulos, evaluaciones y tareas.', side: 'bottom' },
                { element: '#cap-level-selector .cap-level-card-enter-btn, #cap-level-selector a, #cap-level-selector button', title: 'Entrar a un nivel', description: 'Pulsa Entrar en un nivel.', side: 'bottom', requireClick: true, preventNav: false },
                { element: '#cap-module-selector-grid a, [data-tour="cap-module-selector"] a', title: 'Entrar a un módulo', description: 'Pulsa un módulo para abrir su panel.', side: 'top', requireClick: true, preventNav: false },
                { element: '[data-tour="hub-evaluaciones"]', title: 'Evaluaciones', description: 'Pulsa Evaluaciones para ver creación y notas.', side: 'top', requireClick: true, preventNav: false },
                { element: '[data-tour="hub-tareas"]', title: 'Tareas', description: 'Pulsa Tareas para publicar y calificar.', side: 'top', requireClick: true, preventNav: false }
            ];
        }
        if (profile === 'discipulo') {
            return [
                { element: '#cap-level-selector, [data-tour="sidebar-cap-destino"]', title: 'Tu Cap. Destino', description: 'Aquí presentas evaluaciones y entregas tareas.', side: 'bottom' },
                { element: '#cap-level-selector .cap-level-card-enter-btn, #cap-level-selector a', title: 'Entrar a tu nivel', description: 'Pulsa Entrar.', side: 'left', requireClick: true, preventNav: false },
                { element: '[data-tour="hub-evaluaciones"], [data-tour="lista-evaluaciones-discipulo"]', title: 'Evaluaciones', description: 'Revisa o pulsa el bloque de evaluaciones.', side: 'top', requireClick: true, preventNav: true },
                { element: '[data-tour="hub-tareas"], .cap-tarea-upload-form', title: 'Tareas', description: 'Localiza la entrega de tareas.', side: 'top', requireClick: true, preventNav: true }
            ];
        }
        return [
            { element: '#cap-level-selector, [data-tour="cap-level-selector"]', title: 'Niveles', description: 'Elige un nivel de Cap. Destino.', side: 'bottom' },
            { element: '#cap-level-selector a, #cap-level-selector button, .cap-level-card-enter-btn', title: 'Entrar', description: 'Pulsa Entrar en un nivel.', side: 'bottom', requireClick: true, preventNav: false },
            { element: '#cap-module-selector-grid a, [data-tour="hub-modulo-menu"]', title: 'Módulo', description: 'Pulsa un módulo o una sección del hub.', side: 'top', requireClick: true, preventNav: true }
        ];
    }

    function getModule(id) {
        for (var i = 0; i < MODULES.length; i++) {
            if (MODULES[i].id === id) {
                return MODULES[i];
            }
        }
        return null;
    }

    function getExploreDefs(mod) {
        if (!mod) {
            return [];
        }
        if (mod.id === 'cap_destino') {
            return capDestinoExplore();
        }
        return mod.explore || [];
    }

    function discoverQueue() {
        var queue = [];
        for (var i = 0; i < MODULES.length; i++) {
            var m = MODULES[i];
            if (exists(m.enterSel)) {
                queue.push(m.id);
            }
        }
        return queue;
    }

    function curriculumFingerprint(queue) {
        var parts = queue.slice();
        for (var i = 0; i < queue.length; i++) {
            var mod = getModule(queue[i]);
            var defs = getExploreDefs(mod);
            for (var j = 0; j < defs.length; j++) {
                parts.push(defs[j].element + '\t' + defs[j].title + '\t' + (defs[j].requireClick ? '1' : '0'));
            }
        }
        return hashString(parts.join('\n'));
    }

    function curriculumDoneKey(fp) {
        return STORAGE_PREFIX + 'curriculum_' + fp + '_done';
    }

    function moduleDoneKey(moduleId, defs) {
        var parts = [moduleId];
        for (var i = 0; i < defs.length; i++) {
            parts.push(String(defs[i].element || '') + '\t' + String(defs[i].title || ''));
        }
        return STORAGE_PREFIX + 'mod_' + moduleId + '_' + hashString(parts.join('\n')) + '_done';
    }

    function resolveSelector(selector) {
        if (!selector) {
            return null;
        }
        return qs(selector);
    }

    function stepFromDef(def) {
        var el = resolveSelector(def.element);
        if (!el) {
            return null;
        }
        var desc = def.description || '';
        if (def.requireClick) {
            desc += '<span class="mci-tour-click-hint">Pulsa el elemento resaltado para continuar</span>';
        }
        return {
            element: def.element,
            popover: {
                title: def.title,
                description: desc,
                side: def.side || 'bottom',
                popoverClass: 'mci-driver-popover' + (def.requireClick ? ' mci-driver-popover--click' : '')
            },
            _def: def
        };
    }

    function buildSteps(defs) {
        var steps = [];
        for (var i = 0; i < defs.length; i++) {
            var s = stepFromDef(defs[i]);
            if (s) {
                steps.push(s);
            }
        }
        return steps;
    }

    function destroyDriver() {
        if (typeof clickCleanup === 'function') {
            try { clickCleanup(); } catch (e) { /* ignore */ }
            clickCleanup = null;
        }
        if (activeDriver && typeof activeDriver.destroy === 'function') {
            try { activeDriver.destroy(); } catch (e) { /* ignore */ }
        }
        activeDriver = null;
    }

    function runDriver(steps, options) {
        options = options || {};
        if (!steps.length) {
            if (typeof options.onEmpty === 'function') {
                options.onEmpty();
            }
            return false;
        }

        destroyDriver();
        var canSkip = !!options.canSkip;
        var metaByIndex = steps.map(function (s) { return s._def || null; });
        // Driver.js no garantiza conservar campos custom: pasos limpios
        var driverSteps = steps.map(function (s) {
            return {
                element: s.element,
                popover: s.popover
            };
        });
        var driverApi = window.driver.js.driver;
        var driverObj = driverApi({
            showProgress: true,
            progressText: '{{current}} de {{total}}' + (options.label ? ' · ' + options.label : ''),
            nextBtnText: 'Siguiente',
            prevBtnText: 'Anterior',
            doneBtnText: options.doneBtnText || 'Listo',
            allowClose: canSkip,
            disableActiveInteraction: false,
            overlayOpacity: 0.55,
            stagePadding: 8,
            popoverClass: 'mci-driver-popover' + (canSkip ? '' : ' mci-driver-popover--required'),
            steps: driverSteps,
            onHighlightStarted: function (element) {
                if (typeof clickCleanup === 'function') {
                    try { clickCleanup(); } catch (e) { /* ignore */ }
                    clickCleanup = null;
                }
                var idx = typeof driverObj.getActiveIndex === 'function' ? driverObj.getActiveIndex() : 0;
                var def = metaByIndex[idx];
                var pop = document.querySelector('.driver-popover');
                if (pop) {
                    if (def && def.requireClick) {
                        pop.classList.add('mci-driver-popover--click');
                    } else {
                        pop.classList.remove('mci-driver-popover--click');
                    }
                    if (!canSkip) {
                        pop.classList.add('mci-driver-popover--required');
                    }
                }
                if (!def || !def.requireClick || !element) {
                    return;
                }
                var advanced = false;
                var handler = function (ev) {
                    if (advanced) {
                        return;
                    }
                    advanced = true;
                    if (def.preventNav) {
                        ev.preventDefault();
                        ev.stopPropagation();
                    }
                    element.removeEventListener('click', handler, true);
                    clickCleanup = null;
                    window.setTimeout(function () {
                        var cur = typeof driverObj.getActiveIndex === 'function' ? driverObj.getActiveIndex() : 0;
                        if (cur >= driverSteps.length - 1) {
                            if (typeof options.onComplete === 'function') {
                                options.onComplete();
                            }
                            destroyDriver();
                            return;
                        }
                        driverObj.moveNext();
                    }, def.preventNav ? 0 : 40);
                };
                element.addEventListener('click', handler, true);
                clickCleanup = function () {
                    element.removeEventListener('click', handler, true);
                };
            },
            onNextClick: function () {
                var idx = typeof driverObj.getActiveIndex === 'function' ? driverObj.getActiveIndex() : 0;
                var def = metaByIndex[idx];
                if (def && def.requireClick) {
                    return;
                }
                if (idx >= driverSteps.length - 1) {
                    if (typeof options.onComplete === 'function') {
                        options.onComplete();
                    }
                    destroyDriver();
                    return;
                }
                driverObj.moveNext();
            },
            onDestroyStarted: function () {
                if (!canSkip) {
                    return;
                }
                destroyDriver();
            }
        });
        activeDriver = driverObj;
        driverObj.drive(options.startIndex || 0);
        return true;
    }

    function ensureCurriculumState(forceRestart) {
        var queue = discoverQueue();
        if (!queue.length) {
            return null;
        }
        var fp = curriculumFingerprint(queue);
        var doneKey = curriculumDoneKey(fp);
        var force = forceRestart || /[?&]tour=1(?:&|$)/.test(urlSearch);

        if (!force && wasDone(doneKey)) {
            clearActive();
            return null;
        }

        var state = readActive();
        if (!state || state.fp !== fp || forceRestart) {
            state = {
                fp: fp,
                queue: queue,
                idx: 0,
                phase: 'welcome'
            };
            writeActive(state);
        }
        return state;
    }

    function currentModuleFromUrl() {
        for (var i = 0; i < MODULES.length; i++) {
            if (MODULES[i].route.test(urlSearch)) {
                return MODULES[i];
            }
        }
        return null;
    }

    function advanceAfterModule(state, moduleId) {
        var defs = getExploreDefs(getModule(moduleId));
        markDone(moduleDoneKey(moduleId, defs));
        var idx = state.queue.indexOf(moduleId);
        if (idx < 0) {
            idx = state.idx;
        }
        state.idx = idx + 1;
        state.phase = 'enter';
        writeActive(state);
        if (state.idx >= state.queue.length) {
            markDone(curriculumDoneKey(state.fp));
            clearActive();
            refreshLauncherState();
            window.alert('Guía completada. Ya puedes usar la plataforma con normalidad. El botón Guías (izquierda) sirve para repetir módulos.');
            return;
        }
        window.setTimeout(function () {
            runEnterStep(state);
        }, 400);
    }

    function runWelcome(state) {
        var steps = buildSteps([
            {
                element: '[data-tour="sidebar-brand"]',
                title: 'Guía obligatoria de la plataforma',
                description: 'Vas a entrar a cada módulo de tu menú, ver sus acciones y practicarlas. No se puede saltar la primera vez. Si el flujo cambia, se pedirá de nuevo.',
                side: 'right'
            },
            {
                element: '#sidebarArrowToggle, .app-chrome-bar',
                title: 'Menú en móvil',
                description: 'En pantallas pequeñas abre el menú con este botón. En escritorio el menú ya está visible a la izquierda.',
                side: 'bottom'
            }
        ]);
        runDriver(steps, {
            label: 'Bienvenida',
            canSkip: false,
            onComplete: function () {
                state.phase = 'enter';
                state.idx = 0;
                writeActive(state);
                runEnterStep(state);
            },
            onEmpty: function () {
                state.phase = 'enter';
                writeActive(state);
                runEnterStep(state);
            }
        });
    }

    function runEnterStep(state) {
        if (state.idx >= state.queue.length) {
            markDone(curriculumDoneKey(state.fp));
            clearActive();
            refreshLauncherState();
            return;
        }
        var moduleId = state.queue[state.idx];
        var mod = getModule(moduleId);
        if (!mod || !exists(mod.enterSel)) {
            state.idx += 1;
            writeActive(state);
            runEnterStep(state);
            return;
        }

        // Si ya estamos en el módulo, pasar directo a explorar
        if (mod.route.test(urlSearch)) {
            state.phase = 'explore';
            writeActive(state);
            runExplore(state, mod);
            return;
        }

        var steps = buildSteps([
            {
                element: mod.enterSel,
                title: 'Entra a «' + mod.label + '»',
                description: 'Pulsa este módulo del menú para entrar. Dentro practicarás sus funciones principales.',
                side: 'right',
                requireClick: true,
                preventNav: false
            }
        ]);

        state.phase = 'enter';
        writeActive(state);

        runDriver(steps, {
            label: mod.label,
            canSkip: false,
            onComplete: function () {
                // Si no hubo navegación (enlace raro), forzar explore si ya estamos
                if (mod.route.test(String(window.location.search || ''))) {
                    state.phase = 'explore';
                    writeActive(state);
                    runExplore(state, mod);
                }
            },
            onEmpty: function () {
                state.idx += 1;
                writeActive(state);
                runEnterStep(state);
            }
        });

        // Al hacer click el handler permite navegación; marcar explore para la siguiente página
        var link = qs(mod.enterSel);
        if (link) {
            var markExplore = function () {
                state.phase = 'explore';
                writeActive(state);
            };
            link.addEventListener('click', markExplore, { once: true, capture: true });
        }
    }

    function runExplore(state, mod) {
        var defs = getExploreDefs(mod);
        var steps = buildSteps(defs);
        if (!steps.length) {
            advanceAfterModule(state, mod.id);
            return;
        }

        // Retomar tras reload (p. ej. Filtrar): si hay flag exploreDoneSteps
        var startIndex = 0;
        if (state.exploreResume && state.exploreResume.module === mod.id) {
            startIndex = Math.min(state.exploreResume.step || 0, steps.length - 1);
            delete state.exploreResume;
            writeActive(state);
        }

        runDriver(steps, {
            label: mod.label,
            canSkip: false,
            startIndex: startIndex,
            doneBtnText: 'Módulo listo',
            onComplete: function () {
                advanceAfterModule(state, mod.id);
            }
        });
    }

    function resumeOrStart(force) {
        var state = ensureCurriculumState(!!force);
        if (!state) {
            return false;
        }

        if (state.phase === 'welcome') {
            runWelcome(state);
            return true;
        }

        if (state.phase === 'explore') {
            var mod = getModule(state.queue[state.idx]);
            var onUrl = currentModuleFromUrl();
            if (mod && mod.route.test(urlSearch)) {
                runExplore(state, mod);
                return true;
            }
            if (onUrl && state.queue.indexOf(onUrl.id) === state.idx) {
                runExplore(state, onUrl);
                return true;
            }
            // Quedó explore pero no estamos en el módulo: volver a pedir entrada
            state.phase = 'enter';
            writeActive(state);
            runEnterStep(state);
            return true;
        }

        runEnterStep(state);
        return true;
    }

    function refreshLauncherState() {
        var btn = qs('#mci-tour-launcher');
        if (!btn) {
            return;
        }
        var queue = discoverQueue();
        var fp = curriculumFingerprint(queue);
        var pending = queue.length > 0 && !wasDone(curriculumDoneKey(fp));
        if (pending) {
            btn.classList.add('mci-tour-launcher--required');
            btn.setAttribute('title', 'Guía pendiente: debes recorrer tus módulos');
        } else {
            btn.classList.remove('mci-tour-launcher--required');
            btn.setAttribute('title', 'Volver a ver las guías');
        }
    }

    function showTourMenu() {
        var existing = qs('#mci-tour-menu');
        if (existing) {
            existing.remove();
            return;
        }
        var menu = document.createElement('div');
        menu.id = 'mci-tour-menu';
        menu.className = 'mci-tour-menu';
        menu.innerHTML = '<div class="mci-tour-menu-title">Guías</div>';

        var restart = document.createElement('button');
        restart.type = 'button';
        restart.className = 'mci-tour-menu-item';
        restart.textContent = 'Reiniciar recorrido completo';
        restart.addEventListener('click', function () {
            menu.remove();
            clearActive();
            var queue = discoverQueue();
            var fp = curriculumFingerprint(queue);
            try { localStorage.removeItem(curriculumDoneKey(fp)); } catch (e) { /* ignore */ }
            resumeOrStart(true);
        });
        menu.appendChild(restart);

        var queue = discoverQueue();
        queue.forEach(function (id) {
            var mod = getModule(id);
            if (!mod) {
                return;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'mci-tour-menu-item';
            var defs = getExploreDefs(mod);
            var done = wasDone(moduleDoneKey(id, defs));
            btn.textContent = mod.label + (done ? '' : ' · pendiente');
            btn.addEventListener('click', function () {
                menu.remove();
                var state = {
                    fp: curriculumFingerprint(queue),
                    queue: queue,
                    idx: queue.indexOf(id),
                    phase: mod.route.test(urlSearch) ? 'explore' : 'enter'
                };
                writeActive(state);
                if (state.phase === 'explore') {
                    runExplore(state, mod);
                } else {
                    runEnterStep(state);
                }
            });
            menu.appendChild(btn);
        });

        document.body.appendChild(menu);
    }

    function ensureLauncher() {
        if (qs('#mci-tour-launcher')) {
            refreshLauncherState();
            return;
        }
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.id = 'mci-tour-launcher';
        btn.className = 'mci-tour-launcher';
        btn.setAttribute('aria-label', 'Guías interactivas');
        btn.innerHTML = '<i class="bi bi-compass" aria-hidden="true"></i><span>Guías</span>';
        btn.addEventListener('click', function () {
            showTourMenu();
        });
        document.body.appendChild(btn);
        refreshLauncherState();
    }

    // Tras Filtrar (submit), marcar resume en el siguiente paso explore
    document.addEventListener('submit', function (ev) {
        var state = readActive();
        if (!state || state.phase !== 'explore') {
            return;
        }
        var mod = getModule(state.queue[state.idx]);
        if (!mod || !mod.route.test(urlSearch)) {
            return;
        }
        if (activeDriver && typeof activeDriver.getActiveIndex === 'function') {
            state.exploreResume = {
                module: mod.id,
                step: activeDriver.getActiveIndex() + 1
            };
            writeActive(state);
        }
    }, true);

    function init() {
        ensureLauncher();
        var force = /[?&]tour=1(?:&|$)/.test(urlSearch);
        var queue = discoverQueue();
        var fp = curriculumFingerprint(queue);
        var pending = force || (queue.length > 0 && !wasDone(curriculumDoneKey(fp))) || !!readActive();
        if (!pending) {
            return;
        }
        window.setTimeout(function () {
            resumeOrStart(force);
        }, 600);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
