<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

startSession();
if (currentUser()) { header('Location: dashboard.php'); exit; }

$token = trim($_GET['token'] ?? '');
$ok    = '';
$err   = '';

// Verify token exists and is valid
$db   = getDB();
$stmt = $db->prepare("SELECT id, full_name FROM users WHERE reset_token=? AND reset_expires > ?");
$stmt->execute([$token, time()]);
$targetUser = $stmt->fetch();

if (!$token || !$targetUser) {
    $err = 'Nollauslinkki on vanhentunut tai virheellinen. Pyydä uusi linkki.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $targetUser) {
    $pass  = $_POST['new_password']  ?? '';
    $pass2 = $_POST['new_password2'] ?? '';
    if (strlen($pass) < 6) {
        $err = 'Salasanan on oltava vähintään 6 merkkiä.';
    } elseif ($pass !== $pass2) {
        $err = 'Salasanat eivät täsmää.';
    } else {
        if (resetPasswordWithToken($token, $pass)) {
            $ok = 'Salasana vaihdettu! Voit nyt kirjautua sisään.';
            $targetUser = null; // hide form
        } else {
            $err = 'Nollaus epäonnistui — linkki on saattanut vanhentua.';
        }
    }
}

htmlHead('Uusi salasana');
?>
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}
.login-card{background:white;border-radius:28px;padding:48px 40px;width:100%;max-width:420px;box-shadow:0 8px 40px rgba(45,106,79,.22);position:relative;z-index:1;animation:slideUp .5s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
</style>

<div class="login-card">
  <div style="text-align:center;margin-bottom:28px">
    <span style="font-size:48px">🔐</span>
    <h1 style="font-family:'Nunito',sans-serif;font-size:22px;font-weight:900;color:var(--forest-dark);margin-top:8px">Aseta uusi salasana</h1>
    <?php if ($targetUser): ?>
    <p style="color:var(--text-soft);font-size:14px;margin-top:4px">Käyttäjä: <strong><?= htmlspecialchars($targetUser['full_name']) ?></strong></p>
    <?php endif; ?>
  </div>

  <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok):  ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <?php if ($targetUser): ?>
  <form method="POST">
    <div class="form-group">
      <label>Uusi salasana (min. 6 merkkiä)</label>
      <input type="password" name="new_password" required autofocus minlength="6" autocomplete="new-password">
    </div>
    <div class="form-group">
      <label>Uusi salasana uudelleen</label>
      <input type="password" name="new_password2" required minlength="6" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary btn-full">🔐 Aseta uusi salasana</button>
  </form>
  <?php endif; ?>

  <?php if ($ok || $err): ?>
  <div style="text-align:center;margin-top:16px">
    <a href="index.php" class="btn btn-secondary">← Kirjautumissivulle</a>
  </div>
  <?php endif; ?>
</div>

<?php htmlFoot(); ?>
