<?php
// Tampilan isi table + pagination + CRUD baris + aksi table.
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;
$total   = 0;
$totalPages = 1;
$rows    = [];
$columns = [];
$meta    = [];
$pks     = [];
$hasPk   = false;
try {
  $qt = qi($activeTable, $db['driver']);
  $total = $pdo->query("SELECT COUNT(*) FROM $qt")->fetchColumn();
  $totalPages = max(1, ceil($total / $perPage));
  $rows = $pdo->query("SELECT * FROM $qt LIMIT $perPage OFFSET $offset")->fetchAll();
  $meta = getColumnsMeta($pdo, $activeTable, $db['driver']);
  $pks  = getPrimaryKeys($pdo, $activeTable, $db['driver']);
  $hasPk = !empty($pks);
  $columns = $meta ? array_column($meta, 'name') : ($rows ? array_keys($rows[0]) : []);
} catch (Exception $e) {
  echo '<div class="error-box">⚠ ' . h($e->getMessage()) . '</div>';
}
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;gap:12px;flex-wrap:wrap">
  <div style="font-size:12px;color:var(--text2)">
    <?= number_format($total) ?> rows · Page <?= $page ?> / <?= $totalPages ?>
    <?php if (!$hasPk): ?><span style="color:var(--yellow)"> · read-only (tanpa primary key)</span><?php endif; ?>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if (!empty($columns)): ?>
      <button class="btn btn-primary btn-sm" style="width:auto;margin:0" onclick="addRow()">+ Tambah Baris</button>
    <?php endif; ?>
    <a href="?action=relations&table=<?= urlencode($activeTable) ?>" class="btn btn-ghost btn-sm">🔗 Relasi</a>
    <button class="btn btn-ghost btn-sm" onclick="showRenameTableModal('<?= h($activeTable) ?>')">✎ Rename</button>
    <button class="btn btn-ghost btn-sm" style="color:var(--red)" onclick="showDropTableModal('<?= h($activeTable) ?>')">✕ Hapus Table</button>
  </div>
</div>
<?php if ($page > 1 || $page < $totalPages): ?>
  <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:16px">
    <?php if ($page > 1): ?>
      <a href="?action=dashboard&table=<?= urlencode($activeTable) ?>&page=<?= $page - 1 ?>" class="btn btn-ghost btn-sm">← Prev</a>
    <?php endif; ?>
    <?php if ($page < $totalPages): ?>
      <a href="?action=dashboard&table=<?= urlencode($activeTable) ?>&page=<?= $page + 1 ?>" class="btn btn-ghost btn-sm">Next →</a>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php if (!empty($rows)): ?>
  <div class="data-table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <?php foreach ($columns as $col): ?><th><?= h($col) ?></th><?php endforeach; ?>
          <?php if ($hasPk): ?><th style="text-align:right">Aksi</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $row): ?>
          <tr>
            <?php foreach ($columns as $col): $val = $row[$col] ?? null; ?>
              <td>
                <?php if (is_null($val)): ?><span class="null-val">NULL</span>
                <?php elseif ($val === true || $val === 't'): ?><span class="bool-true">true</span>
                <?php elseif ($val === false || $val === 'f'): ?><span class="bool-false">false</span>
                <?php else: ?><?= h($val) ?><?php endif; ?>
              </td>
            <?php endforeach; ?>
            <?php if ($hasPk): ?>
              <td style="text-align:right;white-space:nowrap">
                <button class="btn-icon" title="Edit baris" onclick="editRow(<?= $i ?>)">✎</button>
                <button class="btn-icon btn-icon-danger" title="Hapus baris" onclick="deleteRow(<?= $i ?>)">✕</button>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="empty">
    <div class="icon">📭</div>
    <h3>Table kosong</h3>
    <p><?php if (!empty($columns)): ?><a href="javascript:void(0)" onclick="addRow()" style="color:var(--accent2)">Tambah baris pertama →</a><?php else: ?>Belum ada data.<?php endif; ?></p>
  </div>
<?php endif; ?>

<?php if (!empty($columns)): ?>
  <!-- Modal: Tambah / Edit Baris -->
  <div class="modal-overlay" id="modal-row">
    <div class="modal-box" style="width:480px;max-height:88vh;overflow-y:auto">
      <div class="modal-title" id="row-modal-title">Tambah Baris</div>
      <div class="modal-sub">Table <strong style="font-family:var(--mono);color:var(--text)"><?= h($activeTable) ?></strong></div>
      <form method="POST" id="row-form">
        <input type="hidden" name="__table" value="<?= h($activeTable) ?>">
        <input type="hidden" name="__page" value="<?= $page ?>">
        <div id="row-pk-holder"></div>
        <?php foreach ($meta as $m): ?>
          <div class="field">
            <label>
              <?= h($m['name']) ?>
              <span style="color:var(--text3);text-transform:none;letter-spacing:0">
                <?= h($m['type']) ?><?= $m['auto'] ? ' · auto' : ($m['nullable'] ? ' · nullable' : '') ?>
              </span>
            </label>
            <input type="text" name="data[<?= h($m['name']) ?>]" data-col="<?= h($m['name']) ?>"
              class="input-mono" autocomplete="off"
              <?= $m['auto'] ? 'placeholder="(auto)"' : '' ?>>
          </div>
        <?php endforeach; ?>
        <div class="modal-actions" style="margin-top:8px">
          <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;margin:0">Simpan →</button>
          <button type="button" onclick="hideModals()" class="btn btn-ghost" style="flex:1;justify-content:center">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Form tersembunyi untuk hapus baris -->
  <form method="POST" action="?action=delete_row" id="delete-row-form" style="display:none">
    <input type="hidden" name="__table" value="<?= h($activeTable) ?>">
    <input type="hidden" name="__page" value="<?= $page ?>">
    <div id="delete-pk-holder"></div>
  </form>

  <script>
    var LB_ROWS = <?= json_encode($rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    var LB_PKS = <?= json_encode($pks, JSON_HEX_TAG) ?>;
  </script>
<?php endif; ?>
