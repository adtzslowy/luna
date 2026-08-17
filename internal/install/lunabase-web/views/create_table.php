<?php // Form buat table baru. ?>
<div style="max-width:860px">
  <div class="section-header" style="margin-bottom:20px">
    <span class="section-title">Buat Table Baru</span>
    <a href="?action=dashboard" class="btn btn-ghost btn-sm">← Kembali</a>
  </div>
  <form method="POST" action="?action=create_table">
    <div class="field" style="margin-bottom:20px">
      <label>Nama Table</label>
      <input type="text" name="table_name" placeholder="users" class="input-mono">
    </div>
    <div class="section-title" style="margin-bottom:12px">Kolom</div>
    <div style="display:grid;grid-template-columns:1fr 160px 50px 50px 50px 36px;gap:8px;padding:0 12px 8px;font-size:10px;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.05em">
      <span>Nama Kolom</span><span>Tipe Data</span><span>PK</span><span>NULL</span><span>AUTO</span><span></span>
    </div>
    <div id="col-list">
      <div class="col-row">
        <input type="text" name="columns[0][name]" placeholder="id">
        <select name="columns[0][type]">
          <option>SERIAL</option>
          <option>INT</option>
          <option>BIGINT</option>
          <option>VARCHAR(255)</option>
          <option>TEXT</option>
          <option>BOOLEAN</option>
          <option>TIMESTAMP</option>
          <option>DATE</option>
          <option>NUMERIC</option>
          <option>JSONB</option>
          <option>UUID</option>
        </select>
        <div class="col-checkbox"><input type="checkbox" name="columns[0][primary]"></div>
        <div class="col-checkbox"><input type="checkbox" name="columns[0][nullable]"></div>
        <div class="col-checkbox"><input type="checkbox" name="columns[0][auto]" checked></div>
        <button type="button" class="btn-danger" onclick="removeCol(this)">✕</button>
      </div>
    </div>
    <button type="button" class="btn btn-ghost btn-sm" onclick="addCol()" style="margin:12px 0 28px">+ Tambah Kolom</button>
    <br>
    <button type="submit" class="btn btn-primary" style="width:auto;padding:10px 28px">Buat Table →</button>
  </form>
</div>
