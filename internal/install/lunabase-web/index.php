<?php
// ─────────────────────────────────────────────
// LunaBase — front controller
// Struktur:
//   inc/helpers.php   → koneksi & helper
//   inc/actions.php   → handler POST (login, CRUD database/table, SQL)
//   views/            → tampilan (login, dashboard, table, dll)
//   assets/           → style.css, app.js, logo.svg
// ─────────────────────────────────────────────
session_start();

define('LUNABASE_VERSION', '1.2.0');

require __DIR__ . '/inc/helpers.php';

$action  = $_GET['action'] ?? 'login';
$error   = '';
$success = '';

// Handle aksi (redirect & exit jika sukses).
require __DIR__ . '/inc/actions.php';

// ─── Data untuk render ───
$db  = $_SESSION['db'] ?? null;

// Belum login → paksa ke halaman login, tanpa perlu coba koneksi dulu.
if ($action === 'login' || !$db) {
  $pdo = null;
} else {
  $pdo = getConnection();
  if (!$pdo) {
    header('Location: ?action=login');
    exit;
  }
}

$databases   = [];
$tables      = [];
$activeTable = $_GET['table'] ?? null;

if ($pdo && $db) {
  try {
    $databases = getDatabases($pdo, $db['driver']);
  } catch (Exception $e) {
  }
  try {
    $tables = getTables($pdo, $db['driver']);
  } catch (Exception $e) {
  }
}

// ─── Render ───
if ($action === 'login' || !$pdo) {
  require __DIR__ . '/views/login.php';
} else {
  require __DIR__ . '/views/app.php';
}
