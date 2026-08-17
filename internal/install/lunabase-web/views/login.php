<!DOCTYPE html>
<html lang="id" data-theme="dark">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LunaBase — Masuk</title>
  <link rel="icon" type="image/svg+xml" href="assets/logo.svg">
  <link rel="stylesheet" href="assets/style.css?v=<?= LUNABASE_VERSION ?>">
</head>

<body>
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-logo">
        <div class="moon"><img src="assets/logo.svg" alt="Luna"></div>
        <h1>Luna<span>Base</span></h1>
      </div>
      <p class="login-subtitle">Koneksi ke database lokal kamu</p>
      <?php if ($error): ?><div class="error-box">⚠ <?= h($error) ?></div><?php endif; ?>
      <form method="POST" action="?action=login">
        <div class="field">
          <label>Driver</label>
          <select name="driver" id="drv" onchange="updatePort(this.value)">
            <option value="pgsql">PostgreSQL</option>
            <option value="mysql">MySQL / MariaDB</option>
          </select>
        </div>
        <div class="field field-row">
          <div><label>Host</label><input type="text" name="host" value="localhost"></div>
          <div><label>Port</label><input type="text" name="port" id="port" value="5432"></div>
        </div>
        <div class="field"><label>Username</label><input type="text" name="user" value="<?= h($_SERVER['USER'] ?? '') ?>"></div>
        <div class="field"><label>Password</label><input type="password" name="password"></div>
        <div class="field">
          <label>Database <span style="color:var(--text3);font-size:10px;text-transform:none;letter-spacing:0">(kosongkan = default, atau ketik nama baru)</span></label>
          <input type="text" name="dbname" id="dbname" placeholder="postgres">
        </div>
        <button type="submit" class="btn btn-primary">Masuk →</button>
      </form>
    </div>
  </div>
  <script src="assets/app.js?v=<?= LUNABASE_VERSION ?>"></script>
</body>

</html>
