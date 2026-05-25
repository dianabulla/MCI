(function () {
    'use strict';

    var role = (document.body && document.body.getAttribute('data-product-tour-role')) || '';
    if (!role || typeof window.driver === 'undefined' || !window.driver.js || !window.driver.js.driver) {
        return;
    }

    var STORAGE_PREFIX = 'mci_product_tour_';
    var TOUR_VERSION = role === 'maestro' ? 'v2' : 'v1';
    var storageKey = STORAGE_PREFIX + role + '_' + TOUR_VERSION + '_done';

    function qs(selector) {
        return document.querySelector(selector);
    }

    function exists(selector) {
        return !!qs(selector);
    }

    function step(selector, title, description, side) {
        if (!exists(selector)) {
            return null;
        }
        return {
            element: selector,
            popover: {
                title: title,
                description: description,
                side: side || 'bottom',
                popoverClass: 'mci-driver-popover'
            }
        };
    }

    function stepDef(def) {
        return step(def.element, def.title, def.description, def.side);
    }

    function buildMaestroSteps() {
        var steps = [];
        var add = function (sel, title, desc, side) {
            var s = step(sel, title, desc, side);
            if (s) {
                steps.push(s);
            }
        };

        var defs = [
            {
                element: '[data-tour="maestro-bienvenida"]',
                title: 'Guía del profesor',
                description: 'Como maestro gestionas evaluaciones, tareas e inscritos por nivel y módulo. Este recorrido te muestra cómo crear, publicar y calificar.',
                side: 'bottom'
            },
            {
                element: '[data-tour="sidebar-cap-destino"]',
                title: '1. Entrada a Cap. Destino',
                description: 'Siempre empieza aquí. Abre Capacitación Destino para elegir nivel y módulo.',
                side: 'right'
            },
            {
                element: '#cap-level-selector',
                title: '2. Elige el nivel',
                description: 'Selecciona el nivel que impartes hoy (Nivel 1, 2 o 3) y pulsa Entrar.',
                side: 'bottom'
            },
            {
                element: '#cap-module-selector-grid',
                title: '3. Elige el módulo',
                description: 'Entra al módulo con el que trabajarás. Las tarjetas azules tienen evaluaciones o tareas activas hoy.',
                side: 'top'
            },
            {
                element: '[data-tour="hub-modulo-menu"]',
                title: '4. Panel del módulo',
                description: 'Desde este panel abres cada sección: evaluaciones, tareas, inscritos o material de apoyo.',
                side: 'bottom'
            },
            {
                element: '[data-tour="hub-evaluaciones"]',
                title: '5. Evaluaciones (crear y revisar notas)',
                description: 'Pulsa aquí para crear evaluaciones de opción múltiple, definir fechas de apertura y consultar el historial de presentaciones y notas de tus alumnos.',
                side: 'top'
            },
            {
                element: '[data-tour="hub-tareas"]',
                title: '6. Tareas (publicar y calificar)',
                description: 'Pulsa aquí para crear tareas del módulo, recibir entregas de los discípulos y asignar nota con retroalimentación.',
                side: 'top'
            },
            {
                element: '[data-tour="maestro-nav-eval"]',
                title: 'Navegación en evaluaciones',
                description: 'Usa Volver al módulo, Módulos o Inicio. El botón Historial presentadas muestra quién respondió y con qué puntaje.',
                side: 'bottom'
            },
            {
                element: '[data-tour="maestro-historial-eval"]',
                title: 'Historial de presentaciones',
                description: 'Revisa intentos, puntajes y aprobados de cada alumno. Desde ahí también puedes ver el detalle por evaluación.',
                side: 'bottom'
            },
            {
                element: '[data-tour="maestro-crear-eval-form"]',
                title: 'Crear una evaluación',
                description: 'Completa título, lección y puntaje mínimo (aprobación 80%). El nivel y módulo ya vienen del contexto en el que entraste.',
                side: 'top'
            },
            {
                element: '[data-tour="maestro-eval-fechas"]',
                title: 'Fechas de la evaluación',
                description: 'Define fecha de inicio y fin para que la evaluación solo sea visible y presentable en ese rango. Sin fechas, no aparece en el listado vigente.',
                side: 'top'
            },
            {
                element: '[data-tour="maestro-eval-preguntas"]',
                title: 'Preguntas cerradas',
                description: 'Pulsa Agregar pregunta, escribe el enunciado y marca la respuesta correcta. Puedes agregar varias preguntas antes de guardar.',
                side: 'top'
            },
            {
                element: '[data-tour="maestro-eval-guardar"]',
                title: 'Guardar evaluación',
                description: 'Al guardar, la evaluación queda registrada. Si definiste fechas vigentes para hoy, los discípulos la verán en su módulo.',
                side: 'top'
            },
            {
                element: '[data-tour="maestro-lista-eval-vigentes"]',
                title: 'Evaluaciones vigentes del módulo',
                description: 'Aquí ves las evaluaciones activas hoy. Puedes Editar, ver Notas, ajustar fechas con Guardar fechas o Activar si estaba inactiva.',
                side: 'top'
            },
            {
                element: '[data-tour="maestro-seccion-tareas"]',
                title: 'Sección de tareas del módulo',
                description: 'En esta pantalla publicas tareas y revisas las entregas de los discípulos del módulo seleccionado.',
                side: 'top'
            },
            {
                element: '[data-tour="maestro-crear-tarea"]',
                title: 'Crear una tarea',
                description: 'Escribe título, instrucciones (descripción) y fecha límite. Pulsa Crear tarea: los discípulos la verán en su módulo para subir archivos.',
                side: 'top'
            },
            {
                element: '[data-tour="maestro-calificar-tarea"]',
                title: 'Calificar entregas',
                description: 'Abre Calificar entregas, revisa el archivo del alumno, asigna nota (0 a 5), escribe retroalimentación y pulsa Guardar calificación.',
                side: 'top'
            },
            {
                element: '[data-tour="hub-inscritos"]',
                title: 'Inscritos y asistencia',
                description: 'Opcional: consulta la planilla del nivel y marca asistencia a las clases.',
                side: 'top'
            },
            {
                element: '[data-tour="page-header-cap"]',
                title: 'Volver sin perder contexto',
                description: 'Los botones superiores te llevan a Inicio, Niveles o al módulo sin salir de Capacitación Destino.',
                side: 'bottom'
            }
        ];

        defs.forEach(function (def) {
            var s = stepDef(def);
            if (s) {
                steps.push(s);
            }
        });

        return steps;
    }

    function buildDiscipuloSteps() {
        var steps = [];
        var add = function (sel, title, desc, side) {
            var s = step(sel, title, desc, side);
            if (s) {
                steps.push(s);
            }
        };

        add('[data-tour="sidebar-cap-destino"]', 'Tu espacio Cap. Destino', 'Este es tu punto de entrada: niveles, módulos, evaluaciones y tareas.', 'right');
        add('#cap-level-selector', 'Tu nivel', 'Elige el nivel en el que estás inscrito. Solo verás los módulos permitidos para ti.', 'bottom');
        add('.cap-level-card-enter-btn', 'Entrar', 'Pulsa Entrar para continuar al listado de módulos.', 'left');
        add('#cap-module-selector-grid', 'Tu módulo de hoy', 'Entra al módulo con actividad (azul) cuando haya evaluaciones o tareas activas.', 'top');
        add('.cap-clase-modulo-pick', 'Ir a clase', 'Conéctate a la clase en vivo cuando tu líder haya configurado el enlace.', 'bottom');
        add('[data-tour="hub-evaluaciones"]', 'Evaluaciones', 'Presenta tus evaluaciones vigentes. El temporizador de 20 minutos empieza al pulsar Responder.', 'top');
        add('[data-tour="hub-tareas"]', 'Tareas', 'Sube tus archivos (varios a la vez) y revisa abajo tus entregas y calificaciones.', 'top');
        add('.disc-nav-actions', 'Navegación del módulo', 'Volver al módulo, cambiar de módulo, ir a Tareas o volver al inicio.', 'bottom');
        add('[data-tour="lista-evaluaciones-discipulo"]', 'Evaluaciones disponibles', 'Aquí aparecen las evaluaciones activas de hoy. Usa Responder cuando estés listo.', 'top');
        add('.cap-tarea-upload-form', 'Entregar tarea', 'Escribe un comentario opcional y selecciona uno o varios archivos (imagen, audio, video, PDF, etc.).', 'top');
        add('.cap-entregas-usuario-wrap', 'Tus entregas', 'Después de subir, aquí ves cada archivo, la fecha y la calificación de tu líder.', 'top');

        return steps;
    }

    function getSteps() {
        if (role === 'maestro') {
            return buildMaestroSteps();
        }
        if (role === 'discipulo') {
            return buildDiscipuloSteps();
        }
        return [];
    }

    function markDone() {
        try {
            localStorage.setItem(storageKey, '1');
        } catch (e) {
            /* ignore */
        }
    }

    function wasDone() {
        try {
            return localStorage.getItem(storageKey) === '1';
        } catch (e) {
            return false;
        }
    }

    function shouldAutoStart() {
        var url = String(window.location.search || '');
        if (url.indexOf('tour=1') !== -1) {
            return true;
        }
        if (wasDone()) {
            return false;
        }
        return /url=home\/material\/capacitacion-destino|url=programas\/evaluaciones|url=programas\/tareas/.test(url);
    }

    function runTour() {
        var steps = getSteps();
        if (!steps.length) {
            return;
        }

        var driverApi = window.driver.js.driver;
        var driverObj = driverApi({
            showProgress: true,
            progressText: '{{current}} de {{total}}',
            nextBtnText: 'Siguiente',
            prevBtnText: 'Anterior',
            doneBtnText: 'Listo',
            allowClose: true,
            overlayOpacity: 0.55,
            stagePadding: 8,
            popoverClass: 'mci-driver-popover',
            steps: steps,
            onDestroyed: function () {
                markDone();
            }
        });

        driverObj.drive();
    }

    function ensureLauncher() {
        if (qs('#mci-tour-launcher')) {
            return;
        }
        var label = role === 'maestro' ? 'Guía profesor' : 'Guía';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.id = 'mci-tour-launcher';
        btn.className = 'mci-tour-launcher';
        btn.setAttribute('aria-label', 'Ver guía interactiva');
        btn.innerHTML = '<i class="bi bi-mortarboard" aria-hidden="true"></i><span>' + label + '</span>';
        btn.addEventListener('click', function () {
            runTour();
        });
        document.body.appendChild(btn);
    }

    function init() {
        ensureLauncher();
        if (shouldAutoStart()) {
            window.setTimeout(function () {
                runTour();
            }, 700);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
