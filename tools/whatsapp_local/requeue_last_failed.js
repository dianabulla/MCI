require('dotenv').config();
const mysql = require('mysql2/promise');

async function main() {
  const conn = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT || 3306),
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    ssl: { rejectUnauthorized: false },
  });

  try {
    await conn.query(
      `UPDATE whatsapp_local_queue
       SET estado = 'pendiente', ultimo_error = NULL
       WHERE id = (
         SELECT id2 FROM (
           SELECT id AS id2
           FROM whatsapp_local_queue
           WHERE estado = 'fallido'
           ORDER BY id DESC
           LIMIT 1
         ) t
       )`
    );

    const [rows] = await conn.query(
      `SELECT id, telefono, tipo_evento, estado, intentos, LEFT(COALESCE(ultimo_error, ''), 140) AS error
       FROM whatsapp_local_queue
       ORDER BY id DESC
       LIMIT 5`
    );
    console.table(rows);
  } finally {
    await conn.end();
  }
}

main().catch((err) => {
  console.error('Error:', err && err.message ? err.message : err);
  process.exit(1);
});
