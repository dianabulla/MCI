/**
 * Discipular: asignar, cambiar o quitar a una persona de una casilla (1 a 12).
 * El panel se abre dentro de la propia tarjeta, sin ventana flotante.
 */
(function () {
    'use strict';

    var raiz = document.querySelector('.asig-equipo');
    if (!raiz || raiz.getAttribute('data-puede-gestionar') !== '1') {
        return;
    }

    var cfg = {
        idLider: raiz.getAttribute('data-id-lider') || '',
        idMinisterio: raiz.getAttribute('data-id-ministerio') || '0',
        modo: raiz.getAttribute('data-modo-cupo') || 'pastoral',
        jerLider: raiz.getAttribute('data-jerarquia-lider') || '',
        generoRed: raiz.getAttribute('data-genero-red') || '',
        tab: raiz.getAttribute('data-tab') || '',
        urlPersonas: raiz.getAttribute('data-url-personas') || '',
        urlAsignar: raiz.getAttribute('data-url-asignar') || '',
        urlLiberar: raiz.getAttribute('data-url-liberar') || ''
    };

    var personas = null;
    var promesaPersonas = null;
    var tarjetaAbierta = null;

    function normalizar(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function el(tag, clase, texto) {
        var nodo = document.createElement(tag);
        if (clase) {
            nodo.className = clase;
        }
        if (texto !== undefined && texto !== null) {
            nodo.textContent = texto;
        }
        return nodo;
    }

    function jerarquiaPermite(jerLider, jerPersona) {
        if (!jerPersona || jerPersona === 'administrativo' || jerPersona === 'pastor') {
            return false;
        }
        if (!jerLider || jerLider === 'miembro' || jerLider === 'administrativo') {
            return true;
        }
        if (jerLider === 'lider_celula') {
            return jerPersona === 'miembro';
        }
        if (jerLider === 'lider_144') {
            return jerPersona === 'lider_celula' || jerPersona === 'miembro';
        }
        if (jerLider === 'lider_12') {
            return jerPersona === 'lider_144' || jerPersona === 'lider_celula' || jerPersona === 'miembro';
        }
        return true;
    }

    function cargarPersonas() {
        if (personas) {
            return Promise.resolve(personas);
        }
        if (promesaPersonas) {
            return promesaPersonas;
        }
        if (!cfg.urlPersonas) {
            personas = [];
            return Promise.resolve(personas);
        }

        var url = cfg.urlPersonas + (cfg.urlPersonas.indexOf('?') >= 0 ? '&' : '?')
            + 'id_ministerio=' + encodeURIComponent(cfg.idMinisterio);
        if (cfg.idLider && cfg.idLider !== '0') {
            url += '&id_lider=' + encodeURIComponent(cfg.idLider);
        }

        promesaPersonas = fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (resp) {
                if (!resp.ok) {
                    throw new Error('No se pudo cargar la lista de personas.');
                }
                return resp.json();
            })
            .then(function (items) {
                personas = Array.isArray(items) ? items : [];
                return personas;
            })
            .catch(function () {
                promesaPersonas = null;
                throw new Error('No se pudo cargar la lista de personas. Recarga la página.');
            });

        return promesaPersonas;
    }

    function idsYaEnEquipo() {
        var ids = {};
        Array.prototype.forEach.call(raiz.querySelectorAll('.jer-slot-card[data-id-persona]'), function (li) {
            var id = li.getAttribute('data-id-persona');
            if (id && id !== '0') {
                ids[id] = true;
            }
        });
        return ids;
    }

    function enviarPost(url, campos) {
        var form = document.createElement('form');
        form.method = 'post';
        form.action = url;
        Object.keys(campos).forEach(function (nombre) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = nombre;
            input.value = String(campos[nombre]);
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }

    function camposBase(numero, idActual) {
        return {
            id_lider: cfg.idLider,
            id_ministerio: cfg.idMinisterio,
            modo_cupo: cfg.modo,
            tab_retorno: cfg.tab,
            cobertura_principal_retorno: cfg.idLider,
            genero_red_retorno: cfg.generoRed,
            numero_cupo: numero,
            id_persona_actual_slot: idActual
        };
    }

    function cerrarPanel() {
        if (!tarjetaAbierta) {
            return;
        }
        var contenedor = tarjetaAbierta.querySelector('.asig-panel-slot');
        if (contenedor) {
            contenedor.innerHTML = '';
        }
        tarjetaAbierta.classList.remove('is-editando');
        tarjetaAbierta = null;
    }

    function abrirPanel(tarjeta) {
        if (tarjetaAbierta === tarjeta) {
            cerrarPanel();
            return;
        }
        cerrarPanel();
        tarjetaAbierta = tarjeta;
        tarjeta.classList.add('is-editando');

        var numero = tarjeta.getAttribute('data-slot') || '';
        var idActual = tarjeta.getAttribute('data-id-persona') || '0';
        var nombreActual = tarjeta.getAttribute('data-nombre') || '';
        var ocupada = idActual !== '0';
        var contenedor = tarjeta.querySelector('.asig-panel-slot');
        if (!contenedor) {
            return;
        }
        contenedor.innerHTML = '';

        var panel = el('div', 'asig-panel');
        panel.appendChild(el('p', 'asig-panel-titulo', ocupada
            ? 'Casilla ' + numero + ': elige a quien la ocupará'
            : 'Casilla ' + numero + ': elige a quien asignar'));

        var buscar = el('input', 'asig-buscar');
        buscar.type = 'search';
        buscar.placeholder = 'Buscar por nombre, cédula o teléfono';
        buscar.setAttribute('autocomplete', 'off');
        panel.appendChild(buscar);

        var lista = el('ul', 'asig-resultados');
        panel.appendChild(lista);

        var confirmar = el('div', 'asig-confirmar');
        confirmar.hidden = true;
        panel.appendChild(confirmar);

        var pie = el('div', 'asig-pie');
        if (ocupada) {
            var quitar = el('button', 'btn btn-sm btn-outline-danger', 'Dejar la casilla libre');
            quitar.type = 'button';
            quitar.addEventListener('click', function () {
                mostrarConfirmacion({
                    texto: 'Quitar a ' + nombreActual + ' de la casilla ' + numero + '. Quedará sin líder asignado.',
                    boton: 'Sí, dejar libre',
                    clase: 'btn btn-sm btn-danger',
                    accion: function () {
                        var campos = camposBase(numero, idActual);
                        campos.id_persona = idActual;
                        enviarPost(cfg.urlLiberar, campos);
                    }
                });
            });
            pie.appendChild(quitar);
        }
        var cerrar = el('button', 'btn btn-sm btn-light', 'Cerrar');
        cerrar.type = 'button';
        cerrar.addEventListener('click', cerrarPanel);
        pie.appendChild(cerrar);
        panel.appendChild(pie);

        contenedor.appendChild(panel);

        function mostrarConfirmacion(opciones) {
            confirmar.innerHTML = '';
            confirmar.hidden = false;
            confirmar.appendChild(el('p', 'asig-confirmar-texto', opciones.texto));
            var fila = el('div', 'asig-confirmar-botones');
            var ok = el('button', opciones.clase, opciones.boton);
            ok.type = 'button';
            ok.addEventListener('click', function () {
                ok.disabled = true;
                opciones.accion();
            });
            var no = el('button', 'btn btn-sm btn-light', 'Cancelar');
            no.type = 'button';
            no.addEventListener('click', function () {
                confirmar.hidden = true;
                confirmar.innerHTML = '';
            });
            fila.appendChild(ok);
            fila.appendChild(no);
            confirmar.appendChild(fila);
        }

        function elegir(persona) {
            var nombre = persona.nombre || persona.etiqueta || ('Persona ' + persona.id);
            var texto = 'Poner a ' + nombre + ' en la casilla ' + numero + '.';
            if (ocupada) {
                texto += ' ' + nombreActual + ' quedará sin líder asignado.';
            }
            mostrarConfirmacion({
                texto: texto,
                boton: 'Confirmar',
                clase: 'btn btn-sm btn-primary',
                accion: function () {
                    var campos = camposBase(numero, idActual);
                    campos.id_persona = persona.id;
                    enviarPost(cfg.urlAsignar, campos);
                }
            });
        }

        function pintar() {
            var texto = normalizar(buscar.value);
            var enEquipo = idsYaEnEquipo();
            lista.innerHTML = '';

            if (!personas) {
                lista.appendChild(el('li', 'asig-vacio', 'Cargando personas…'));
                return;
            }

            var coincidencias = personas.filter(function (p) {
                var id = String(p.id || '');
                if (!id || enEquipo[id]) {
                    return false;
                }
                if (!jerarquiaPermite(cfg.jerLider, String(p.jerarquia || 'miembro'))) {
                    return false;
                }
                return texto === '' || normalizar(p.search || (p.nombre + ' ' + p.documento + ' ' + p.telefono)).indexOf(texto) !== -1;
            });

            if (!coincidencias.length) {
                lista.appendChild(el('li', 'asig-vacio', 'No hay personas con ese dato.'));
                return;
            }

            coincidencias.slice(0, 8).forEach(function (p) {
                var li = el('li', 'asig-resultado');
                var datos = el('div', 'asig-resultado-datos');
                datos.appendChild(el('strong', '', p.nombre || p.etiqueta || ('Persona ' + p.id)));
                var detalle = [];
                if (p.nombre_rol) { detalle.push(p.nombre_rol); }
                if (p.documento) { detalle.push('CC ' + p.documento); }
                if (p.telefono) { detalle.push('Tel ' + p.telefono); }
                if (detalle.length) {
                    datos.appendChild(el('small', '', detalle.join(' · ')));
                }
                var idLiderActual = String(p.id_lider_actual || '0');
                if (idLiderActual !== '0' && idLiderActual !== String(cfg.idLider) && p.nombre_lider_actual) {
                    datos.appendChild(el('small', 'asig-resultado-aviso', 'Hoy está con ' + p.nombre_lider_actual));
                }
                li.appendChild(datos);
                var boton = el('button', 'btn btn-sm btn-outline-primary', 'Elegir');
                boton.type = 'button';
                boton.addEventListener('click', function () { elegir(p); });
                li.appendChild(boton);
                lista.appendChild(li);
            });

            if (coincidencias.length > 8) {
                lista.appendChild(el('li', 'asig-vacio', 'Hay ' + (coincidencias.length - 8) + ' más. Escribe para acotar.'));
            }
        }

        buscar.addEventListener('input', pintar);
        pintar();
        buscar.focus();

        cargarPersonas().then(pintar).catch(function (error) {
            lista.innerHTML = '';
            lista.appendChild(el('li', 'asig-vacio', error.message));
        });
    }

    raiz.addEventListener('click', function (e) {
        var boton = e.target && e.target.closest ? e.target.closest('.js-casilla-abrir') : null;
        if (!boton) {
            return;
        }
        e.preventDefault();
        var tarjeta = boton.closest('.jer-slot-card');
        if (tarjeta) {
            abrirPanel(tarjeta);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            cerrarPanel();
        }
    });
})();
