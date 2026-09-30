<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = requireAdmin();
$db   = getDB();

$err = '';
$ok  = '';

// CSRF-tokenin luonti (olettaen että auth.php on käynnistänyt session)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-tarkistus
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $err = 'Virheellinen turvatunniste (CSRF). Yritä uudelleen.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_group') {
            $name  = trim($_POST['name'] ?? '');
            $emoji = trim($_POST['emoji'] ?? '🐦');
            if ($name === '') { $err = 'Ryhmän nimi ei voi olla tyhjä.'; }
            else {
                $max = $db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM groups_table")->fetchColumn();
                $db->prepare("INSERT INTO groups_table (name, emoji, sort_order) VALUES (?,?,?)")->execute([$name, $emoji, $max]);
                $ok = "Ryhmä \"$name\" lisätty!";
            }
        } elseif ($action === 'edit_group') {
            $id    = (int)$_POST['group_id'];
            $name  = trim($_POST['name'] ?? '');
            $emoji = trim($_POST['emoji'] ?? '🐦');
            if ($name) {
                $db->prepare("UPDATE groups_table SET name=?, emoji=? WHERE id=?")->execute([$name, $emoji, $id]);
                $ok = 'Ryhmä päivitetty.';
            }
        } elseif ($action === 'delete_group') {
            $id = (int)$_POST['group_id'];
            $cnt = $db->prepare("SELECT COUNT(*) FROM children WHERE group_id=?");
            $cnt->execute([$id]);
            if ($cnt->fetchColumn() > 0) {
                $err = 'Poista ensin kaikki lapset ryhmästä ennen ryhmän poistoa.';
            } else {
                $db->prepare("DELETE FROM groups_table WHERE id=?")->execute([$id]);
                $ok = 'Ryhmä poistettu.';
            }
        } elseif ($action === 'add_child') {
            $gid  = (int)$_POST['group_id'];
            $name = trim($_POST['name'] ?? '');
            $age  = max(0, min(7, (int)($_POST['age'] ?? 3)));
            if ($name) {
                $db->prepare("INSERT INTO children (group_id, name, age) VALUES (?,?,?)")->execute([$gid, $name, $age]);
                $ok = "Lapsi \"$name\" lisätty!";
            }
        } elseif ($action === 'edit_child') {
            $cid  = (int)$_POST['child_id'];
            $name = trim($_POST['name'] ?? '');
            $age  = max(0, min(7, (int)($_POST['age'] ?? 3)));
            $gid  = (int)$_POST['group_id'];
            if ($name) {
                $db->prepare("UPDATE children SET name=?, age=?, group_id=? WHERE id=?")->execute([$name, $age, $gid, $cid]);
                $ok = 'Lapsen tiedot päivitetty.';
            }
        } elseif ($action === 'delete_child') {
            $cid = (int)$_POST['child_id'];
            $db->prepare("DELETE FROM children WHERE id=?")->execute([$cid]);
            $ok = 'Lapsi poistettu.';
        }
    }
}

// Load data
$groups = $db->query("SELECT * FROM groups_table ORDER BY sort_order, id")->fetchAll();
$groupsWithChildren = [];
foreach ($groups as $g) {
    $stmt = $db->prepare("SELECT * FROM children WHERE group_id=? ORDER BY name");
    $stmt->execute([$g['id']]);
    $groupsWithChildren[] = ['group' => $g, 'children' => $stmt->fetchAll()];
}
$ageGroups = getAgeGroups();
$allGroups = $groups; // for move-child dropdown

$emojis = ['🐦','🐦‍⬛','🐤','🦉','🦜','🦚','🦅','🦋','🌸','🌟','🍀','🌈','🐸','🐝','🦊','🐯'];

htmlHead('Ryhmien hallinta');
?>
<?php topbar($user, 'groups'); ?>

<div class="page">

<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<div class="card">
  <div class="card-title">➕ Lisää uusi ryhmä</div>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="action" value="add_group">
    <div class="form-row">
      <div class="form-group">
        <label>Ryhmän nimi</label>
        <input type="text" name="name" placeholder="esim. Satakielet" required maxlength="40">
      </div>
      <div class="form-group" style="max-width:130px">
        <label>Emoji</label>
        <select name="emoji">
          <?php foreach ($emojis as $e): ?>
          <option value="<?= $e ?>"><?= $e ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary">➕ Lisää ryhmä</button>
  </form>
</div>

<?php foreach ($groupsWithChildren as $gd): $g = $gd['group']; ?>
<div class="card">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap">
    <div style="font-size:28px"><?= htmlspecialchars($g['emoji']) ?></div>
    <form method="POST" style="flex:1;min-width:200px">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" value="edit_group">
      <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
      <div class="form-row" style="margin-bottom:8px">
        <div class="form-group" style="margin-bottom:0">
          <input type="text" name="name" value="<?= htmlspecialchars($g['name']) ?>" required style="font-family:'Nunito',sans-serif;font-weight:900;font-size:17px">
        </div>
        <div class="form-group" style="margin-bottom:0;max-width:110px">
          <select name="emoji">
            <?php foreach ($emojis as $e): ?>
            <option value="<?= $e ?>" <?= $e===$g['emoji']?'selected':'' ?>><?= $e ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-secondary btn-sm" style="align-self:flex-end">💾 Tallenna</button>
      </div>
    </form>
    <form method="POST" onsubmit="return confirm('Poistetaanko ryhmä? Varmista että ryhmässä ei ole lapsia.')">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" value="delete_group">
      <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
      <button type="submit" class="btn btn-danger btn-sm">🗑️ Poista ryhmä</button>
    </form>
  </div>

  <hr style="border:none;border-top:1px solid #eee;margin-bottom:14px">

  <?php if (empty($gd['children'])): ?>
  <p style="color:var(--text-soft);font-size:14px;margin-bottom:12px">Ei lapsia tässä ryhmässä.</p>
  <?php else: ?>
  <div class="table-wrap" style="margin-bottom:14px">
    <table>
      <thead><tr><th>Nimi</th><th>Ikä</th><th>Kerroin</th><th>Ryhmä</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($gd['children'] as $c): ?>
      <tr>
        <td>
          <form id="edit_child_<?= $c['id'] ?>" method="POST" style="display:none;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="edit_child">
            <input type="hidden" name="child_id" value="<?= $c['id'] ?>">
          </form>
          <input form="edit_child_<?= $c['id'] ?>" type="text" name="name" value="<?= htmlspecialchars($c['name']) ?>" class="admin-inline-input" required style="min-width: 140px;">
        </td>
        <td>
          <input form="edit_child_<?= $c['id'] ?>" type="number" name="age" value="<?= $c['age'] ?>" min="0" max="7" class="admin-inline-input" style="width:60px">
        </td>
        <td style="font-weight:700;color:var(--forest)"><?= formatFactor(ageFactor((int)$c['age'], $ageGroups)) ?></td>
        <td>
          <select form="edit_child_<?= $c['id'] ?>" name="group_id" class="admin-inline-input" style="width:120px">
            <?php foreach ($allGroups as $ag): ?>
            <option value="<?= $ag['id'] ?>" <?= $ag['id']==$g['id']?'selected':'' ?>><?= htmlspecialchars($ag['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td style="white-space:nowrap">
          <button form="edit_child_<?= $c['id'] ?>" type="submit" class="btn btn-secondary btn-sm">💾</button>
          
          <form method="POST" style="display:inline" onsubmit="return confirm('Poistetaanko lapsi?')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="delete_child">
            <input type="hidden" name="child_id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">✕</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <details style="margin-top:8px">
    <summary style="cursor:pointer;font-weight:700;color:var(--forest);font-size:14px;padding:6px 0">➕ Lisää lapsi ryhmään <?= htmlspecialchars($g['name']) ?></summary>
    <form method="POST" style="margin-top:12px">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" value="add_child">
      <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
      <div class="form-row">
        <div class="form-group">
          <label>Lapsen nimi</label>
          <input type="text" name="name" placeholder="Etunimi Sukunimi" required maxlength="60">
        </div>
        <div class="form-group" style="max-width:100px">
          <label>Ikä (v)</label>
          <input type="number" name="age" value="3" min="0" max="7" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">➕ Lisää lapsi</button>
    </form>
  </details>
</div>
<?php endforeach; ?>

</div>

<style>
.admin-inline-input{padding:6px 10px;border:2px solid #e0ece7;border-radius:7px;font-family:'Quicksand',sans-serif;font-size:13px;font-weight:600;background:var(--mist);outline:none;width:100%}
.admin-inline-input:focus{border-color:var(--forest-light);background:white}
details summary::-webkit-details-marker{display:none}

/* Mobiili-taulukon korjaukset */
.table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  width: 100%;
}
.table-wrap table {
  width: 100%;
  min-width: 550px;
  border-collapse: collapse;
}
</style>

<?php htmlFoot(); ?>