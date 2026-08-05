/**
 * Comprueba en WhatsApp (misma sesión del worker) si cada cumpleañero de hoy
 * tiene número registrado y resuelve chatId.
 *
 * Uso (con worker detenido o en otra terminal; escanea QR si hace falta):
 *   node tools/whatsapp_local/verificar_numeros_cumpleanos.js
 */
require('dotenv').config({ path: require('path').join(__dirname, '.env') });
const mysql = require('mysql2/promise');
const { Client, LocalAuth } = require('whatsapp-web.js');

function normalizarTelefono(telefono) {
  const digits = String(telefono || '').replace(/\D+/g, '');
  if (!digits) return null;
  if (/^\d{10}$/.test(digits)) return `57${digits}`;
  if (/^\d{11,15}$/.test(digits)) return digits;
  return null;
}

async function main() {
  const pool = mysql.createPool({
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT || 3306),
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'mcimadrid',
  });

  const [rows] = await pool.query(
    `SELECT Id_Persona, Nombre, Apellido, Telefono
     FROM persona
     WHERE Fecha_Nacimiento IS NOT NULL
       AND Fecha_Nacimiento <> '0000-00-00'
       AND MONTH(Fecha_Nacimiento) = MONTH(CURDATE())
       AND DAY(Fecha_Nacimiento) = DAY(CURDATE())
       AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)`
  );

  const [cola] = await pool.query(
    `SELECT telefono, estado, intentos, ultimo_error
     FROM whatsapp_local_queue
     WHERE tipo_evento = 'felicitacion_cumpleanos'
       AND DATE(creado_en) = CURDATE()`
  );
  const colaPorTel = {};
  for (const c of cola) {
    colaPorTel[String(c.telefono)] = c;
  }

  console.log(`Cumpleañeros hoy en BD: ${rows.length}\n`);

  const client = new Client({
    authStrategy: new LocalAuth({ clientId: process.env.WA_CLIENT_ID || 'mcimadrid_server' }),
    puppeteer: {
      headless: String(process.env.WA_HEADLESS || 'true').toLowerCase() !== 'false',
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
    },
  });

  client.on('qr', () => console.log('Escanea QR en la terminal (qrcode)...'));

  await new Promise((resolve, reject) => {
    client.on('ready', resolve);
    client.on('auth_failure', reject);
    client.initialize().catch(reject);
  });

  console.log('WhatsApp listo. Verificando números...\n');

  for (const p of rows) {
    const nombre = `${p.Nombre || ''} ${p.Apellido || ''}`.trim();
    const telRaw = p.Telefono;
    const tel = normalizarTelefono(telRaw);
    const col = tel ? colaPorTel[tel] || colaPorTel[`57${tel}`] : null;

    if (!tel) {
      console.log(`❌ ${nombre}: teléfono inválido en BD (${telRaw})`);
      continue;
    }

    let registrado = false;
    let chatId = null;
    try {
      const numberId = await client.getNumberId(tel);
      if (numberId && numberId._serialized) {
        chatId = numberId._serialized;
        registrado = true;
      }
    } catch (e) {
      /* ignore */
    }

    if (!registrado) {
      try {
        registrado = await client.isRegisteredUser(`${tel}@c.us`);
        if (registrado) chatId = `${tel}@c.us`;
      } catch (e) {
        /* ignore */
      }
    }

    const estadoCola = col ? `${col.estado} (int ${col.intentos}) ${col.ultimo_error || ''}` : 'sin fila cola hoy';
    const icon = registrado ? '✓' : '✗';
    console.log(
      `${icon} ${nombre}\n` +
        `   BD: ${telRaw} → ${tel}\n` +
        `   WhatsApp: ${registrado ? 'SÍ (' + chatId + ')' : 'NO registrado / no resuelve'}\n` +
        `   Cola: ${estadoCola}\n`
    );
  }

  console.log(
    '\nSi tu número sale ✓ y otros también pero fallan al enviar con imagen,\n' +
      'suele ser privacidad del destinatario (solo contactos) o que nunca han escrito a esta línea.\n' +
      'Prueba enviar la misma imagen MANUAL desde el celular del QR a un número que falló.'
  );

  await client.destroy();
  await pool.end();
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
