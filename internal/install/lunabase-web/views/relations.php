<?php
// Setup relationship / foreign key untuk sebuah table.
$fks  = [];
$cols = [];
try {
  $fks  = getForeignKeys($pdo, $activeTable, $db['driver']);
  $cols = array_column(getColumnsMeta($pdo, $activeTable, $db['driver']), 'name');
} catch (Exception $e) {
  echo '<div class="error-box">⚠ ' . h($e->getMessage()) . '</div>';
}
?>
<div style="max-width:760px">
  <div class="section-header" style="margin-bottom:20px">
    <span class="section-title">Relasi — <?= h($activeTable) ?></span>
    <a href="?action=dashboard&table=<?= urlencode($activeTable) ?>" class="btn btn-ghost btn-sm">← Kembali ke table</a>
  </div>

  <?php if (isset($_GET['fk_added'])): ?><div class="success-box">✓ Foreign key berhasil ditambahkan.</div><?php endif; ?>
  <?php if (isset($_GET['fk_dropped'])): ?><div class="error-box">Foreign key berhasil dihapus.</div><?php endif; ?>

  <!-- Daftar FK yang ada -->
  <div class="section-title" style="margin-bottom:12px">Foreign Key Saat Ini</div>
  <?php if (!empty($fks)): ?>
    <div class="data-table-wrap" style="margin-bottom:28px">
      <table class="data-table">
        <thead>
          <tr><th>Constraint</th><th>Kolom</th><th>Mereferensi</th><th style="text-align:right">Aksi</th></tr>
        </thead>
        <tbody>
          <?php foreach ($fks as $fk): ?>
            <tr>
              <td><?= h($fk['constraint']) ?></td>
              <td><?= h($fk['column']) ?></td>
              <td><span style="color:var(--accent2)"><?= h($fk['foreign_table']) ?></span>(<?= h($fk['foreign_column']) ?>)</td>
              <td style="text-align:right">
                <form method="POST" action="?action=drop_foreign_key" style="display:inline"
                  onsubmit="return confirm('Hapus foreign key <?= h($fk['constraint']) ?>?')">
                  <input type="hidden" name="__table" value="<?= h($activeTable) ?>">
                  <input type="hidden" name="constraint" value="<?= h($fk['constraint']) ?>">
                  <button type="submit" class="btn-icon btn-icon-danger" title="Hapus FK">✕</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div style="color:var(--text2);font-size:13px;margin-bottom:28px">Belum ada foreign key di table ini.</div>
  <?php endif; ?>

  <!-- Tambah FK -->
  <div class="section-title" style="margin-bottom:12px">Tambah Relasi</div>
  <form method="POST" action="?action=add_foreign_key" style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:20px">
    <input type="hidden" name="__table" value="<?= h($activeTable) ?>">
    <div class="field">
      <label>Kolom di table ini</label>
      <select name="column">
        <?php foreach ($cols as $c): ?><option value="<?= h($c) ?>"><?= h($c) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field field-row" style="grid-template-columns:1fr 1fr">
      <div>
        <label>Table tujuan</label>
        <select name="ref_table" id="fk-ref-table" onchange="this.nextElementSibling">
          <?php foreach ($tables as $t): ?>
            <option value="<?= h($t) ?>"><?= h($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Kolom tujuan</label>
        <input type="text" name="ref_column" class="input-mono" value="id" placeholder="id">
      </div>
    </div>
    <div class="field">
      <label>ON DELETE</label>
      <select name="on_delete">
        <option value="NO ACTION">NO ACTION</option>
        <option value="CASCADE">CASCADE</option>
        <option value="SET NULL">SET NULL</option>
        <option value="RESTRICT">RESTRICT</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary" style="width:auto;padding:10px 28px;margin-top:8px">Tambah Relasi →</button>
  </form>
  <p style="color:var(--text2);font-size:12px;margin-top:12px">
    Tipe data kolom & kolom tujuan harus cocok, dan kolom tujuan harus berupa primary key / unique.
  </p>
</div>
