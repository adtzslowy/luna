<?php
// ─────────────────────────────────────────────
// LunaBase — request handlers (POST actions)
// Mengembalikan pesan error lewat variabel $error.
// Action yang sukses akan redirect & exit.
// ─────────────────────────────────────────────

$post = $_SERVER['REQUEST_METHOD'] === 'POST';

// LOGIN
if ($action === 'login' && $post) {
  $driver   = $_POST['driver']   ?? 'pgsql';
  $host     = $_POST['host']     ?? 'localhost';
  $port     = $_POST['port']     ?? ($driver === 'pgsql' ? '5432' : '3306');
  $user     = $_POST['user']     ?? '';
  $password = $_POST['password'] ?? '';
  $dbname   = trim($_POST['dbname'] ?? '');
  $defaultDb = defaultDb($driver);
  $targetDb  = $dbname ?: $defaultDb;
  $opts = pdoOptions();

  try {
    new PDO(makeDsn($driver, $host, $port, $targetDb), $user, $password, $opts);
    $_SESSION['db'] = compact('driver', 'host', 'port', 'user', 'password') + ['dbname' => $targetDb];
    header('Location: ?action=dashboard');
    exit;
  } catch (Exception $e) {
    try {
      $pdoDef = new PDO(makeDsn($driver, $host, $port, $defaultDb), $user, $password, $opts);
      if ($targetDb !== $defaultDb) {
        $safe = safeDb($targetDb);
        if ($safe) {
          if ($driver === 'pgsql') $pdoDef->exec("CREATE DATABASE \"$safe\"");
          else $pdoDef->exec("CREATE DATABASE `$safe`");
          new PDO(makeDsn($driver, $host, $port, $safe), $user, $password, $opts);
          $_SESSION['db'] = compact('driver', 'host', 'port', 'user', 'password') + ['dbname' => $safe];
          header('Location: ?action=dashboard&created_db=' . urlencode($safe));
          exit;
        }
      }
      $_SESSION['db'] = compact('driver', 'host', 'port', 'user', 'password') + ['dbname' => $defaultDb];
      header('Location: ?action=dashboard');
      exit;
    } catch (Exception $e2) {
      $error = $e2->getMessage();
    }
  }
}

// LOGOUT
if ($action === 'logout') {
  session_destroy();
  header('Location: ?action=login');
  exit;
}

// SWITCH DB
if ($action === 'switch_db' && $post && isset($_SESSION['db'])) {
  $_SESSION['db']['dbname'] = $_POST['dbname'];
  header('Location: ?action=dashboard');
  exit;
}

// CREATE DATABASE
if ($action === 'create_database' && $post && isset($_SESSION['db'])) {
  $newDb  = safeDb(trim($_POST['dbname'] ?? ''));
  $driver = $_SESSION['db']['driver'];
  if ($newDb) {
    try {
      $pdo_tmp = getConnection();
      if ($driver === 'pgsql') $pdo_tmp->exec("CREATE DATABASE \"$newDb\"");
      else $pdo_tmp->exec("CREATE DATABASE `$newDb`");
      $_SESSION['db']['dbname'] = $newDb;
      header('Location: ?action=dashboard&created_db=' . urlencode($newDb));
      exit;
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  } else {
    $error = 'Nama database tidak valid.';
  }
}

// RENAME / EDIT DATABASE
if ($action === 'rename_database' && $post && isset($_SESSION['db'])) {
  $oldDb  = safeDb(trim($_POST['old'] ?? ''));
  $newDb  = safeDb(trim($_POST['new'] ?? ''));
  $driver = $_SESSION['db']['driver'];

  if (!$oldDb || !$newDb) {
    $error = 'Nama database tidak valid.';
  } elseif (isProtectedDb($oldDb)) {
    $error = 'Database ini tidak bisa diubah namanya.';
  } elseif ($oldDb === $newDb) {
    $error = 'Nama database baru sama dengan yang lama.';
  } else {
    $prev = $_SESSION['db']['dbname'];
    try {
      if ($driver === 'pgsql') {
        // PostgreSQL: rename native. Harus terkoneksi ke db lain (default).
        $pdoDef = connectTo(defaultDb($driver));
        $pdoDef->exec("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname='$oldDb' AND pid <> pg_backend_pid()");
        $pdoDef->exec("ALTER DATABASE \"$oldDb\" RENAME TO \"$newDb\"");
      } else {
        // MySQL tidak punya RENAME DATABASE: buat db baru, pindahkan semua table, drop db lama.
        $pdoOld = connectTo($oldDb);
        $pdoOld->exec("CREATE DATABASE `$newDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $tbls = array_column($pdoOld->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM), 0);
        $pdoOld->exec("SET FOREIGN_KEY_CHECKS=0");
        foreach ($tbls as $t) {
          $qt = qi($t, 'mysql');
          $pdoOld->exec("RENAME TABLE `$oldDb`.$qt TO `$newDb`.$qt");
        }
        $pdoOld->exec("SET FOREIGN_KEY_CHECKS=1");
        $pdoOld->exec("DROP DATABASE `$oldDb`");
      }
      $_SESSION['db']['dbname'] = $newDb;
      header('Location: ?action=dashboard&renamed_db=' . urlencode($newDb));
      exit;
    } catch (Exception $e) {
      $_SESSION['db']['dbname'] = $prev;
      $error = $e->getMessage();
    }
  }
}

// DROP DATABASE
if ($action === 'drop_database' && $post && isset($_SESSION['db'])) {
  $dropDb  = safeDb(trim($_POST['dbname'] ?? ''));
  $driver  = $_SESSION['db']['driver'];
  if (isProtectedDb($dropDb)) {
    $error = 'Database ini tidak bisa dihapus.';
  } elseif ($dropDb) {
    $prev = $_SESSION['db']['dbname'];
    try {
      $_SESSION['db']['dbname'] = defaultDb($driver);
      $pdo_tmp = getConnection();
      if ($driver === 'pgsql') {
        $pdo_tmp->exec("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname='$dropDb'");
        $pdo_tmp->exec("DROP DATABASE \"$dropDb\"");
      } else {
        $pdo_tmp->exec("DROP DATABASE `$dropDb`");
      }
      header('Location: ?action=dashboard&dropped_db=' . urlencode($dropDb));
      exit;
    } catch (Exception $e) {
      $_SESSION['db']['dbname'] = $prev;
      $error = $e->getMessage();
    }
  }
}

// CREATE TABLE
if ($action === 'create_table' && $post && isset($_SESSION['db'])) {
  $driver = $_SESSION['db']['driver'];
  $tname  = trim($_POST['table_name'] ?? '');
  $cols   = $_POST['columns'] ?? [];
  if (!$tname) {
    $error = 'Nama table tidak boleh kosong.';
  } elseif (empty($cols)) {
    $error = 'Minimal satu kolom.';
  } else {
    $defs = [];
    foreach ($cols as $col) {
      $cname = trim($col['name'] ?? '');
      if (!$cname) continue;
      $ctype = $col['type'] ?? 'VARCHAR(255)';
      $cauto = isset($col['auto']);
      $cpk   = isset($col['primary']);
      $cnull = isset($col['nullable']) ? '' : 'NOT NULL';
      if ($cauto) {
        $defs[] = $driver === 'pgsql'
          ? qi($cname, $driver) . " SERIAL PRIMARY KEY"
          : qi($cname, $driver) . " INT AUTO_INCREMENT PRIMARY KEY";
      } else {
        $defs[] = trim(qi($cname, $driver) . " $ctype $cnull " . ($cpk ? 'PRIMARY KEY' : ''));
      }
    }
    try {
      $pdo_tmp = getConnection();
      $pdo_tmp->exec("CREATE TABLE " . qi($tname, $driver) . " (\n  " . implode(",\n  ", $defs) . "\n)");
      header('Location: ?action=dashboard&table=' . urlencode($tname) . '&created_table=1');
      exit;
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }
}

// DROP TABLE
if ($action === 'drop_table' && $post && isset($_SESSION['db'])) {
  $driver = $_SESSION['db']['driver'];
  $tname  = trim($_POST['table_name'] ?? '');
  if ($tname) {
    try {
      $pdo_tmp = getConnection();
      $pdo_tmp->exec("DROP TABLE " . qi($tname, $driver));
      header('Location: ?action=dashboard&dropped_table=' . urlencode($tname));
      exit;
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }
}

// RENAME TABLE
if ($action === 'rename_table' && $post && isset($_SESSION['db'])) {
  $driver = $_SESSION['db']['driver'];
  $old    = trim($_POST['old'] ?? '');
  $new    = trim($_POST['new'] ?? '');
  if ($old && $new && $old !== $new) {
    try {
      $pdo_tmp = getConnection();
      if ($driver === 'pgsql') {
        $pdo_tmp->exec("ALTER TABLE " . qi($old, $driver) . " RENAME TO " . qi($new, $driver));
      } else {
        $pdo_tmp->exec("RENAME TABLE " . qi($old, $driver) . " TO " . qi($new, $driver));
      }
      header('Location: ?action=dashboard&table=' . urlencode($new) . '&renamed_table=1');
      exit;
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }
}

// ─────────────────────────────────────────────
// Row CRUD — edit isi table (prepared statements)
// ─────────────────────────────────────────────

// INSERT ROW
if ($action === 'insert_row' && $post && isset($_SESSION['db'])) {
  $driver = $_SESSION['db']['driver'];
  $table  = $_POST['__table'] ?? '';
  $data   = $_POST['data'] ?? [];
  $pdo_tmp = getConnection();
  if (!in_array($table, getTables($pdo_tmp, $driver), true)) {
    $error = 'Table tidak ditemukan.';
  } else {
    // Hanya sertakan kolom yang diisi; sisanya pakai default/auto-increment.
    $cols = [];
    $vals = [];
    foreach ($data as $c => $v) {
      if ($v === '') continue;
      $cols[] = qi($c, $driver);
      $vals[] = $v;
    }
    try {
      if (empty($cols)) {
        $sql = $driver === 'pgsql'
          ? "INSERT INTO " . qi($table, $driver) . " DEFAULT VALUES"
          : "INSERT INTO " . qi($table, $driver) . " () VALUES ()";
        $pdo_tmp->exec($sql);
      } else {
        $ph = implode(',', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO " . qi($table, $driver) . " (" . implode(',', $cols) . ") VALUES ($ph)";
        $pdo_tmp->prepare($sql)->execute($vals);
      }
      header('Location: ?action=dashboard&table=' . urlencode($table) . '&row_inserted=1');
      exit;
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }
}

// UPDATE ROW
if ($action === 'update_row' && $post && isset($_SESSION['db'])) {
  $driver = $_SESSION['db']['driver'];
  $table  = $_POST['__table'] ?? '';
  $data   = $_POST['data'] ?? [];
  $pk     = $_POST['__pk']  ?? [];
  $pdo_tmp = getConnection();
  if (!in_array($table, getTables($pdo_tmp, $driver), true)) {
    $error = 'Table tidak ditemukan.';
  } elseif (empty($pk)) {
    $error = 'Baris tanpa primary key tidak bisa diedit.';
  } else {
    // Nullable + dikosongkan = set NULL.
    $nullable = [];
    foreach (getColumnsMeta($pdo_tmp, $table, $driver) as $m) $nullable[$m['name']] = $m['nullable'];
    $set = [];
    $vals = [];
    foreach ($data as $c => $v) {
      $set[]  = qi($c, $driver) . " = ?";
      $vals[] = ($v === '' && !empty($nullable[$c])) ? null : $v;
    }
    $where = [];
    foreach ($pk as $c => $v) {
      $where[] = qi($c, $driver) . " = ?";
      $vals[]  = $v;
    }
    try {
      $sql = "UPDATE " . qi($table, $driver) . " SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $where);
      $pdo_tmp->prepare($sql)->execute($vals);
      $back = $_POST['__page'] ?? 1;
      header('Location: ?action=dashboard&table=' . urlencode($table) . '&page=' . (int)$back . '&row_updated=1');
      exit;
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }
}

// DELETE ROW
if ($action === 'delete_row' && $post && isset($_SESSION['db'])) {
  $driver = $_SESSION['db']['driver'];
  $table  = $_POST['__table'] ?? '';
  $pk     = $_POST['__pk']  ?? [];
  $pdo_tmp = getConnection();
  if (!in_array($table, getTables($pdo_tmp, $driver), true)) {
    $error = 'Table tidak ditemukan.';
  } elseif (empty($pk)) {
    $error = 'Baris tanpa primary key tidak bisa dihapus.';
  } else {
    $where = [];
    $vals  = [];
    foreach ($pk as $c => $v) {
      $where[] = qi($c, $driver) . " = ?";
      $vals[]  = $v;
    }
    try {
      $sql = "DELETE FROM " . qi($table, $driver) . " WHERE " . implode(' AND ', $where);
      $pdo_tmp->prepare($sql)->execute($vals);
      $back = $_POST['__page'] ?? 1;
      header('Location: ?action=dashboard&table=' . urlencode($table) . '&page=' . (int)$back . '&row_deleted=1');
      exit;
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }
}

// ─────────────────────────────────────────────
// Relationship — foreign key
// ─────────────────────────────────────────────

// ADD FOREIGN KEY
if ($action === 'add_foreign_key' && $post && isset($_SESSION['db'])) {
  $driver  = $_SESSION['db']['driver'];
  $table   = $_POST['__table'] ?? '';
  $col     = $_POST['column'] ?? '';
  $refTab  = $_POST['ref_table'] ?? '';
  $refCol  = trim($_POST['ref_column'] ?? '');
  $onDel   = strtoupper($_POST['on_delete'] ?? '');
  $allowed = ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION'];
  $pdo_tmp = getConnection();
  $tables  = getTables($pdo_tmp, $driver);
  $cols    = array_column(getColumnsMeta($pdo_tmp, $table, $driver), 'name');

  if (!in_array($table, $tables, true) || !in_array($refTab, $tables, true)) {
    $error = 'Table tidak valid.';
  } elseif (!in_array($col, $cols, true) || $refCol === '') {
    $error = 'Kolom tidak valid.';
  } else {
    $name = safeDb("fk_{$table}_{$col}_{$refTab}");
    try {
      $sql = "ALTER TABLE " . qi($table, $driver) . " ADD CONSTRAINT " . qi($name, $driver)
        . " FOREIGN KEY (" . qi($col, $driver) . ") REFERENCES " . qi($refTab, $driver) . " (" . qi($refCol, $driver) . ")";
      if (in_array($onDel, $allowed, true)) $sql .= " ON DELETE $onDel";
      $pdo_tmp->exec($sql);
      header('Location: ?action=relations&table=' . urlencode($table) . '&fk_added=1');
      exit;
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }
}

// DROP FOREIGN KEY
if ($action === 'drop_foreign_key' && $post && isset($_SESSION['db'])) {
  $driver = $_SESSION['db']['driver'];
  $table  = $_POST['__table'] ?? '';
  $name   = $_POST['constraint'] ?? '';
  $pdo_tmp = getConnection();
  if (!in_array($table, getTables($pdo_tmp, $driver), true)) {
    $error = 'Table tidak ditemukan.';
  } else {
    $existing = array_column(getForeignKeys($pdo_tmp, $table, $driver), 'constraint');
    if (!in_array($name, $existing, true)) {
      $error = 'Constraint tidak ditemukan.';
    } else {
      try {
        $sql = $driver === 'pgsql'
          ? "ALTER TABLE " . qi($table, $driver) . " DROP CONSTRAINT " . qi($name, $driver)
          : "ALTER TABLE " . qi($table, $driver) . " DROP FOREIGN KEY " . qi($name, $driver);
        $pdo_tmp->exec($sql);
        header('Location: ?action=relations&table=' . urlencode($table) . '&fk_dropped=1');
        exit;
      } catch (Exception $e) {
        $error = $e->getMessage();
      }
    }
  }
}

// RUN SQL
if ($action === 'run_sql' && $post && isset($_SESSION['db'])) {
  $sql_query    = trim($_POST['sql'] ?? '');
  $sql_result   = null;
  $sql_error    = null;
  $sql_affected = null;
  if ($sql_query) {
    try {
      $pdo_tmp = getConnection();
      $stmt = $pdo_tmp->query($sql_query);
      if ($stmt && $stmt->columnCount() > 0) $sql_result = $stmt->fetchAll();
      else $sql_affected = $stmt ? $stmt->rowCount() : 0;
    } catch (Exception $e) {
      $sql_error = $e->getMessage();
    }
  }
}
