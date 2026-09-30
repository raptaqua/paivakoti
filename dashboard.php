<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireLogin();
$db   = getDB();

$today = date('Y-m-d');

// Handle absence toggle (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $childId = (int)($_POST['child_id'] ?? 0);
    $action  = $_POST['action'];

    if ($action === 'toggle_today') {
        // Check if already absent today
        $exists = $db->prepare("SELECT 1 FROM daily_absence WHERE child_id=? AND absence_date=?");
        $exists->execute([$childId, $today]);
        if ($exists->fetchColumn()) {
            $db->prepare("DELETE FROM daily_absence WHERE child_id=? AND absence_date=?")->execute([$childId, $today]);
        } else {
            $db->prepare("INSERT OR IGNORE INTO daily_absence (child_id, absence_date) VALUES (?,?)")->execute([$childId, $today]);
        }
    } elseif ($action === 'toggle_long') {
        $c = $db->prepare("SELECT long_absent FROM children WHERE id=?");
        $c->execute([$childId]);
        $row = $c->fetch();
        if ($row) {
            $newVal = $row['long_absent'] ? 0 : 1;
            $db->prepare("UPDATE children SET long_absent=? WHERE id=?")->execute([$newVal, $childId]);
            // If setting long absent, also add today's absence
            if ($newVal) {
                $db->prepare("INSERT OR IGNORE INTO daily_absence (child_id, absence_date) VALUES (?,?)")->execute([$childId, $today]);
            }
        }
    } elseif ($action === 'reset_day' && isAdmin()) {
        // Remove today's absences (not long absences)
        $db->prepare("DELETE FROM daily_absence WHERE absence_date=?")->execute([$today]);
    }

    header('Location: dashboard.php');
    exit;
}

// Load groups with children + absence info
$groups = $db->query("SELECT * FROM groups_table ORDER BY sort_order, id")->fetchAll();
$groupData = [];
foreach ($groups as $g) {
    $stmt = $db->prepare("
        SELECT c.*,
               (SELECT 1 FROM daily_absence WHERE child_id=c.id AND absence_date=?) as today_absent
        FROM children c WHERE c.group_id=? ORDER BY c.name
    ");
    $stmt->execute([$today, $g['id']]);
    $children = $stmt->fetchAll();

    $present = 0; $ratioSum = 0.0;
    foreach ($children as $c) {
        $isAbsent = $c['long_absent'] || $c['today_absent'];
        if (!$isAbsent) {
            $present++;
            $ratioSum += $c['age'] < 3 ? 1.75 : 1.0;
        }
    }
    $adults = $ratioSum > 0 ? ceil($ratioSum / 7) : 0;

    $groupData[] = [
        'group'    => $g,
        'children' => $children,
        'present'  => $present,
        'total'    => count($children),
        'ratio'    => $ratioSum,
        'adults'   => $adults,
    ];
}

$totPresent = array_sum(array_column($groupData, 'present'));
$totRatio   = array_sum(array_column($groupData, 'ratio'));
$totAdults  = array_sum(array_column($groupData, 'adults'));

$weekdays = ['Sunnuntai','Maanantai','Tiistai','Keskiviikko','Torstai','Perjantai','Lauantai'];
$months   = ['tammikuuta','helmikuuta','maaliskuuta','huhtikuuta','toukokuuta','kesäkuuta','heinäkuuta','elokuuta','syyskuuta','lokakuuta','marraskuuta','joulukuuta'];
$dateStr  = $weekdays[date('w')] . ' ' . date('j') . '. ' . $months[date('n')-1] . ' ' . date('Y');

htmlHead('Ryhmät');
?>
<?php topbar($user, 'dashboard'); ?>

<div class="page">

  <div class="date-banner">
    <div>
      <div class="date-text">📅 <?= $dateStr ?></div>
      <div class="date-sub">Päivittäinen suhdelaskuri</div>
    </div>
    <?php if (isAdmin()): ?>
    <form method="POST" onsubmit="return confirm('Nollataan päivän poissaolot?')">
      <input type="hidden" name="action" value="reset_day">
      <button type="submit" class="btn btn-warning btn-sm">🔄 Uusi päivä</button>
    </form>
    <?php endif; ?>
  </div>

  <div class="summary-bar">
    <div class="summary-item">
      <span class="summary-number"><?= $totPresent ?></span>
      <div class="summary-label">Lapsia paikalla</div>
    </div>
    <div class="summary-item">
      <span class="summary-number"><?= number_format($totRatio, 1) ?></span>
      <div class="summary-label">Yhteissuhde</div>
    </div>
    <div class="summary-item">
      <span class="summary-number"><?= $totAdults ?></span>
      <div class="summary-label">Aikuisia tarvitaan</div>
    </div>
  </div>

  <?php if (flash('success')): ?>
  <div class="alert alert-success"><?= htmlspecialchars(flash('success')) ?></div>
  <?php endif; ?>

  <?php foreach ($groupData as $gd): $g = $gd['group']; ?>
  <div class="group-card expanded" id="gc-<?= $g['id'] ?>">
    <div class="group-header" onclick="toggleGroup(<?= $g['id'] ?>)">
      <div class="group-title">
        <span class="group-emoji"><?= htmlspecialchars($g['emoji']) ?></span>
        <div>
          <div class="group-name-text"><?= htmlspecialchars($g['name']) ?></div>
          <div class="group-count"><?= $gd['present'] ?>/<?= $gd['total'] ?> paikalla</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <div class="ratio-badge">
          <div class="ratio-num"><?= number_format($gd['ratio'], 1) ?></div>
          <div class="ratio-lbl">suhdeluku</div>
        </div>
        <div class="ratio-adults">
          <div class="ratio-num"><?= $gd['adults'] ?></div>
          <div class="ratio-lbl">aikuista</div>
        </div>
        <span class="expand-arrow">▾</span>
      </div>
    </div>

    <div class="children-list" id="cl-<?= $g['id'] ?>">
      <?php if (empty($gd['children'])): ?>
        <div style="text-align:center;padding:24px;color:var(--text-soft);font-size:14px">
          👶 Ei lapsia tässä ryhmässä
        </div>
      <?php else: foreach ($gd['children'] as $c):
        $isAbsent = $c['long_absent'] || $c['today_absent'];
        $isYoung  = $c['age'] < 3;
      ?>
      <div class="child-row">
        <div class="child-avatar <?= $isAbsent ? 'absent' : '' ?>"><?= mb_substr($c['name'],0,1) ?></div>
        <div style="flex:1;min-width:0">
          <div class="child-name <?= $isAbsent ? 'absent' : '' ?>"><?= htmlspecialchars($c['name']) ?></div>
          <div class="child-meta">
            <span class="age-badge <?= $isYoung ? 'young' : '' ?>"><?= $c['age'] ?>v · <?= $isYoung ? '1.75' : '1.0' ?></span>
            <?php if ($c['long_absent']): ?><span class="absence-lbl">Poissa toistaiseksi</span><?php endif; ?>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
          <!-- Today absence toggle -->
          <?php if (!$c['long_absent']): ?>
          <form method="POST" style="margin:0">
            <input type="hidden" name="action" value="toggle_today">
            <input type="hidden" name="child_id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $c['today_absent'] ? 'btn-danger' : 'btn-secondary' ?>">
              <?= $c['today_absent'] ? '✓ Poissa tänään' : 'Merkitse poissa' ?>
            </button>
          </form>
          <?php endif; ?>
          <!-- Long absence toggle -->
          <form method="POST" style="margin:0">
            <input type="hidden" name="action" value="toggle_long">
            <input type="hidden" name="child_id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $c['long_absent'] ? 'btn-danger' : '' ?>"
              style="<?= !$c['long_absent'] ? 'background:none;border:none;color:var(--text-soft);text-decoration:underline;padding:2px 4px;font-size:11px' : '' ?>">
              <?= $c['long_absent'] ? '↩ Peruuta toistaiseksi' : 'Toistaiseksi' ?>
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

</div>

<script>
function toggleGroup(id) {
  const card = document.getElementById('gc-'+id);
  const list = document.getElementById('cl-'+id);
  const expanded = card.classList.toggle('expanded');
  list.style.display = expanded ? 'block' : 'none';
}
// Keep all expanded on load
document.querySelectorAll('.children-list').forEach(el => el.style.display = 'block');
</script>

<?php htmlFoot(); ?>
