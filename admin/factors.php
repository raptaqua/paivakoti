<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = requireAdmin();
$db   = getDB();

$err = '';
$ok  = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/** Validate an age group form. Returns [label, min, max|null, factor] or an error string. */
function readAgeGroup(PDO $db, int $ignoreId): array|string {
    $min    = filter_var($_POST['min_age'] ?? '', FILTER_VALIDATE_INT);
    $maxRaw = trim($_POST['max_age'] ?? '');
    $max    = $maxRaw === '' ? null : filter_var($maxRaw, FILTER_VALIDATE_INT);
    $factor = filter_var(str_replace(',', '.', trim($_POST['factor'] ?? '')), FILTER_VALIDATE_FLOAT);

    if ($min === false || $min < 0 || $min > 18)            return 'Alaikä on oltava luku 0–18.';
    if ($max === false || ($max !== null && ($max < $min || $max > 18))) return 'Yläikä on oltava luku alaiän ja 18 välillä (tai tyhjä = ei ylärajaa).';
    if ($factor === false || $factor < 0.1 || $factor > 10)  return 'Kertoimen on oltava luku 0,1–10.';

    // Age ranges must not overlap
    $stmt = $db->prepare("SELECT label FROM age_groups WHERE id != ? AND min_age <= ? AND (max_age IS NULL OR max_age >= ?)");
    $stmt->execute([$ignoreId, $max ?? PHP_INT_MAX, $min]);
    if ($other = $stmt->fetchColumn()) return "Ikäväli menee päällekkäin ikäluokan \"$other\" kanssa.";

    $label = trim($_POST['label'] ?? '');
    if ($label === '') {
        $label = $max === null ? "$min-vuotiaat ja vanhemmat" : ($min === $max ? "$min-vuotiaat" : "$min–$max-vuotiaat");
    }
    return [mb_substr($label, 0, 40), $min, $max, (float)$factor];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $err = 'Virheellinen turvatunniste (CSRF). Yritä uudelleen.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int)($_POST['id'] ?? 0);

        if ($action === 'add' || $action === 'edit') {
            $r = readAgeGroup($db, $action === 'edit' ? $id : 0);
            if (is_string($r)) {
                $err = $r;
            } elseif ($action === 'add') {
                $db->prepare("INSERT INTO age_groups (label, min_age, max_age, factor) VALUES (?,?,?,?)")->execute($r);
                $ok = 'Ikäluokka lisätty!';
            } else {
                $db->prepare("UPDATE age_groups SET label=?, min_age=?, max_age=?, factor=? WHERE id=?")->execute([...$r, $id]);
                $ok = 'Ikäluokka päivitetty!';
            }
        } elseif ($action === 'delete') {
            $db->prepare("DELETE FROM age_groups WHERE id=?")->execute([$id]);
            $ok = 'Ikäluokka poistettu.';
        }
    }
}

$ageGroups = getAgeGroups();

// Ages (0–7) not covered by any age group
$uncovered = [];
foreach (range(0, 7) as $a) {
    $covered = false;
    foreach ($ageGroups as $g) {
        if ($a >= $g['min_age'] && ($g['max_age'] === null || $a <= $g['max_age'])) { $covered = true; break; }
    }
    if (!$covered) $uncovered[] = $a;
}

htmlHead('Kertoimet');
?>
<?php topbar($user, 'factors'); ?>

<div class="page">

<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<div class="card">
  <div class="card-title">⚖️ Ikäluokkien kertoimet</div>
  <p style="font-size:14px;font-weight:600;color:var(--text-soft);margin-bottom:14px">
    Kerroin määrää, kuinka monta “yksikköä” lapsi laskee suhdelukuun. Tarvittavien aikuisten määrä = yhteissuhde ÷ 7 (ylöspäin pyöristettynä).
  </p>
  <?php if ($uncovered): ?>
  <div class="alert alert-info">Iät <?= implode(', ', $uncovered) ?> eivät kuulu mihinkään ikäluokkaan, ja niiden kerroin on 1,0.</div>
  <?php endif; ?>

  <?php foreach ($ageGroups as $g): ?>
  <form method="POST" style="border-bottom:1px solid #f0f0f0;padding:12px 0">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="id" value="<?= $g['id'] ?>">
    <div class="form-row">
      <div class="form-group" style="flex:2;min-width:160px">
        <label>Nimi</label>
        <input type="text" name="label" value="<?= htmlspecialchars($g['label']) ?>" maxlength="40">
      </div>
      <div class="form-group" style="max-width:100px;min-width:90px">
        <label>Iästä</label>
        <input type="number" name="min_age" value="<?= $g['min_age'] ?>" min="0" max="18" required>
      </div>
      <div class="form-group" style="max-width:100px;min-width:90px">
        <label>Ikään</label>
        <input type="number" name="max_age" value="<?= $g['max_age'] ?>" min="0" max="18" placeholder="∞">
      </div>
      <div class="form-group" style="max-width:110px;min-width:90px">
        <label>Kerroin</label>
        <input type="text" inputmode="decimal" name="factor" value="<?= formatFactor((float)$g['factor']) ?>" required>
      </div>
    </div>
    <button type="submit" name="action" value="edit" class="btn btn-primary btn-sm">💾 Tallenna</button>
    <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm"
            onclick="return confirm('Poistetaanko ikäluokka?')" formnovalidate>🗑️ Poista</button>
  </form>
  <?php endforeach; ?>
  <?php if (!$ageGroups): ?><p style="font-weight:600">Ei ikäluokkia. Kaikkien lasten kerroin on 1,0.</p><?php endif; ?>
</div>

<div class="card">
  <div class="card-title">➕ Lisää ikäluokka</div>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="action" value="add">
    <div class="form-row">
      <div class="form-group" style="flex:2;min-width:160px">
        <label>Nimi (valinnainen)</label>
        <input type="text" name="label" maxlength="40" placeholder="esim. 1–2-vuotiaat">
      </div>
      <div class="form-group" style="max-width:100px;min-width:90px">
        <label>Iästä</label>
        <input type="number" name="min_age" min="0" max="18" required>
      </div>
      <div class="form-group" style="max-width:100px;min-width:90px">
        <label>Ikään</label>
        <input type="number" name="max_age" min="0" max="18" placeholder="∞">
      </div>
      <div class="form-group" style="max-width:110px;min-width:90px">
        <label>Kerroin</label>
        <input type="text" inputmode="decimal" name="factor" placeholder="1.5" required>
      </div>
    </div>
    <p style="font-size:12px;color:var(--text-soft);font-weight:600;margin-bottom:12px">Jätä “Ikään” tyhjäksi, jos ikäluokalla ei ole ylärajaa. Ikävälit eivät saa mennä päällekkäin.</p>
    <button type="submit" class="btn btn-primary">➕ Lisää ikäluokka</button>
  </form>
</div>

</div>
<?php htmlFoot(); ?>
