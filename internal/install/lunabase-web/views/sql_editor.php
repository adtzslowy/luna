<?php // SQL editor + hasil query. ?>
<div class="section-header" style="margin-bottom:20px">
  <span class="section-title">SQL Editor</span>
  <span style="font-size:12px;color:var(--text2)">Ctrl+Enter untuk jalankan</span>
</div>
<?php if (isset($sql_error)): ?><div class="error-box">⚠ <?= h($sql_error) ?></div><?php endif; ?>
<?php if (isset($sql_affected)): ?><div class="success-box">✓ Query berhasil — <?= (int)$sql_affected ?> baris terpengaruh</div><?php endif; ?>
<form method="POST" action="?action=run_sql" id="sql-form">
  <textarea class="sql-editor" name="sql" placeholder="SELECT * FROM users LIMIT 10;&#10;-- Ctrl+Enter untuk jalankan"><?= h($_POST['sql'] ?? '') ?></textarea>
  <div style="display:flex;justify-content:flex-end;margin-top:10px;gap:8px">
    <button type="button" onclick="document.querySelector('.sql-editor').value=''" class="btn btn-ghost btn-sm">Hapus</button>
    <button type="submit" class="btn btn-primary" style="width:auto;padding:10px 24px">▶ Jalankan</button>
  </div>
</form>
<?php if (isset($sql_result) && $sql_result !== null): ?>
  <div class="result-wrap">
    <div class="result-header"><?= count($sql_result) ?> baris dikembalikan</div>
    <?php if (!empty($sql_result)): ?>
      <table class="result-table">
        <thead>
          <tr><?php foreach (array_keys($sql_result[0]) as $col): ?><th><?= h($col) ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody>
          <?php foreach ($sql_result as $row): ?>
            <tr>
              <?php foreach ($row as $val): ?>
                <td><?= is_null($val) ? '<span class="null-val">NULL</span>' : h($val) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
<?php endif; ?>
