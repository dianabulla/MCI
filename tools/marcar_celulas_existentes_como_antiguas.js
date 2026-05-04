#!/usr/bin/env node
/*
 * Marca celulas existentes como antiguas (Es_Antiguo = 1).
 *
 * Modo por defecto: simulacion (NO escribe).
 * Modo real: --execute
 *
 * Requisitos:
 * - Node.js
 * - Cliente mysql en PATH (XAMPP/MariaDB)
 *
 * Credenciales por variables de entorno o flags:
 * - DB_HOST / --host (default: 127.0.0.1)
 * - DB_PORT / --port (default: 3306)
 * - DB_USER / --user (default: root)
 * - DB_PASS / --pass (default: empty)
 * - DB_NAME / --database (required)
 *
 * Ejemplos:
 * node tools/marcar_celulas_existentes_como_antiguas.js --database mcimadrid
 * node tools/marcar_celulas_existentes_como_antiguas.js --database mcimadrid --execute
 */

const { spawnSync } = require('child_process');

function parseArgs(argv) {
  const out = {
    execute: false,
    forceAll: false,
    host: process.env.DB_HOST || '127.0.0.1',
    port: process.env.DB_PORT || '3306',
    user: process.env.DB_USER || 'root',
    pass: process.env.DB_PASS || '',
    database: process.env.DB_NAME || '',
  };

  for (let i = 0; i < argv.length; i += 1) {
    const a = argv[i];
    const next = argv[i + 1];
    if (a === '--execute') out.execute = true;
    else if (a === '--force-all') out.forceAll = true;
    else if (a === '--host' && next) out.host = next, i += 1;
    else if (a === '--port' && next) out.port = next, i += 1;
    else if (a === '--user' && next) out.user = next, i += 1;
    else if (a === '--pass' && next) out.pass = next, i += 1;
    else if ((a === '--database' || a === '--db') && next) out.database = next, i += 1;
    else if (a === '--help' || a === '-h') out.help = true;
  }

  return out;
}

function printHelp() {
  console.log('Uso:');
  console.log('  node tools/marcar_celulas_existentes_como_antiguas.js --database <db> [--execute] [--force-all]');
  console.log('');
  console.log('Opciones:');
  console.log('  --execute       Ejecuta UPDATE real (por defecto es simulacion)');
  console.log('  --force-all     Fuerza UPDATE sobre todas las filas');
  console.log('  --host <host>   Host MySQL (default 127.0.0.1)');
  console.log('  --port <port>   Puerto MySQL (default 3306)');
  console.log('  --user <user>   Usuario MySQL (default root)');
  console.log('  --pass <pass>   Contrasena MySQL (default vacio)');
  console.log('  --database <db> Base de datos (o env DB_NAME)');
}

function runMysql(sql, cfg) {
  const args = [
    '-h', String(cfg.host),
    '-P', String(cfg.port),
    '-u', String(cfg.user),
    '-D', String(cfg.database),
    '-N',
    '-B',
    '-e', sql,
  ];

  const env = { ...process.env };
  if (cfg.pass) {
    env.MYSQL_PWD = String(cfg.pass);
  }

  const res = spawnSync('mysql', args, {
    encoding: 'utf8',
    env,
  });

  if (res.error) {
    throw new Error('No se pudo ejecutar mysql. Verifica que el cliente mysql este en PATH.');
  }

  if (res.status !== 0) {
    const stderr = (res.stderr || '').trim();
    throw new Error(stderr || 'Error ejecutando consulta MySQL.');
  }

  return (res.stdout || '').trim();
}

function toInt(value) {
  const n = Number(String(value || '').trim());
  return Number.isFinite(n) ? n : 0;
}

(function main() {
  try {
    const cfg = parseArgs(process.argv.slice(2));

    if (cfg.help) {
      printHelp();
      process.exit(0);
    }

    if (!cfg.database) {
      console.error('ERROR: Debes indicar --database o variable DB_NAME.');
      process.exit(1);
    }

    console.log('=== Marcar celulas existentes como antiguas ===');
    if (!cfg.execute) {
      console.log('MODO: SIMULACION (sin cambios)');
    } else {
      console.log('MODO: EJECUCION REAL');
    }

    // 1) Asegurar columna Es_Antiguo.
    const existsSql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'celula' AND COLUMN_NAME = 'Es_Antiguo'";
    const exists = toInt(runMysql(existsSql, cfg));
    if (exists === 0) {
      runMysql("ALTER TABLE celula ADD COLUMN Es_Antiguo TINYINT(1) NOT NULL DEFAULT 0", cfg);
      console.log('OK: Columna Es_Antiguo creada.');
    } else {
      console.log('OK: Columna Es_Antiguo ya existe.');
    }

    // 2) Resumen previo.
    const total = toInt(runMysql('SELECT COUNT(*) FROM celula', cfg));
    const antiguas = toInt(runMysql('SELECT COUNT(*) FROM celula WHERE Es_Antiguo = 1', cfg));
    const pendientes = toInt(runMysql('SELECT COUNT(*) FROM celula WHERE Es_Antiguo <> 1 OR Es_Antiguo IS NULL', cfg));

    console.log(`Total: ${total}`);
    console.log(`Antiguas: ${antiguas}`);
    console.log(`Pendientes: ${pendientes}`);

    if (!cfg.execute) {
      console.log('Simulacion completada. Ejecuta con --execute para aplicar cambios.');
      process.exit(0);
    }

    if (pendientes <= 0 && !cfg.forceAll) {
      console.log('Nada por actualizar.');
      process.exit(0);
    }

    // 3) Update real.
    if (cfg.forceAll) {
      runMysql('UPDATE celula SET Es_Antiguo = 1', cfg);
      console.log('UPDATE aplicado sobre todas las filas (--force-all).');
    } else {
      runMysql('UPDATE celula SET Es_Antiguo = 1 WHERE Es_Antiguo <> 1 OR Es_Antiguo IS NULL', cfg);
      console.log('UPDATE aplicado sobre filas pendientes.');
    }

    const antiguasFinal = toInt(runMysql('SELECT COUNT(*) FROM celula WHERE Es_Antiguo = 1', cfg));
    const pendientesFinal = toInt(runMysql('SELECT COUNT(*) FROM celula WHERE Es_Antiguo <> 1 OR Es_Antiguo IS NULL', cfg));

    console.log(`Antiguas (final): ${antiguasFinal}`);
    console.log(`Pendientes (final): ${pendientesFinal}`);
    console.log('Proceso finalizado.');
  } catch (err) {
    console.error('ERROR:', err.message || err);
    process.exit(1);
  }
})();
