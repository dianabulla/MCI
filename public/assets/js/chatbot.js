(function () {
    'use strict';

    var cfg = window.MCI_CHATBOT || {};
    var root = document.getElementById('mci-chatbot-root');
    if (!root || !cfg.consultarUrl) {
        return;
    }

    var panel = document.getElementById('mci-chatbot-panel');
    var toggle = document.getElementById('mci-chatbot-toggle');
    var closeBtn = document.getElementById('mci-chatbot-close');
    var messages = document.getElementById('mci-chatbot-messages');
    var suggestionsEl = document.getElementById('mci-chatbot-suggestions');
    var form = document.getElementById('mci-chatbot-form');
    var input = document.getElementById('mci-chatbot-input');
    var sendBtn = document.getElementById('mci-chatbot-send');

    var initialized = false;
    var busy = false;

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function scrollBottom() {
        if (messages) {
            messages.scrollTop = messages.scrollHeight;
        }
    }

    function appendMessage(text, role) {
        if (!messages) {
            return null;
        }
        var div = document.createElement('div');
        div.className = 'mci-chatbot-msg mci-chatbot-msg--' + role;
        div.textContent = text;
        messages.appendChild(div);
        scrollBottom();
        return div;
    }

    function renderCards(cards) {
        if (!Array.isArray(cards) || cards.length === 0) {
            return '';
        }

        var stats = cards.filter(function (c) { return c && c.type === 'stat'; });
        var personas = cards.filter(function (c) { return c && c.type === 'persona'; });
        var html = '';

        if (stats.length > 0) {
            html += '<div class="mci-chatbot-stat-grid">';
            stats.forEach(function (card) {
                html += '<div class="mci-chatbot-stat"><strong>' + escapeHtml(card.subtitle || '0') + '</strong><span>' + escapeHtml(card.title || '') + '</span></div>';
            });
            html += '</div>';
        }

        if (personas.length > 0) {
            html += '<div class="mci-chatbot-cards">';
            personas.forEach(function (card) {
                var url = card.url || '#';
                var meta = Array.isArray(card.meta) ? card.meta.join(' · ') : '';
                html += '<a class="mci-chatbot-card" href="' + escapeHtml(url) + '">'
                    + '<span class="mci-chatbot-card-title">' + escapeHtml(card.title || '') + '</span>'
                    + (card.subtitle ? '<span class="mci-chatbot-card-sub">' + escapeHtml(card.subtitle) + '</span>' : '')
                    + (meta ? '<span class="mci-chatbot-card-meta">' + escapeHtml(meta) + '</span>' : '')
                    + '</a>';
            });
            html += '</div>';
        }

        return html;
    }

    function renderLinks(links) {
        if (!Array.isArray(links) || links.length === 0) {
            return '';
        }
        var html = '<div class="mci-chatbot-links">';
        links.forEach(function (link) {
            if (!link || !link.url) {
                return;
            }
            html += '<a class="mci-chatbot-link" href="' + escapeHtml(link.url) + '">' + escapeHtml(link.label || 'Abrir') + '</a>';
        });
        html += '</div>';
        return html;
    }

    function renderSuggestions(list) {
        if (!suggestionsEl) {
            return;
        }
        suggestionsEl.innerHTML = '';
        if (!Array.isArray(list) || list.length === 0) {
            return;
        }
        list.forEach(function (text) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'mci-chatbot-chip';
            btn.textContent = text;
            btn.addEventListener('click', function () {
                if (input) {
                    input.value = text;
                }
                enviarMensaje(text);
            });
            suggestionsEl.appendChild(btn);
        });
    }

    function appendBotResponse(data) {
        var wrapper = document.createElement('div');
        wrapper.className = 'mci-chatbot-msg mci-chatbot-msg--bot';
        wrapper.textContent = data.reply || 'Listo.';
        messages.appendChild(wrapper);

        var extraHtml = renderCards(data.cards) + renderLinks(data.links);
        if (extraHtml) {
            var extra = document.createElement('div');
            extra.className = 'mci-chatbot-msg mci-chatbot-msg--bot';
            extra.style.background = 'transparent';
            extra.style.border = 'none';
            extra.style.padding = '0';
            extra.innerHTML = extraHtml;
            messages.appendChild(extra);
        }

        renderSuggestions(data.suggestions || []);
        scrollBottom();
    }

    function setBusy(state) {
        busy = state;
        if (sendBtn) {
            sendBtn.disabled = state;
        }
        if (input) {
            input.disabled = state;
        }
    }

    function enviarMensaje(texto) {
        var message = String(texto || '').trim();
        if (message === '' || busy) {
            return;
        }

        appendMessage(message, 'user');
        if (input) {
            input.value = '';
        }

        var loading = appendMessage('Pensando…', 'loading');
        setBusy(true);

        fetch(cfg.consultarUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ message: message })
        })
            .then(function (resp) {
                return resp.json().then(function (data) {
                    return { ok: resp.ok, data: data };
                });
            })
            .then(function (result) {
                if (loading && loading.parentNode) {
                    loading.parentNode.removeChild(loading);
                }
                var data = result.data || {};
                if (!result.ok && !data.reply) {
                    appendMessage(data.error || 'No pude procesar la solicitud.', 'bot');
                    return;
                }
                appendBotResponse(data);
            })
            .catch(function () {
                if (loading && loading.parentNode) {
                    loading.parentNode.removeChild(loading);
                }
                appendMessage('Error de conexión. Intenta de nuevo.', 'bot');
            })
            .finally(function () {
                setBusy(false);
            });
    }

    function cargarBienvenida() {
        if (!cfg.sugerenciasUrl) {
            appendMessage('Hola. Escribe «ayuda» para ver qué puedo hacer.', 'bot');
            return;
        }
        fetch(cfg.sugerenciasUrl, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                appendMessage(data.welcome || 'Hola. ¿En qué te ayudo?', 'bot');
                renderSuggestions(data.suggestions || []);
            })
            .catch(function () {
                appendMessage('Hola. Escribe «ayuda» para ver ejemplos.', 'bot');
            });
    }

    function abrirPanel() {
        if (!panel || !toggle) {
            return;
        }
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        if (!initialized) {
            initialized = true;
            cargarBienvenida();
        }
        if (input) {
            setTimeout(function () { input.focus(); }, 100);
        }
    }

    function cerrarPanel() {
        if (!panel || !toggle) {
            return;
        }
        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            if (panel && panel.classList.contains('is-open')) {
                cerrarPanel();
            } else {
                abrirPanel();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', cerrarPanel);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            enviarMensaje(input ? input.value : '');
        });
    }
})();
