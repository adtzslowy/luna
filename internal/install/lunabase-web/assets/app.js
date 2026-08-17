// ─────────────────────────────────────────────
// LunaBase — client side
// ─────────────────────────────────────────────

// Database names that are protected from edit/delete
const PROTECTED_DB = ['postgres', 'mysql', 'information_schema', 'performance_schema', 'sys', 'template0', 'template1'];

// Theme
function toggleTheme() {
  const h = document.documentElement;
  const n = h.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  h.setAttribute('data-theme', n);
  localStorage.setItem('lb-theme', n);
}
(function () {
  const t = localStorage.getItem('lb-theme');
  if (t) document.documentElement.setAttribute('data-theme', t);
})();

// Filter tables on dashboard
function filterTables(q) {
  const needle = q.toLowerCase();
  document.querySelectorAll('.table-card').forEach(c => {
    c.style.display = c.dataset.name.toLowerCase().includes(needle) ? '' : 'none';
  });
}

// Modals
function showModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.style.display = 'flex';
    const focusable = el.querySelector('input[autofocus], input');
    if (focusable) setTimeout(() => focusable.focus(), 30);
  }
}

function hideModals() {
  document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
}

function showDropModal(name) {
  if (PROTECTED_DB.includes(name)) {
    alert('Database default tidak bisa dihapus.');
    return;
  }
  document.getElementById('drop-name').textContent = name;
  document.getElementById('drop-input').value = name;
  showModal('modal-drop');
}

function showRenameModal(name) {
  if (PROTECTED_DB.includes(name)) {
    alert('Database default tidak bisa diubah namanya.');
    return;
  }
  document.getElementById('rename-old').value = name;
  const input = document.getElementById('rename-new');
  input.value = name;
  showModal('modal-rename');
  setTimeout(() => input.select(), 40);
}

function showRenameTableModal(name) {
  document.getElementById('rename-table-old').value = name;
  const input = document.getElementById('rename-table-new');
  input.value = name;
  showModal('modal-rename-table');
  setTimeout(() => input.select(), 40);
}

function showDropTableModal(name) {
  document.getElementById('drop-table-name').textContent = name;
  document.getElementById('drop-table-input').value = name;
  showModal('modal-drop-table');
}

document.addEventListener('click', function (e) {
  document.querySelectorAll('.modal-overlay').forEach(m => {
    if (e.target === m) hideModals();
  });
});

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') hideModals();
});

// Create table — dynamic columns
const COLUMN_TYPES = ['SERIAL', 'INT', 'BIGINT', 'VARCHAR(255)', 'TEXT', 'BOOLEAN', 'TIMESTAMP', 'DATE', 'NUMERIC', 'JSONB', 'UUID', 'FLOAT'];
let colIndex = 1;

function addCol() {
  const i = colIndex++;
  const d = document.createElement('div');
  d.className = 'col-row';
  d.innerHTML = `
    <input type="text" name="columns[${i}][name]" placeholder="kolom_${i}">
    <select name="columns[${i}][type]">${COLUMN_TYPES.map(t => `<option>${t}</option>`).join('')}</select>
    <div class="col-checkbox"><input type="checkbox" name="columns[${i}][primary]"></div>
    <div class="col-checkbox"><input type="checkbox" name="columns[${i}][nullable]"></div>
    <div class="col-checkbox"><input type="checkbox" name="columns[${i}][auto]"></div>
    <button type="button" class="btn-danger" onclick="removeCol(this)">✕</button>`;
  document.getElementById('col-list').appendChild(d);
}

function removeCol(btn) {
  if (document.querySelectorAll('.col-row').length <= 1) return;
  btn.closest('.col-row').remove();
}

// ─── Row CRUD (table view) ───
function fillPkHolder(holderId, row) {
  const holder = document.getElementById(holderId);
  holder.innerHTML = '';
  (window.LB_PKS || []).forEach(pk => {
    const inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = '__pk[' + pk + ']';
    inp.value = (row[pk] === null || row[pk] === undefined) ? '' : row[pk];
    holder.appendChild(inp);
  });
}

function addRow() {
  const form = document.getElementById('row-form');
  if (!form) return;
  form.action = '?action=insert_row';
  document.getElementById('row-modal-title').textContent = 'Tambah Baris';
  document.getElementById('row-pk-holder').innerHTML = '';
  form.querySelectorAll('input[data-col]').forEach(i => i.value = '');
  showModal('modal-row');
}

function editRow(i) {
  const form = document.getElementById('row-form');
  if (!form) return;
  const row = window.LB_ROWS[i];
  form.action = '?action=update_row';
  document.getElementById('row-modal-title').textContent = 'Edit Baris';
  form.querySelectorAll('input[data-col]').forEach(inp => {
    const v = row[inp.dataset.col];
    inp.value = (v === null || v === undefined) ? '' : v;
  });
  fillPkHolder('row-pk-holder', row);
  showModal('modal-row');
}

function deleteRow(i) {
  if (!confirm('Hapus baris ini? Tindakan tidak bisa dibatalkan.')) return;
  fillPkHolder('delete-pk-holder', window.LB_ROWS[i]);
  document.getElementById('delete-row-form').submit();
}

// SQL Editor: Ctrl/Cmd + Enter to run
document.addEventListener('keydown', function (e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
    const f = document.querySelector('.sql-editor')?.closest('form');
    if (f) f.submit();
  }
});

// Login: sync default port with driver
function updatePort(d) {
  const port = document.getElementById('port');
  const dbname = document.getElementById('dbname');
  if (port) port.value = d === 'pgsql' ? '5432' : '3306';
  if (dbname) dbname.placeholder = d === 'pgsql' ? 'postgres' : '';
}
