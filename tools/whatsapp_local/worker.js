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

const BATCH_LIMIT = Math.max(1, parseInt(process.env.WA_BATCH_LIMIT || '20', 10));
const DELAY_MS = Math.max(500, parseInt(process.env.WA_DELAY_MS || '3500', 10));
const POLL_MS = Math.max(3000, parseInt(process.env.WA_POLL_MS || '5000', 10));
const MAX_ATTEMPTS = Math.max(1, parseInt(process.env.WA_MAX_ATTEMPTS || '3', 10));

let procesando = false;

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
}

function normalizarTelefono(telefono) {
  const digits = String(telefono || '').replace(/\D+/g, '');
  if (!digits) return null;
  if (/^\d{10}$/.test(digits)) return `57${digits}`;
  if (/^\d{11,15}$/.test(digits)) return digits;
  return null;
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function obtenerPendientes(limit) {
  const [rows] = await pool.query(
    `SELECT id, telefono, mensaje, media_url, media_tipo, intentos
     FROM whatsapp_local_queue
     WHERE estado = 'pendiente' AND intentos < ?
     ORDER BY id ASC
     LIMIT ?`,
    [MAX_ATTEMPTS, limit]
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
    const pendientes = await obtenerPendientes(BATCH_LIMIT);
    if (!pendientes.length) {
      return;
    }

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

      const chatId = `${telefono}@c.us`;
      try {
        const texto = String(item.mensaje || '');
        const mediaUrl = String(item.media_url || '').trim();

        if (mediaUrl) {
          const media = await MessageMedia.fromUrl(mediaUrl, { unsafeMime: true });
          await client.sendMessage(chatId, media, { caption: texto || undefined });
        } else {
          await client.sendMessage(chatId, texto);
        }

        await marcarEnviado(id);
        console.log(`[OK] ${id} -> ${telefono}`);
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

      await sleep(DELAY_MS);
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
  console.log('WhatsApp conectado. Worker activo.');
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
