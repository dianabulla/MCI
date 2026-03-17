require('dotenv').config();
const mysql = require('mysql2/promise');

function parseBoolean(value, defaultValue = false) {
  if (value === undefined || value === null || value === '') return defaultValue;
  const normalized = String(value).trim().toLowerCase();
  return normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'si';
}

function getDbSslConfig() {
  const sslMode = String(process.env.DB_SSL_MODE || 'disabled').trim().toLowerCase();
  if (sslMode === 'required' || sslMode === 'true' || sslMode === '1') {
    return { rejectUnauthorized: parseBoolean(process.env.DB_SSL_REJECT_UNAUTHORIZED, false) };
  }
  return undefined;
}

async function main() {
  const config = {
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT || 3306),
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    connectTimeout: Number(process.env.DB_CONNECT_TIMEOUT_MS || 15000),
    ssl: getDbSslConfig(),
  };

  if (!config.host || !config.user || !config.database) {
    throw new Error('Faltan datos en .env: DB_HOST, DB_USER o DB_NAME');
  }

  const conn = await mysql.createConnection(config);
  try {
    const [rows] = await conn.query('SELECT DATABASE() AS db, NOW() AS server_time');
    const info = rows && rows[0] ? rows[0] : {};
    console.log('Conexion OK');
    console.log('DB activa:', info.db || '(sin db)');
    console.log('Hora servidor:', info.server_time || '(sin hora)');

    const [queueRows] = await conn.query("SHOW TABLES LIKE 'whatsapp_local_queue'");
    if (Array.isArray(queueRows) && queueRows.length > 0) {
      const [countRows] = await conn.query("SELECT COUNT(*) AS total FROM whatsapp_local_queue WHERE estado = 'pendiente'");
      const total = countRows && countRows[0] ? Number(countRows[0].total || 0) : 0;
      console.log('Tabla whatsapp_local_queue: existente');
      console.log('Pendientes actuales:', total);
    } else {
      console.log('Tabla whatsapp_local_queue: aun no existe (se crea al iniciar worker)');
    }
  } finally {
    await conn.end();
  }
}

main().catch((err) => {
  console.error('Error de conexion:', err && err.message ? err.message : err);
  process.exit(1);
});
