<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

startSession();
if (currentUser()) { header('Location: dashboard.php'); exit; }

$ok  = '';
$err = '';
$token = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    if ($username === '') {
        $err = 'Syötä käyttäjätunnus.';
    } else {
        $token = generateResetToken($username);
        if ($token) {
            // In a real system you'd email this link.
            // Here we show it directly (intranet use).
            $ok = $token;
        } else {
            $err = 'Käyttäjätunnusta ei löydy tai tili ei ole aktiivinen.';
        }
    }
}

htmlHead('Salasanan nollaus');
?>
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}
.login-card{background:white;border-radius:28px;padding:48px 40px;width:100%;max-width:440px;box-shadow:0 8px 40px rgba(45,106,79,.22);position:relative;z-index:1;animation:slideUp .5s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.back-link{display:block;text-align:center;margin-top:14px;font-size:13px;color:var(--forest);text-decoration:none;font-weight:600}
.back-link:hover{text-decoration:underline}
.token-box{background:var(--mist);border:2px solid var(--leaf-dark);border-radius:10px;padding:14px;margin:12px 0;word-break:break-all;font-family:monospace;font-size:13px;color:var(--forest-dark)}
</style>

<div class="login-card">
  <div style="text-align:center;margin-bottom:28px">
    <span style="font-size:48px">🔑</span>
    <h1 style="font-family:'Nunito',sans-serif;font-size:22px;font-weight:900;color:var(--forest-dark);margin-top:8px">Salasanan nollaus</h1>
  </div>

  <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <?php if ($ok): ?>
  <div class="alert alert-success">✅ Nollauslinkki luotu (voimassa 1 tunnin).</div>
  <p style="font-size:13px;color:var(--text-soft);margin-bottom:8px">
    Kopioi alla oleva linkki ja avaa se selaimessa:<br>
    <strong style="color:var(--forest-dark)">(Normaalisti tämä lähetettäisiin sähköpostilla — intranet-käytössä näytetään suoraan)</strong>
  </p>
  <div class="token-box">
    <?php
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'];
    $base  = dirname($_SERVER['REQUEST_URI']);
    $link  = $proto . '://' . $host . rtrim($base, '/') . '/reset_password.php?token=' . urlencode($ok);
    echo htmlspecialchars($link);
    ?>
  </div>
  <a href="<?= htmlspecialchars($link) ?>" class="btn btn-primary btn-full" style="margin-top:8px">→ Avaa nollauslinkki</a>

  <?php else: ?>
  <form method="POST">
    <div class="form-group">
      <label>Käyttäjätunnus</label>
      <input type="text" name="username" autofocus placeholder="käyttäjätunnus" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-full">Lähetä nollauslinkki</button>
  </form>
  <?php endif; ?>

  <a href="index.php" class="back-link">← Takaisin kirjautumiseen</a>
</div>

<?php htmlFoot(); ?>
