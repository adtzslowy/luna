<!-- Modal: Buat Database -->
<div class="modal-overlay" id="modal-create">
  <div class="modal-box">
    <div class="modal-title">Buat Database Baru</div>
    <div class="modal-sub">Database langsung aktif setelah dibuat.</div>
    <form method="POST" action="?action=create_database">
      <input type="text" name="dbname" class="modal-input" placeholder="nama_database" autofocus>
      <div class="modal-actions">
        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;margin:0">Buat →</button>
        <button type="button" onclick="hideModals()" class="btn btn-ghost" style="flex:1;justify-content:center">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit / Rename Database -->
<div class="modal-overlay" id="modal-rename">
  <div class="modal-box">
    <div class="modal-title">Edit Database</div>
    <div class="modal-sub">
      Ubah nama database.
      <?php if ($db['driver'] !== 'pgsql'): ?>
        <span style="color:var(--yellow)">MySQL akan memindahkan semua table ke database baru.</span>
      <?php endif; ?>
    </div>
    <form method="POST" action="?action=rename_database">
      <input type="hidden" name="old" id="rename-old">
      <input type="text" name="new" id="rename-new" class="modal-input" placeholder="nama_baru">
      <div class="modal-actions">
        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;margin:0">Simpan →</button>
        <button type="button" onclick="hideModals()" class="btn btn-ghost" style="flex:1;justify-content:center">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Hapus Database -->
<div class="modal-overlay" id="modal-drop">
  <div class="modal-box">
    <div class="modal-title" style="color:var(--red)">Hapus Database</div>
    <div class="modal-sub">
      Database <strong id="drop-name" style="font-family:var(--mono);color:var(--text)"></strong> akan dihapus permanen beserta semua datanya.
    </div>
    <form method="POST" action="?action=drop_database">
      <input type="hidden" name="dbname" id="drop-input">
      <div class="modal-actions">
        <button type="submit" class="btn-danger-solid">Hapus Permanen</button>
        <button type="button" onclick="hideModals()" class="btn btn-ghost" style="flex:1;justify-content:center">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Rename Table -->
<div class="modal-overlay" id="modal-rename-table">
  <div class="modal-box">
    <div class="modal-title">Rename Table</div>
    <div class="modal-sub">Ubah nama table.</div>
    <form method="POST" action="?action=rename_table">
      <input type="hidden" name="old" id="rename-table-old">
      <input type="text" name="new" id="rename-table-new" class="modal-input" placeholder="nama_table_baru">
      <div class="modal-actions">
        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;margin:0">Simpan →</button>
        <button type="button" onclick="hideModals()" class="btn btn-ghost" style="flex:1;justify-content:center">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Hapus Table -->
<div class="modal-overlay" id="modal-drop-table">
  <div class="modal-box">
    <div class="modal-title" style="color:var(--red)">Hapus Table</div>
    <div class="modal-sub">
      Table <strong id="drop-table-name" style="font-family:var(--mono);color:var(--text)"></strong> akan dihapus permanen beserta semua datanya.
    </div>
    <form method="POST" action="?action=drop_table">
      <input type="hidden" name="table_name" id="drop-table-input">
      <div class="modal-actions">
        <button type="submit" class="btn-danger-solid">Hapus Permanen</button>
        <button type="button" onclick="hideModals()" class="btn btn-ghost" style="flex:1;justify-content:center">Batal</button>
      </div>
    </form>
  </div>
</div>
