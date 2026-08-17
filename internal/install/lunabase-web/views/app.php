<?php
// Shell dashboard: sidebar + topbar + konten.
$isSqlView   = in_array($action, ['sql_editor', 'run_sql'], true);
$isCreateTbl = $action === 'create_table';
$isRelations = $action === 'relations';
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LunaBase — <?= h($db['dbname']) ?></title>
  <link rel="icon" type="image/svg+xml" href="assets/logo.svg">
  <link rel="stylesheet" href="assets/style.css?v=<?= LUNABASE_VERSION ?>">
</head>

<body>
  <div class="app">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo">
          <div class="moon"><img src="assets/logo.svg" alt="Luna"></div>
          Luna<span class="logo-span">Base</span>
        </div>
        <span class="driver-badge driver-<?= h($db['driver']) ?>">
          <?= $db['driver'] === 'pgsql' ? 'PG' : 'MY' ?>
        </span>
      </div>

      <!-- DB Switcher -->
      <div class="db-switcher">
        <label>Database</label>
        <form method="POST" action="?action=switch_db" id="db-form">
          <select name="dbname" onchange="document.getElementById('db-form').submit()">
            <?php foreach ($databases as $dn): ?>
              <option value="<?= h($dn) ?>" <?= $dn === $db['dbname'] ? 'selected' : '' ?>><?= h($dn) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <div class="db-actions">
          <button class="db-btn db-btn-create" onclick="showModal('modal-create')">+ Buat</button>
          <button class="db-btn db-btn-edit" onclick="showRenameModal('<?= h($db['dbname']) ?>')" title="Edit / rename database ini">✎</button>
          <button class="db-btn db-btn-drop" onclick="showDropModal('<?= h($db['dbname']) ?>')" title="Hapus database ini">✕</button>
        </div>
      </div>

      <!-- Tables -->
      <div class="sidebar-section">
        <div class="sidebar-section-label">
          <span>Tables</span>
          <span style="font-family:var(--mono);color:var(--accent2)"><?= count($tables) ?></span>
        </div>
        <ul class="table-list">
          <?php foreach ($tables as $tbl): ?>
            <li>
              <a href="?action=dashboard&table=<?= urlencode($tbl) ?>" class="<?= $activeTable === $tbl ? 'active' : '' ?>">
                <svg class="tbl-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <rect x="3" y="3" width="18" height="18" rx="2" />
                  <path d="M3 9h18M3 15h18M9 3v18" />
                </svg>
                <?= h($tbl) ?>
              </a>
            </li>
          <?php endforeach; ?>
          <?php if (empty($tables)): ?>
            <li style="padding:8px 10px;color:var(--text2);font-size:12px">Belum ada table</li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="sidebar-footer">
        <div class="conn-info">
          <strong><?= h($db['user']) ?></strong><br>
          <?= h($db['host']) ?>:<?= h($db['port']) ?>
        </div>
        <a href="?action=logout" class="btn btn-ghost btn-sm">Keluar</a>
      </div>
    </aside>

    <!-- Main -->
    <main class="main">
      <div class="topbar">
        <div class="topbar-title">
          <?php if ($isRelations): ?>
            <span class="breadcrumb"><?= h($db['dbname']) ?> / <?= h($activeTable) ?> /</span> Relasi
          <?php elseif ($activeTable): ?>
            <span class="breadcrumb"><?= h($db['dbname']) ?> /</span> <?= h($activeTable) ?>
          <?php elseif ($isCreateTbl): ?>
            <span class="breadcrumb"><?= h($db['dbname']) ?> /</span> Buat Table
          <?php elseif ($isSqlView): ?>
            <span class="breadcrumb"><?= h($db['dbname']) ?> /</span> SQL Editor
          <?php else: ?>
            <?= h($db['dbname']) ?>
          <?php endif; ?>
        </div>
        <div class="topbar-actions">
          <a href="?action=sql_editor" class="btn btn-ghost btn-sm">⌨ SQL Editor</a>
          <a href="?action=create_table" class="btn btn-ghost btn-sm">+ Buat Table</a>
          <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="5" />
              <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
            </svg>
          </button>
        </div>
      </div>

      <div class="content">
        <!-- Notifikasi -->
        <?php if (isset($_GET['created_db'])): ?>
          <div class="success-box">✓ Database <strong><?= h($_GET['created_db']) ?></strong> berhasil dibuat dan aktif.</div>
        <?php endif; ?>
        <?php if (isset($_GET['renamed_db'])): ?>
          <div class="success-box">✓ Database berhasil diubah menjadi <strong><?= h($_GET['renamed_db']) ?></strong>.</div>
        <?php endif; ?>
        <?php if (isset($_GET['dropped_db'])): ?>
          <div class="error-box">Database <strong><?= h($_GET['dropped_db']) ?></strong> berhasil dihapus.</div>
        <?php endif; ?>
        <?php if (isset($_GET['created_table'])): ?>
          <div class="success-box">✓ Table berhasil dibuat.</div>
        <?php endif; ?>
        <?php if (isset($_GET['renamed_table'])): ?>
          <div class="success-box">✓ Table berhasil diubah namanya.</div>
        <?php endif; ?>
        <?php if (isset($_GET['dropped_table'])): ?>
          <div class="error-box">Table <strong><?= h($_GET['dropped_table']) ?></strong> berhasil dihapus.</div>
        <?php endif; ?>
        <?php if (isset($_GET['row_inserted'])): ?><div class="success-box">✓ Baris berhasil ditambahkan.</div><?php endif; ?>
        <?php if (isset($_GET['row_updated'])): ?><div class="success-box">✓ Baris berhasil diperbarui.</div><?php endif; ?>
        <?php if (isset($_GET['row_deleted'])): ?><div class="error-box">Baris berhasil dihapus.</div><?php endif; ?>
        <?php if ($error): ?>
          <div class="error-box">⚠ <?= h($error) ?></div>
        <?php endif; ?>

        <?php
        if ($isRelations && $activeTable) {
          require __DIR__ . '/relations.php';
        } elseif ($isCreateTbl) {
          require __DIR__ . '/create_table.php';
        } elseif ($isSqlView) {
          require __DIR__ . '/sql_editor.php';
        } elseif ($activeTable) {
          require __DIR__ . '/table.php';
        } else {
          require __DIR__ . '/dashboard.php';
        }
        ?>
      </div>
    </main>
  </div>

  <?php require __DIR__ . '/modals.php'; ?>

  <script src="assets/app.js?v=<?= LUNABASE_VERSION ?>"></script>
</body>

</html>
