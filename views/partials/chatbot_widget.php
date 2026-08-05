<?php
/**
 * Widget flotante del asistente MCI (solo usuarios autenticados con permiso).
 */
if (!class_exists('AuthController') || !AuthController::estaAutenticado()) {
    return;
}

$puedeUsarChatbot = AuthController::puedeUsarChatbotAsistente();

if (!$puedeUsarChatbot) {
    return;
}

$chatbotConsultarUrl = public_app_url('chatbot/consultar');
$chatbotSugerenciasUrl = public_app_url('chatbot/sugerencias');
$chatbotCssUrl = function_exists('asset_url') ? asset_url('css/chatbot.css') : (ASSETS_URL . '/css/chatbot.css?v=20260803');
$chatbotJsUrl = function_exists('asset_url') ? asset_url('js/chatbot.js') : (ASSETS_URL . '/js/chatbot.js?v=20260803');
?>
<link rel="stylesheet" href="<?= htmlspecialchars($chatbotCssUrl, ENT_QUOTES, 'UTF-8') ?>">

<div id="mci-chatbot-root" class="mci-chatbot" aria-live="polite">
    <div id="mci-chatbot-panel" class="mci-chatbot-panel" aria-hidden="true" role="dialog" aria-labelledby="mci-chatbot-title">
        <header class="mci-chatbot-head">
            <div>
                <h3 id="mci-chatbot-title">Asistente MCI</h3>
                <p class="mci-chatbot-sub">Buscar personas · reportes rápidos</p>
            </div>
            <button type="button" id="mci-chatbot-close" class="mci-chatbot-close" aria-label="Cerrar asistente">&times;</button>
        </header>
        <div id="mci-chatbot-messages" class="mci-chatbot-messages"></div>
        <div id="mci-chatbot-suggestions" class="mci-chatbot-suggestions"></div>
        <form id="mci-chatbot-form" class="mci-chatbot-form" autocomplete="off">
            <input
                type="text"
                id="mci-chatbot-input"
                class="mci-chatbot-input"
                placeholder="Ej: buscar María, ganados este mes…"
                maxlength="500"
                aria-label="Mensaje para el asistente"
            >
            <button type="submit" id="mci-chatbot-send" class="mci-chatbot-send" aria-label="Enviar">
                <i class="bi bi-send-fill" aria-hidden="true"></i>
            </button>
        </form>
    </div>
    <button type="button" id="mci-chatbot-toggle" class="mci-chatbot-toggle" aria-expanded="false" aria-controls="mci-chatbot-panel" title="Asistente MCI">
        <i class="bi bi-chat-dots-fill" aria-hidden="true"></i>
        <span class="mci-chatbot-toggle-label">Asistente</span>
    </button>
</div>

<script>
window.MCI_CHATBOT = {
    consultarUrl: <?= json_encode($chatbotConsultarUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    sugerenciasUrl: <?= json_encode($chatbotSugerenciasUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="<?= htmlspecialchars($chatbotJsUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
