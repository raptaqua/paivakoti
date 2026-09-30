<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = requireAdmin();
$db   = getDB();

$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $username  = trim($_POST['username'] ?? '');
        $fullName  = trim($_POST['full_name'] ?? '');
        $role      = in_array($_POST['role'] ?? '', ['admin','ohjaaja']) ? $_POST['role'] : 'ohjaaja';
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if ($username === '' || $fullName === '' || $password === '') {
            $err = 'Täytä kaikki pakolliset kentät.';
        } elseif (strlen($password) < 6) {
            $err = 'Salasanan on oltava vähintään 6 merkkiä.';
        } elseif ($password !== $password2) {
            $err = 'Salasanat eivät täsmää.';
        } else {
            // Check username unique
            $chk = $db->prepare("SELECT id FROM users WHERE username=?");
            $chk->execute([$username]);
            if ($chk->fetch()) {
                $err = 'Käyttäjätunnus on jo käytössä.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?,?,?,?)")
                   ->execute([$username, $hash, $fullName, $role]);
                $ok = "Käyttäjä \"$username\" luotu!";
            }
        }
    } elseif ($action === 'edit_user') {
        $uid      = (int)$_POST['user_id'];
        $fullName = trim($_POST['full_name'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['admin','ohjaaja']) ? $_POST['role'] : 'ohjaaja';
        $active   = isset($_POST['active']) ? 1 : 0;

        // Prevent demoting yourself
        if ($uid === $user['id'] && $role !== 'admin') {
            $err = 'Et voi muuttaa omia admin-oikeuksiasi.';
        } elseif ($uid === $user['id'] && !$active) {
            $err = 'Et voi poistaa omaa tiliäsi käytöstä.';
        } else {
            $db->prepare("UPDATE users SET full_name=?, role=?, active=? WHERE id=?")
               ->execute([$fullName, $role, $active, $uid]);
            $ok = 'Käyttäjän tiedot päivitetty.';
        }
    } elseif ($action === 'reset_password_admin') {
        $uid       = (int)$_POST['user_id'];
        $newPass   = $_POST['new_password'] ?? '';
        $newPass2  = $_POST['new_password2'] ?? '';
        if (strlen($newPass) < 6) {
            $err = 'Salasanan on oltava vähintään 6 merkkiä.';
        } elseif ($newPass !== $newPass2) {
            $err = 'Salasanat eivät täsmää.';
        } else {
            changePassword($uid, $newPass);
            $ok = 'Salasana vaihdettu.';
        }
    } elseif ($action === 'delete_user') {
        $uid = (int)$_POST['user_id'];
        if ($uid === $user['id']) {
            $err = 'Et voi poistaa omaa tiliäsi.';
        } else {
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
            $ok = 'Käyttäjä poistettu.';
        }
    }
}

$users = $db->query("SELECT * FROM users ORDER BY role DESC, full_name")->fetchAll();

htmlHead('Käyttäjien hallinta');
?>
<?php topbar($user, 'users'); ?>

<div class="page">

<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<!-- ADD USER -->
<div class="card">
  <div class="card-title">👤 Lisää uusi käyttäjä</div>
  <form method="POST">
    <input type="hidden" name="action" value="add_user">
    <div class="form-row">
      <div class="form-group">
        <label>Koko nimi *</label>
        <input type="text" name="full_name" placeholder="Maija Meikäläinen" required maxlength="80">
      </div>
      <div class="form-group">
        <label>Käyttäjätunnus *</label>
        <input type="text" name="username" placeholder="maijam" required maxlength="40" autocomplete="off">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Salasana * (min. 6 merkkiä)</label>
        <input type="password" name="password" required autocomplete="new-password">
      </div>
      <div class="form-group">
        <label>Salasana uudelleen *</label>
        <input type="password" name="password2" required autocomplete="new-password">
      </div>
    </div>
    <div class="form-group" style="max-width:200px">
      <label>Rooli</label>
      <select name="role">
        <option value="ohjaaja">👷 Ohjaaja</option>
        <option value="admin">🔑 Admin</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">➕ Luo käyttäjä</button>
  </form>
</div>

<!-- USER LIST -->
<div class="card">
  <div class="card-title">👥 Kaikki käyttäjät</div>
  <?php foreach ($users as $u): ?>
  <div class="user-row">
    <div class="user-avatar"><?= mb_substr($u['full_name'],0,1) ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($u['full_name']) ?>
        <?php if (!$u['active']): ?><span class="badge badge-inactive">Ei aktiivinen</span><?php endif; ?>
      </div>
      <div class="user-sub">@<?= htmlspecialchars($u['username']) ?> &middot;
        <span class="badge <?= $u['role']==='admin' ? 'badge-admin' : 'badge-ohjaaja' ?>"><?= $u['role'] ?></span>
      </div>
    </div>

    <!-- Edit form inline via details -->
    <details style="flex:1;min-width:240px">
      <summary class="btn btn-secondary btn-sm" style="list-style:none;cursor:pointer">✏️ Muokkaa</summary>
      <div style="margin-top:12px;padding:14px;background:var(--mist);border-radius:12px">
        <form method="POST" style="margin-bottom:12px">
          <input type="hidden" name="action" value="edit_user">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <div class="form-row">
            <div class="form-group">
              <label>Koko nimi</label>
              <input type="text" name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" required>
            </div>
            <div class="form-group" style="max-width:140px">
              <label>Rooli</label>
              <select name="role">
                <option value="ohjaaja" <?= $u['role']==='ohjaaja'?'selected':'' ?>>Ohjaaja</option>
                <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
              </select>
            </div>
          </div>
          <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:13px;margin-bottom:10px;cursor:pointer">
            <input type="checkbox" name="active" <?= $u['active']?'checked':'' ?> style="width:16px;height:16px">
            Tili aktiivinen
          </label>
          <button type="submit" class="btn btn-primary btn-sm">💾 Tallenna muutokset</button>
        </form>

        <!-- Password reset by admin -->
        <hr style="border:none;border-top:1px solid #dde;margin:10px 0">
        <div style="font-weight:700;font-size:13px;color:var(--forest-dark);margin-bottom:8px">🔑 Vaihda salasana</div>
        <form method="POST">
          <input type="hidden" name="action" value="reset_password_admin">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <div class="form-row">
            <div class="form-group">
              <input type="password" name="new_password" placeholder="Uusi salasana" autocomplete="new-password" minlength="6">
            </div>
            <div class="form-group">
              <input type="password" name="new_password2" placeholder="Uudelleen" autocomplete="new-password">
            </div>
          </div>
          <button type="submit" class="btn btn-warning btn-sm">🔑 Vaihda salasana</button>
        </form>

        <!-- Delete -->
        <?php if ($u['id'] !== $user['id']): ?>
        <hr style="border:none;border-top:1px solid #dde;margin:10px 0">
        <form method="POST" onsubmit="return confirm('Poistetaanko käyttäjä pysyvästi?')">
          <input type="hidden" name="action" value="delete_user">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm">🗑️ Poista käyttäjä</button>
        </form>
        <?php endif; ?>
      </div>
    </details>
  </div>
  <?php endforeach; ?>
</div>

<!-- ROLE INFO -->
<div class="card">
  <div class="card-title">ℹ️ Roolien oikeudet</div>
  <table>
    <thead><tr><th>Toiminto</th><th>Ohjaaja</th><th>Admin</th></tr></thead>
    <tbody>
      <tr><td>Nähdä ryhmät ja lapset</td><td>✅</td><td>✅</td></tr>
      <tr><td>Merkitä poissaolot</td><td>✅</td><td>✅</td></tr>
      <tr><td>Toistaiseksi-poissaolot</td><td>✅</td><td>✅</td></tr>
      <tr><td>Muokata lasten tietoja</td><td>❌</td><td>✅</td></tr>
      <tr><td>Lisätä/poistaa lapsia</td><td>❌</td><td>✅</td></tr>
      <tr><td>Hallita ryhmiä</td><td>❌</td><td>✅</td></tr>
      <tr><td>Hallita käyttäjiä</td><td>❌</td><td>✅</td></tr>
      <tr><td>Nollata päivän poissaolot</td><td>❌</td><td>✅</td></tr>
    </tbody>
  </table>
</div>

</div>

<style>
details summary::-webkit-details-marker{display:none}
details[open] summary{background:var(--forest);color:white}
</style>

<?php htmlFoot(); ?>
