<?php
// ─────────────────────────────────────────────
// LunaBase — helpers & database connection
// ─────────────────────────────────────────────

// Database yang dilindungi: tidak boleh di-rename atau di-drop.
const PROTECTED_DBS = ['postgres', 'mysql', 'information_schema', 'performance_schema', 'sys', 'template0', 'template1'];

function makeDsn($driver, $host, $port, $dbname)
{
  if ($driver === 'pgsql') {
    // Static PHP libpq tidak bisa connect ke PostgreSQL 16 via TCP.
    // Gunakan Unix socket untuk koneksi lokal agar tetap cepat dan stabil.
    $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    $pgsqlHost = $isLocal ? '/tmp' : $host;
    return "pgsql:host={$pgsqlHost};port={$port};dbname={$dbname};connect_timeout=3";
  }
  return "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
}

function pdoOptions(): array
{
  return [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_TIMEOUT            => 3,
  ];
}

function getConnection()
{
  if (!isset($_SESSION['db'])) return null;
  $db = $_SESSION['db'];
  try {
    return new PDO(
      makeDsn($db['driver'], $db['host'], $db['port'], $db['dbname']),
      $db['user'],
      $db['password'],
      pdoOptions()
    );
  } catch (Exception $e) {
    return null;
  }
}

// Koneksi ke database tertentu memakai kredensial dari session.
function connectTo($dbname)
{
  $db = $_SESSION['db'];
  return new PDO(
    makeDsn($db['driver'], $db['host'], $db['port'], $dbname),
    $db['user'],
    $db['password'],
    pdoOptions()
  );
}

function getDatabases($pdo, $driver)
{
  if ($driver === 'pgsql') {
    return array_column($pdo->query("SELECT datname FROM pg_database WHERE datistemplate = false ORDER BY datname")->fetchAll(), 'datname');
  }
  return array_column($pdo->query("SHOW DATABASES")->fetchAll(), 'Database');
}

function getTables($pdo, $driver)
{
  if ($driver === 'pgsql') {
    return array_column($pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_catalog=current_database() ORDER BY table_name")->fetchAll(), 'table_name');
  }
  return array_column($pdo->query("SHOW TABLES")->fetchAll(), 0);
}

function getRowCount($pdo, $table, $driver)
{
  try {
    return $pdo->query("SELECT COUNT(*) FROM " . qi($table, $driver))->fetchColumn();
  } catch (Exception $e) {
    return 0;
  }
}

// Quote identifier sesuai driver.
function qi($name, $driver)
{
  if ($driver === 'pgsql') return '"' . str_replace('"', '""', $name) . '"';
  return '`' . str_replace('`', '``', $name) . '`';
}

// Bersihkan nama database agar aman dipakai di statement DDL.
function safeDb($name)
{
  return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
}

// Metadata kolom: name, type, nullable, auto (auto-increment/serial).
function getColumnsMeta($pdo, $table, $driver)
{
  if ($driver === 'pgsql') {
    $stmt = $pdo->prepare("SELECT column_name, data_type, is_nullable, column_default
      FROM information_schema.columns
      WHERE table_schema='public' AND table_name=:t ORDER BY ordinal_position");
    $stmt->execute([':t' => $table]);
    $out = [];
    foreach ($stmt->fetchAll() as $r) {
      $out[] = [
        'name'     => $r['column_name'],
        'type'     => $r['data_type'],
        'nullable' => $r['is_nullable'] === 'YES',
        'auto'     => $r['column_default'] !== null && stripos($r['column_default'], 'nextval(') !== false,
      ];
    }
    return $out;
  }
  $stmt = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, EXTRA
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:t ORDER BY ORDINAL_POSITION");
  $stmt->execute([':t' => $table]);
  $out = [];
  foreach ($stmt->fetchAll() as $r) {
    $out[] = [
      'name'     => $r['COLUMN_NAME'],
      'type'     => $r['DATA_TYPE'],
      'nullable' => $r['IS_NULLABLE'] === 'YES',
      'auto'     => stripos($r['EXTRA'] ?? '', 'auto_increment') !== false,
    ];
  }
  return $out;
}

// Daftar nama kolom primary key sebuah table.
function getPrimaryKeys($pdo, $table, $driver)
{
  if ($driver === 'pgsql') {
    $stmt = $pdo->prepare("SELECT kcu.column_name
      FROM information_schema.table_constraints tc
      JOIN information_schema.key_column_usage kcu
        ON tc.constraint_name=kcu.constraint_name AND tc.table_schema=kcu.table_schema
      WHERE tc.constraint_type='PRIMARY KEY' AND tc.table_name=:t AND tc.table_schema='public'
      ORDER BY kcu.ordinal_position");
    $stmt->execute([':t' => $table]);
    return array_column($stmt->fetchAll(), 'column_name');
  }
  $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:t AND CONSTRAINT_NAME='PRIMARY'
    ORDER BY ORDINAL_POSITION");
  $stmt->execute([':t' => $table]);
  return array_column($stmt->fetchAll(), 'COLUMN_NAME');
}

// Daftar foreign key: constraint, column, foreign_table, foreign_column.
function getForeignKeys($pdo, $table, $driver)
{
  if ($driver === 'pgsql') {
    $stmt = $pdo->prepare("SELECT tc.constraint_name AS constraint, kcu.column_name AS column,
        ccu.table_name AS foreign_table, ccu.column_name AS foreign_column
      FROM information_schema.table_constraints tc
      JOIN information_schema.key_column_usage kcu
        ON tc.constraint_name=kcu.constraint_name AND tc.table_schema=kcu.table_schema
      JOIN information_schema.constraint_column_usage ccu
        ON ccu.constraint_name=tc.constraint_name AND ccu.table_schema=tc.table_schema
      WHERE tc.constraint_type='FOREIGN KEY' AND tc.table_name=:t AND tc.table_schema='public'");
    $stmt->execute([':t' => $table]);
    return $stmt->fetchAll();
  }
  $stmt = $pdo->prepare("SELECT CONSTRAINT_NAME AS `constraint`, COLUMN_NAME AS `column`,
      REFERENCED_TABLE_NAME AS foreign_table, REFERENCED_COLUMN_NAME AS foreign_column
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:t AND REFERENCED_TABLE_NAME IS NOT NULL");
  $stmt->execute([':t' => $table]);
  return $stmt->fetchAll();
}

function isProtectedDb($name)
{
  return in_array($name, PROTECTED_DBS, true);
}

function defaultDb($driver)
{
  return $driver === 'pgsql' ? 'postgres' : 'mysql';
}

// Escape untuk output HTML.
function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
