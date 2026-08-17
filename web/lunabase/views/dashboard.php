<?php // Ringkasan database + grid table. ?>
<div class="stats-grid">
  <div class="stat-card">
    <div class="label">Tables</div>
    <div class="value"><?= count($tables) ?></div>
    <div class="sub">di <?= h($db['dbname']) ?></div>
  </div>
  <div class="stat-card">
    <div class="label">Databases</div>
    <div class="value"><?= count($databases) ?></div>
    <div class="sub">tersedia</div>
  </div>
  <div class="stat-card">
    <div class="label">Driver</div>
    <div class="value" style="font-size:16px;color:var(--accent2);letter-spacing:0">
      <?= $db['driver'] === 'pgsql' ? 'PostgreSQL' : 'MySQL' ?>
    </div>
    <div class="sub"><?= h($db['host']) ?>:<?= h($db['port']) ?></div>
  </div>
</div>

<?php if (!empty($tables)): ?>
  <div class="section-header">
    <span class="section-title">Tables</span>
  </div>
  <div class="search-wrap">
    <span class="search-icon">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8" />
        <path d="M21 21l-4.35-4.35" />
      </svg>
    </span>
    <input type="text" placeholder="Cari table..." oninput="filterTables(this.value)">
  </div>
  <div class="tables-grid" id="tables-grid">
    <?php foreach ($tables as $tbl): ?>
      <?php $cnt = getRowCount($pdo, $tbl, $db['driver']); ?>
      <a href="?action=dashboard&table=<?= urlencode($tbl) ?>" class="table-card" data-name="<?= h($tbl) ?>">
        <div class="table-name"><?= h($tbl) ?></div>
        <div class="table-meta">
          <span class="row-count"><?= number_format($cnt) ?></span>
          <span>rows</span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty">
    <div class="icon">📭</div>
    <h3>Database kosong</h3>
    <p>Belum ada table. <a href="?action=create_table" style="color:var(--accent2)">Buat table baru →</a></p>
  </div>
<?php endif; ?>
