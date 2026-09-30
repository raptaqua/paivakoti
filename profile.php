<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireLogin();
$db   = getDB();

$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $newPass2    = $_POST['new_password2'] ?? '';

    // Verify current password
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id=?");
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();

    if (!password_verify($currentPass, $row['password_hash'])) {
        $err = 'Nykyinen salasana on väärä.';
    } elseif (strlen($newPass) < 6) {
        $err = 'Uuden salasanan on oltava vähintään 6 merkkiä.';
    } elseif ($newPass !== $newPass2) {
        $err = 'Uudet salasanat eivät täsmää.';
    } else {
        changePassword($user['id'], $newPass);
        $ok = 'Salasana vaihdettu onnistuneesti!';
    }
}

htmlHead('Profiili');
?>
<?php topbar($user, 'profile'); ?>

<div class="page" style="max-width:520px">

  <div class="card" style="text-align:center;padding:32px">
    <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--forest-light),var(--forest-dark));color:white;display:flex;align-items:center;justify-content:center;font-family:'Nunito',sans-serif;font-size:30px;font-weight:900;margin:0 auto 14px">
      <?= mb_substr($user['full_name'],0,1) ?>
    </div>
    <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:22px;color:var(--forest-dark)"><?= htmlspecialchars($user['full_name']) ?></div>
    <div style="color:var(--text-soft);font-size:14px;margin-top:4px">@<?= htmlspecialchars($user['username']) ?></div>
    <div style="margin-top:10px">
      <span class="badge <?= $user['role']==='admin'?'badge-admin':'badge-ohjaaja' ?>"><?= $user['role'] === 'admin' ? '🔑 Admin' : '👷 Ohjaaja' ?></span>
    </div>
  </div>

  <div class="card">
    <div class="card-title">🔑 Vaihda salasana</div>

    <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($ok):  ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Nykyinen salasana</label>
        <input type="password" name="current_password" required autocomplete="current-password">
      </div>
      <div class="form-group">
        <label>Uusi salasana (min. 6 merkkiä)</label>
        <input type="password" name="new_password" required autocomplete="new-password" minlength="6">
      </div>
      <div class="form-group">
        <label>Uusi salasana uudelleen</label>
        <input type="password" name="new_password2" required autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary">🔑 Vaihda salasana</button>
    </form>
  </div>

</div>

<?php htmlFoot(); ?>
