require('dotenv').config();
const mysql = require('mysql2/promise');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');

function parseBoolean(value, defaultValue = false) {
  if (value === undefined || value === null || value === '') {
    return defaultValue;
  }
  const normalized = String(value).trim().toLowerCase();
  return normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'si';
}

function getDbSslConfig() {
  const sslMode = String(process.env.DB_SSL_MODE || 'disabled').trim().toLowerCase();
  if (sslMode === 'required' || sslMode === 'true' || sslMode === '1') {
    return {
      rejectUnauthorized: parseBoolean(process.env.DB_SSL_REJECT_UNAUTHORIZED, false),
    };
  }
  return undefined;
}

const pool = mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'mcimadrid',
  connectTimeout: Number(process.env.DB_CONNECT_TIMEOUT_MS || 15000),
  ssl: getDbSslConfig(),
  waitForConnections: true,
  connectionLimit: 5,
  queueLimit: 0,
});

// Alinear NOW()/CURDATE() del servidor MySQL con Colombia (sin DST).
// Si tu BD ya tiene time_zone global correcto, puedes desactivar con WA_DB_SET_TIMEZONE=0
const dbTzSql = String(process.env.WA_DB_TIME_ZONE_SQL || "SET time_zone = '-05:00'").trim();
if (dbTzSql && parseBoolean(process.env.WA_DB_SET_TIMEZONE, true)) {
  pool.on('connection', (conn) => {
    conn.query(dbTzSql, () => {});
  });
}

const BATCH_LIMIT = Math.max(1, parseInt(process.env.WA_BATCH_LIMIT || '20', 10));
const delayMinRaw = parseInt(process.env.WA_DELAY_MIN_MS || process.env.WA_DELAY_MS || '60000', 10);
const delayMaxRaw = parseInt(process.env.WA_DELAY_MAX_MS || process.env.WA_DELAY_MS || '180000', 10);
const DELAY_MIN_MS = Math.max(500, Number.isFinite(delayMinRaw) ? delayMinRaw : 60000);
const DELAY_MAX_MS = Math.max(DELAY_MIN_MS, Number.isFinite(delayMaxRaw) ? delayMaxRaw : 180000);
const POLL_MS = Math.max(3000, parseInt(process.env.WA_POLL_MS || '5000', 10));
const MAX_ATTEMPTS = Math.max(1, parseInt(process.env.WA_MAX_ATTEMPTS || '3', 10));
const WAIT_ACK = parseBoolean(process.env.WA_WAIT_ACK, true);
/** Cumpleaños: la API suele dar ACK_ERROR aunque el envío manual sí llega; por defecto no bloquear por ACK. */
const WAIT_ACK_CUMPLEANOS = parseBoolean(process.env.WA_WAIT_ACK_CUMPLEANOS, false);
const WAIT_ACK_MS = Math.max(5000, parseInt(process.env.WA_WAIT_ACK_MS || '90000', 10));
const MIN_ACK_ENVIO = parseInt(process.env.WA_MIN_ACK || '1', 10); // 1 = ACK_SERVER
const ACK_ERROR_GRACE_MS = Math.max(3000, parseInt(process.env.WA_ACK_ERROR_GRACE_MS || '12000', 10));
const SKIP_MEDIA_CUMPLEANOS = parseBoolean(process.env.WA_SKIP_MEDIA_CUMPLEANOS, false);
const CUMPLE_TYPING_MS = Math.max(0, parseInt(process.env.WA_CUMPLE_TYPING_MS || '2000', 10));
const DEFAULT_TEMPLATE_CUMPLEANOS = 'Hoy celebramos tu vida y damos gracias a Dios por tu corazón tan dispuesto para servir.\n\nTu esfuerzo, tu amor por las personas y tu entrega han dejado huellas profundas en nuestra iglesia. Gracias por guiar, apoyar y nunca rendirte.\n\nOramos para que este nuevo año llegue lleno de bendición, fuerzas renovadas y mucha alegría.\n\n¡Feliz cumpleaños te desea MCI Madrid! 🎉 Te honramos y te agradecemos de corazón.';

let procesando = false;
let procesamientoIniciado = false;
let ultimaFechaCumpleanosProcesada = null;
let ultimoLogColaVacia = 0;

async function asegurarTabla() {
  await pool.query(
    `CREATE TABLE IF NOT EXISTS whatsapp_local_queue (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      telefono VARCHAR(20) NOT NULL,
      mensaje TEXT NOT NULL,
      media_url VARCHAR(500) NULL,
      media_tipo VARCHAR(20) NULL,
      tipo_evento VARCHAR(80) NOT NULL,
      referencia VARCHAR(150) NULL,
      estado ENUM('pendiente','procesando','enviado','fallido') NOT NULL DEFAULT 'pendiente',
      intentos INT NOT NULL DEFAULT 0,
      programado_en DATETIME NULL,
      ultimo_error TEXT NULL,
      creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      procesado_en DATETIME NULL,
      INDEX idx_estado (estado),
      INDEX idx_evento (tipo_evento),
      INDEX idx_creado (creado_en),
      UNIQUE KEY uq_evento_ref_tel (tipo_evento, referencia, telefono)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
  );

  const [colMediaUrl] = await pool.query("SHOW COLUMNS FROM whatsapp_local_queue LIKE 'media_url'");
  if (!Array.isArray(colMediaUrl) || colMediaUrl.length === 0) {
    await pool.query("ALTER TABLE whatsapp_local_queue ADD COLUMN media_url VARCHAR(500) NULL AFTER mensaje");
  }

  const [colMediaTipo] = await pool.query("SHOW COLUMNS FROM whatsapp_local_queue LIKE 'media_tipo'");
  if (!Array.isArray(colMediaTipo) || colMediaTipo.length === 0) {
    await pool.query("ALTER TABLE whatsapp_local_queue ADD COLUMN media_tipo VARCHAR(20) NULL AFTER media_url");
  }

  const [colProgramadoEn] = await pool.query("SHOW COLUMNS FROM whatsapp_local_queue LIKE 'programado_en'");
  if (!Array.isArray(colProgramadoEn) || colProgramadoEn.length === 0) {
    await pool.query("ALTER TABLE whatsapp_local_queue ADD COLUMN programado_en DATETIME NULL AFTER intentos");
  }
}

function normalizarTelefono(telefono) {
  const digits = String(telefono || '').replace(/\D+/g, '');
  if (!digits) return null;
  if (/^\d{10}$/.test(digits)) return `57${digits}`;
  if (/^\d{11,15}$/.test(digits)) return digits;
  return null;
}

/**
 * Resuelve el chatId real en WhatsApp (c.us o lid). Evita falsos "enviado" con @c.us inventado.
 */
async function resolverDestinoChat(client, telefonoNorm) {
  if (!telefonoNorm) return null;

  try {
    const numberId = await client.getNumberId(telefonoNorm);
    if (numberId && numberId._serialized) {
      return String(numberId._serialized);
    }
  } catch (err) {
    console.warn(`[WA] getNumberId(${telefonoNorm}):`, err && err.message ? err.message : err);
  }

  try {
    const legacyId = `${telefonoNorm}@c.us`;
    if (typeof client.isRegisteredUser === 'function') {
      const registrado = await client.isRegisteredUser(legacyId);
      if (registrado) {
        return legacyId;
      }
    }
  } catch (err) {
    console.warn(`[WA] isRegisteredUser(${telefonoNorm}):`, err && err.message ? err.message : err);
  }

  return null;
}

function mensajeTieneIdEnvio(sentMsg) {
  if (!sentMsg) return false;
  if (sentMsg.id && (sentMsg.id._serialized || sentMsg.id.id || sentMsg.id)) return true;
  if (sentMsg._data && sentMsg._data.id) return true;
  return false;
}

function idMensajeSerializado(sentMsg) {
  if (!sentMsg || !sentMsg.id) return '';
  return String(sentMsg.id._serialized || sentMsg.id.id || sentMsg.id || '');
}

/**
 * Espera confirmación real de WhatsApp (ACK_SERVER) antes de marcar enviado en BD.
 */
function esperarAckMensaje(client, sentMsg, timeoutMs = WAIT_ACK_MS) {
  const msgId = idMensajeSerializado(sentMsg);
  if (!msgId) {
    return Promise.reject(new Error('Mensaje sin id serializado'));
  }

  if (sentMsg.ack !== undefined && sentMsg.ack >= MIN_ACK_ENVIO) {
    return Promise.resolve(sentMsg.ack);
  }

  return new Promise((resolve, reject) => {
    let settled = false;
    let lastAck = typeof sentMsg.ack === 'number' ? sentMsg.ack : 0;
    let errorGraceTimer = null;

    const cleanup = () => {
      client.removeListener('message_ack', onAck);
      if (errorGraceTimer) {
        clearTimeout(errorGraceTimer);
        errorGraceTimer = null;
      }
    };

    const finishResolve = (ackVal) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      cleanup();
      resolve(ackVal);
    };

    const finishReject = (err) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      cleanup();
      reject(err);
    };

    const timer = setTimeout(() => {
      if (lastAck >= MIN_ACK_ENVIO) {
        finishResolve(lastAck);
        return;
      }
      finishReject(
        new Error(
          `Timeout ACK (${timeoutMs}ms). Último ack=${lastAck} — WhatsApp no confirmó el envío.`
        )
      );
    }, timeoutMs);

    function scheduleErrorGrace() {
      if (errorGraceTimer) return;
      errorGraceTimer = setTimeout(() => {
        errorGraceTimer = null;
        if (settled) return;
        if (lastAck >= MIN_ACK_ENVIO) {
          finishResolve(lastAck);
          return;
        }
        if (lastAck === -1) {
          finishReject(
            new Error(
              'WhatsApp rechazó el mensaje (ACK_ERROR). Suele ser la imagen/video adjunta o límite de la cuenta; el número puede existir igual.'
            )
          );
        }
      }, ACK_ERROR_GRACE_MS);
    }

    function onAck(msg, ack) {
      const ackVal = typeof ack === 'number' ? ack : (msg && msg.ack);
      const incomingId = msg && msg.id ? idMensajeSerializado(msg) : '';
      if (incomingId !== msgId) return;

      lastAck = ackVal;
      sentMsg.ack = ackVal;

      if (ackVal >= MIN_ACK_ENVIO) {
        finishResolve(ackVal);
        return;
      }

      if (ackVal === -1) {
        scheduleErrorGrace();
      }
    }

    client.on('message_ack', onAck);
    if (lastAck === -1) {
      scheduleErrorGrace();
    }
  });
}

async function activarChatHumano(client, chatId) {
  if (CUMPLE_TYPING_MS <= 0) return;
  try {
    const chat = await client.getChatById(chatId);
    if (chat && typeof chat.sendStateTyping === 'function') {
      await chat.sendStateTyping();
      await sleep(CUMPLE_TYPING_MS);
    }
  } catch (_) {
    /* ignorar */
  }
}

/**
 * Cumpleaños con imagen: escribiendo… y luego imagen+caption (como envío manual).
 */
async function enviarCumpleanosHumano(client, chatId, texto, mediaUrl, mediaTipo) {
  await activarChatHumano(client, chatId);
  const media = await MessageMedia.fromUrl(mediaUrl, { unsafeMime: true });
  const mimeHint = String(mediaTipo || '').trim() || inferirMimeDesdeUrl(mediaUrl);
  if (mimeHint && media && !media.mimetype) {
    media.mimetype = mimeHint;
  }
  return client.sendMessage(chatId, media, { caption: texto || undefined });
}

async function confirmarEnvioOpcional(client, sentMsg, esCumple) {
  if (!mensajeTieneIdEnvio(sentMsg)) {
    throw new Error('WhatsApp no devolvió id de mensaje');
  }
  const exigirAck = WAIT_ACK && (!esCumple || WAIT_ACK_CUMPLEANOS);
  if (!exigirAck) {
    if (esCumple) {
      console.log(
        `[WA] Cumpleaños enviado por API (id OK). No se exige ACK: a veces la API marca error y el mensaje sí llega, como en envío manual.`
      );
    }
    return typeof sentMsg.ack === 'number' ? sentMsg.ack : 0;
  }
  return esperarAckMensaje(client, sentMsg);
}

/**
 * Envía y confirma; cumpleaños con media usa flujo humano; ACK_ERROR en cumpleaños no bloquea por defecto.
 */
async function enviarYConfirmarCola(client, chatId, item) {
  const texto = String(item.mensaje || '').trim();
  let payload = { ...item };
  const esCumple = String(item.tipo_evento || '') === 'felicitacion_cumpleanos';

  if (SKIP_MEDIA_CUMPLEANOS && esCumple) {
    payload = { ...payload, media_url: null, media_tipo: null };
  }

  const mediaUrl = String(payload.media_url || '').trim();
  const tieneMedia = mediaUrl !== '';

  const enviarTextoSolo = async () => {
    if (!texto) {
      throw new Error('Sin texto para reintento');
    }
    console.warn(`[WA] Reintento solo texto -> ${chatId}`);
    await activarChatHumano(client, chatId);
    const sentMsg = await client.sendMessage(chatId, texto);
    await confirmarEnvioOpcional(client, sentMsg, esCumple);
    return sentMsg;
  };

  try {
    let sentMsg;
    if (esCumple && tieneMedia) {
      sentMsg = await enviarCumpleanosHumano(client, chatId, texto, mediaUrl, payload.media_tipo);
    } else {
      sentMsg = await enviarMensajeCola(client, chatId, payload);
    }
    try {
      await confirmarEnvioOpcional(client, sentMsg, esCumple);
    } catch (ackErr) {
      if (tieneMedia && texto && esCumple) {
        return enviarTextoSolo();
      }
      throw ackErr;
    }
    return sentMsg;
  } catch (err) {
    if (tieneMedia && texto) {
      return enviarTextoSolo();
    }
    throw err;
  }
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function getRandomDelayMs() {
  if (DELAY_MIN_MS === DELAY_MAX_MS) {
    return DELAY_MIN_MS;
  }
  return Math.floor(Math.random() * (DELAY_MAX_MS - DELAY_MIN_MS + 1)) + DELAY_MIN_MS;
}

function getFechaHoyBogotaYmd() {
  const partes = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'America/Bogota',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(new Date());

  const map = {};
  for (const p of partes) {
    if (p && p.type && p.value) {
      map[p.type] = p.value;
    }
  }

  return `${map.year}-${map.month}-${map.day}`;
}

function renderTemplate(template, vars = {}) {
  if (!template) return '';
  return String(template).replace(/\{([^}]+)\}/g, (_, key) => {
    const value = Object.prototype.hasOwnProperty.call(vars, key) ? vars[key] : '';
    return String(value == null ? '' : value);
  }).trim();
}

async function obtenerTemplateCumpleanos() {
  const [rows] = await pool.query(
    `SELECT plantilla, media_url, media_tipo
     FROM whatsapp_mensaje_template
     WHERE clave = 'felicitacion_cumpleanos'
     LIMIT 1`
  );

  if (!Array.isArray(rows) || !rows.length) {
    return {
      plantilla: DEFAULT_TEMPLATE_CUMPLEANOS,
      media_url: null,
      media_tipo: null,
    };
  }

  const row = rows[0] || {};
  return {
    plantilla: String(row.plantilla || DEFAULT_TEMPLATE_CUMPLEANOS),
    media_url: row.media_url ? String(row.media_url) : null,
    media_tipo: row.media_tipo ? String(row.media_tipo) : null,
  };
}

async function obtenerCumpleanerosDelDia(month, day) {
  const [rows] = await pool.query(
    `SELECT Id_Persona, Nombre, Apellido, Telefono
     FROM persona
     WHERE Fecha_Nacimiento IS NOT NULL
       AND Fecha_Nacimiento <> '0000-00-00'
       AND MONTH(Fecha_Nacimiento) = ?
       AND DAY(Fecha_Nacimiento) = ?
       AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)`,
    [month, day]
  );

  return Array.isArray(rows) ? rows : [];
}

async function encolarCumpleanosDelDiaSiAplica() {
  const fechaHoy = getFechaHoyBogotaYmd();
  if (ultimaFechaCumpleanosProcesada === fechaHoy) {
    return;
  }

  const [year, monthStr, dayStr] = fechaHoy.split('-');
  const month = Number(monthStr);
  const day = Number(dayStr);
  if (!month || !day) {
    return;
  }

  const template = await obtenerTemplateCumpleanos();
  const cumpleaneros = await obtenerCumpleanerosDelDia(month, day);
  let encolados = 0;
  let actualizados = 0;

  for (const persona of cumpleaneros) {
    const idPersona = Number(persona.Id_Persona || 0);
    const telefonoNormalizado = normalizarTelefono(persona.Telefono);
    if (!idPersona || !telefonoNormalizado) {
      continue;
    }

    const nombreCompleto = `${String(persona.Nombre || '').trim()} ${String(persona.Apellido || '').trim()}`.trim();
    const mensaje = renderTemplate(template.plantilla, {
      persona_nombre: nombreCompleto,
      fecha_hoy: `${dayStr}/${monthStr}/${year}`,
    });

    if (!mensaje) {
      continue;
    }

    const referencia = `cumpleanos:${fechaHoy}:persona:${idPersona}`;
    const [result] = await pool.query(
      `INSERT INTO whatsapp_local_queue
         (telefono, mensaje, media_url, media_tipo, tipo_evento, referencia, estado, intentos)
       VALUES (?, ?, ?, ?, 'felicitacion_cumpleanos', ?, 'pendiente', 0)
       ON DUPLICATE KEY UPDATE
         mensaje = VALUES(mensaje),
         media_url = VALUES(media_url),
         media_tipo = VALUES(media_tipo),
         estado = IF(estado = 'enviado', 'enviado', 'pendiente'),
         ultimo_error = IF(estado = 'enviado', ultimo_error, NULL)`,
      [telefonoNormalizado, mensaje, template.media_url, template.media_tipo, referencia]
    );

    if (result && result.affectedRows === 1) {
      encolados += 1;
    } else if (result && result.affectedRows === 2) {
      actualizados += 1;
    }
  }

  ultimaFechaCumpleanosProcesada = fechaHoy;
  const total = cumpleaneros.length;
  if (total > 0) {
    console.log(
      `[CUMPLEANOS] ${fechaHoy}: ${total} cumpleañero(s); ${encolados} nuevo(s) en cola, ${actualizados} ya existían (no se reenvían si están "enviado").`
    );
    if (encolados === 0 && actualizados > 0) {
      console.log(
        '[CUMPLEANOS] Si no llegaron al celular, ejecuta: php tools/whatsapp_local/reencolar_cumpleanos_hoy.php --execute'
      );
    }
  }
}

function buildFiltroFechaColaSql() {
  const onlyToday = parseBoolean(process.env.WA_ONLY_TODAY, true);
  if (onlyToday) {
    return `AND (
            (programado_en IS NULL AND DATE(creado_en) = CURDATE())
            OR (programado_en IS NOT NULL AND DATE(programado_en) = CURDATE())
          )`;
  }

  const maxAgeDays = Math.max(1, parseInt(process.env.WA_QUEUE_MAX_AGE_DAYS || '45', 10));
  return `AND creado_en >= DATE_SUB(NOW(), INTERVAL ${maxAgeDays} DAY)`;
}

async function obtenerPendientes(limit) {
  // WA_ONLY_TODAY=1 (defecto): solo mensajes de hoy (por creado_en o por programado_en).
  // Con programado_en: enviar cuando programado_en <= NOW() dentro del mismo día.
  const filtroFecha = buildFiltroFechaColaSql();
  const onlyToday = parseBoolean(process.env.WA_ONLY_TODAY, true);
  const params = onlyToday ? [MAX_ATTEMPTS, limit] : [MAX_ATTEMPTS, limit];

    const [rows] = await pool.query(
    `SELECT id, telefono, mensaje, media_url, media_tipo, tipo_evento, intentos
     FROM whatsapp_local_queue
     WHERE estado = 'pendiente' AND intentos < ?
       ${filtroFecha}
       AND (
            programado_en IS NULL
            OR programado_en <= NOW()
       )
     ORDER BY CASE
       WHEN tipo_evento IN ('mensaje_capacitacion_destino', 'programacion_mensaje_capacitacion_destino') THEN 0
       WHEN tipo_evento = 'felicitacion_cumpleanos' THEN 1
       ELSE 2
     END ASC, id ASC
     LIMIT ?`,
    params
  );
  return rows;
}

async function marcarProcesando(id) {
  await pool.query(
    `UPDATE whatsapp_local_queue
     SET estado = 'procesando', intentos = intentos + 1
     WHERE id = ? AND estado = 'pendiente'`,
    [id]
  );
}

async function marcarEnviado(id) {
  await pool.query(
    `UPDATE whatsapp_local_queue
     SET estado = 'enviado', ultimo_error = NULL, procesado_en = NOW()
     WHERE id = ?`,
    [id]
  );
}

async function marcarFallido(id, error) {
  await pool.query(
    `UPDATE whatsapp_local_queue
     SET estado = 'fallido', ultimo_error = ?, procesado_en = NOW()
     WHERE id = ?`,
    [String(error || 'error desconocido').substring(0, 1000), id]
  );
}

function inferirMimeDesdeUrl(url) {
  const u = String(url || '').toLowerCase();
  if (/\.jpe?g(\?|$)/.test(u)) return 'image/jpeg';
  if (/\.png(\?|$)/.test(u)) return 'image/png';
  if (/\.gif(\?|$)/.test(u)) return 'image/gif';
  if (/\.webp(\?|$)/.test(u)) return 'image/webp';
  if (/\.mp4(\?|$)/.test(u)) return 'video/mp4';
  if (/\.pdf(\?|$)/.test(u)) return 'application/pdf';
  return null;
}

async function enviarMensajeCola(client, chatId, item) {
  const texto = String(item.mensaje || '');
  const mediaUrl = String(item.media_url || '').trim();
  let sentMsg;

  if (mediaUrl) {
    try {
      const media = await MessageMedia.fromUrl(mediaUrl, { unsafeMime: true });
      const mimeHint = String(item.media_tipo || '').trim() || inferirMimeDesdeUrl(mediaUrl);
      if (mimeHint && media && !media.mimetype) {
        media.mimetype = mimeHint;
      }
      sentMsg = await client.sendMessage(chatId, media, { caption: texto || undefined });
    } catch (mediaErr) {
      console.warn(
        `[WA] Media no cargó (${mediaUrl}):`,
        mediaErr && mediaErr.message ? mediaErr.message : mediaErr,
        '— se envía solo texto.'
      );
      if (!texto) {
        throw mediaErr;
      }
      sentMsg = await client.sendMessage(chatId, texto);
    }
  } else {
    sentMsg = await client.sendMessage(chatId, texto);
  }

  return sentMsg;
}

async function marcarReintento(id, error) {
  await pool.query(
    `UPDATE whatsapp_local_queue
     SET estado = 'pendiente', ultimo_error = ?
     WHERE id = ?`,
    [String(error || 'error desconocido').substring(0, 1000), id]
  );
}

async function procesarCola(client) {
  if (procesando) return;
  procesando = true;

  try {
    await encolarCumpleanosDelDiaSiAplica();

    const pendientes = await obtenerPendientes(BATCH_LIMIT);
    if (!pendientes.length) {
      const ahora = Date.now();
      if (ahora - ultimoLogColaVacia > 120000) {
        console.log('[COLA] Sin mensajes pendientes listos para enviar (hoy / programado_en).');
        ultimoLogColaVacia = ahora;
      }
      return;
    }

    console.log(`[COLA] Enviando ${pendientes.length} mensaje(s) (pausa ${Math.round(DELAY_MIN_MS / 1000)}–${Math.round(DELAY_MAX_MS / 1000)}s entre cada uno)...`);

    for (const item of pendientes) {
      const id = Number(item.id || 0);
      if (!id) continue;
      const intentoActual = Number(item.intentos || 0) + 1;

      await marcarProcesando(id);

      const telefono = normalizarTelefono(item.telefono);
      if (!telefono) {
        await marcarFallido(id, 'Teléfono inválido');
        continue;
      }

      const chatId = await resolverDestinoChat(client, telefono);
      if (!chatId) {
        await marcarFallido(id, 'Número no registrado en WhatsApp o no se pudo resolver chat (getNumberId)');
        console.error(`[FAIL] ${id} -> ${telefono}: sin chatId válido en WhatsApp`);
        continue;
      }

      try {
        const sentMsg = await enviarYConfirmarCola(client, chatId, item);
        const msgRef = idMensajeSerializado(sentMsg);
        const ackNivel = typeof sentMsg.ack === 'number' ? sentMsg.ack : '?';
        await marcarEnviado(id);
        console.log(`[OK] ${id} -> ${chatId} (${telefono}) msg=${msgRef} ack=${ackNivel}`);
      } catch (err) {
        const errorMsg = err && err.message ? err.message : 'Error de envío';
        if (intentoActual < MAX_ATTEMPTS) {
          await marcarReintento(id, errorMsg);
          console.error(`[RETRY ${intentoActual}/${MAX_ATTEMPTS}] ${id} -> ${telefono}:`, errorMsg);
        } else {
          await marcarFallido(id, errorMsg);
          console.error(`[FAIL ${intentoActual}/${MAX_ATTEMPTS}] ${id} -> ${telefono}:`, errorMsg);
        }
      }

      const delayMs = getRandomDelayMs();
      console.log(`[WAIT] Pausa de ${Math.round(delayMs / 1000)}s antes del siguiente envío.`);
      await sleep(delayMs);
    }
  } catch (err) {
    console.error('Error procesando cola:', err && err.message ? err.message : err);
  } finally {
    procesando = false;
  }
}

async function iniciarProcesamiento(client) {
  await asegurarTabla();
  // Primera pasada inmediata para no esperar el primer intervalo.
  await procesarCola(client);

  setInterval(() => {
    procesarCola(client).catch(() => {});
  }, POLL_MS);
}

const client = new Client({
  authStrategy: new LocalAuth({ clientId: process.env.WA_CLIENT_ID || 'mcimadrid_server' }),
  puppeteer: {
    headless: String(process.env.WA_HEADLESS || 'true').toLowerCase() !== 'false',
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  },
});

client.on('qr', (qr) => {
  console.log('QR generado. Escanéalo con WhatsApp.');
  try {
    qrcode.generate(qr, { small: true });
  } catch (e) {
    console.log(qr);
  }
});

client.on('ready', () => {
  if (procesamientoIniciado) {
    console.log('[WA] Sesión reconectada (el procesamiento ya estaba activo).');
    return;
  }
  procesamientoIniciado = true;
  console.log('WhatsApp conectado. Worker activo.');
  console.log(`[CONFIG] Delay aleatorio entre ${Math.round(DELAY_MIN_MS / 1000)}s y ${Math.round(DELAY_MAX_MS / 1000)}s.`);
  console.log(`[CONFIG] Esperar ACK (otros mensajes): ${WAIT_ACK ? 'sí' : 'no'}. Cumpleaños: ${WAIT_ACK_CUMPLEANOS ? 'sí' : 'no (recomendado)'}.`);
  iniciarProcesamiento(client)
    .catch((err) => {
      console.error('No se pudo asegurar tabla whatsapp_local_queue:', err && err.message ? err.message : err);
    });
});

client.on('auth_failure', (msg) => {
  console.error('Fallo de autenticación de WhatsApp:', msg);
});

client.on('disconnected', (reason) => {
  console.error('WhatsApp desconectado:', reason);
});

process.on('SIGINT', async () => {
  console.log('Cerrando worker...');
  try {
    await client.destroy();
    await pool.end();
  } catch (e) {
    // Ignorar errores de cierre.
  }
  process.exit(0);
});

client.initialize();
