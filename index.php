<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

startSession();
if (currentUser()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (login($user, $pass)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Väärä käyttäjätunnus tai salasana.';
}

htmlHead('Kirjaudu');
?>
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}
.login-card{background:white;border-radius:28px;padding:48px 40px;width:100%;max-width:420px;box-shadow:0 8px 40px rgba(45,106,79,.22);position:relative;z-index:1;animation:slideUp .5s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.login-logo{text-align:center;margin-bottom:36px}
.login-logo .big-emoji{font-size:64px;line-height:1;display:block;margin-bottom:12px}
.login-logo h1{font-family:'Nunito',sans-serif;font-size:26px;font-weight:900;color:var(--forest-dark)}
.login-logo p{color:var(--text-soft);font-size:14px;margin-top:4px}
.forgot-link{display:block;text-align:center;margin-top:14px;font-size:13px;color:var(--forest);text-decoration:none;font-weight:600}
.forgot-link:hover{text-decoration:underline}
</style>

<div class="login-card">
  <div class="login-logo">
    <span class="big-emoji">🌳</span>
    <h1>Päiväkoti</h1>
    <p>Suhdelaskuri &amp; Hallinta</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Käyttäjätunnus</label>
      <input type="text" name="username" autofocus autocomplete="username" placeholder="käyttäjätunnus"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Salasana</label>
      <input type="password" name="password" autocomplete="current-password" placeholder="••••••••">
    </div>
    <button type="submit" class="btn btn-primary btn-full">Kirjaudu sisään →</button>
  </form>

  <?php installButton(true); ?>

  <a href="help.php" class="forgot-link" style="display:block;margin-bottom:8px">❓ Ohje</a>
  <a href="reset_request.php" class="forgot-link">🔑 Unohditko salasanan?</a>
</div>

<?php htmlFoot(); ?>
