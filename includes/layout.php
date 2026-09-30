<?php
/**
 * layout.php — Shared HTML head, topbar, footer
 */

function htmlHead(string $title = 'Päiväkoti'): void { ?>
<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars($title) ?> — Päiväkoti</title>
<?php
    $docRoot = rtrim(str_replace('\\','/',realpath($_SERVER['DOCUMENT_ROOT'])), '/');
    $appRoot = rtrim(str_replace('\\','/',realpath(__DIR__ . '/../')), '/');
    $pwaBase = str_replace($docRoot, '', $appRoot);
?>
<link rel="manifest" href="<?= $pwaBase ?>/manifest.webmanifest">
<meta name="theme-color" content="#2d6a4f">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Päiväkoti">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="<?= $pwaBase ?>/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" href="<?= $pwaBase ?>/icons/icon-192.png">
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('<?= $pwaBase ?>/sw.js', {scope: '<?= $pwaBase ?>/'}).catch(function () {});
  });
}

(function () {
  var deferred = null;
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
  var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
  function buttons() { return document.querySelectorAll('[data-install]'); }
  function show(on) { buttons().forEach(function (b) { b.hidden = !on; }); }
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault(); deferred = e; show(true);
  });
  window.addEventListener('appinstalled', function () { deferred = null; show(false); });
  document.addEventListener('DOMContentLoaded', function () {
    if (standalone) { show(false); return; }
    if (isIOS) show(true);   // iOS has no install prompt API: show instructions instead
    buttons().forEach(function (b) {
      b.addEventListener('click', function () {
        if (deferred) {
          deferred.prompt();
          deferred.userChoice.then(function () { deferred = null; show(false); });
        } else {
          alert('Asenna sovellus: paina Safarissa Jaa-painiketta (neliö ja nuoli) ja valitse "Lisää Koti-valikkoon".');
        }
      });
    });
  });
})();
</script>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --sky: #e8f4fd;
  --forest: #2d6a4f;
  --forest-light: #40916c;
  --forest-dark: #1b4332;
  --sun: #f4a261;
  --sun-light: #ffd166;
  --berry: #e63946;
  --berry-light: #ff6b6b;
  --snow: #ffffff;
  --mist: #f0f7f4;
  --bark: #6b4f3a;
  --leaf: #95d5b2;
  --leaf-dark: #52b788;
  --purple: #7b5ea7;
  --text: #1a1a2e;
  --text-soft: #5a6472;
  --shadow: rgba(45,106,79,0.12);
  --shadow-deep: rgba(45,106,79,0.22);
  --radius: 18px;
  --radius-sm: 10px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Quicksand',sans-serif;background:var(--sky);color:var(--text);min-height:100vh}
body::before{content:'';position:fixed;top:-120px;right:-120px;width:400px;height:400px;background:radial-gradient(circle,rgba(149,213,178,.35) 0%,transparent 70%);pointer-events:none;z-index:0}
body::after{content:'';position:fixed;bottom:-100px;left:-100px;width:350px;height:350px;background:radial-gradient(circle,rgba(244,162,97,.2) 0%,transparent 70%);pointer-events:none;z-index:0}

/* TOPBAR */
.topbar{background:linear-gradient(135deg,var(--forest-dark),var(--forest));color:white;padding:0 20px;display:flex;align-items:center;justify-content:space-between;height:62px;box-shadow:0 2px 16px var(--shadow-deep);position:sticky;top:0;z-index:100}
.topbar-logo{font-family:'Nunito',sans-serif;font-weight:900;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:white}
.topbar-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.topbar-btn{background:rgba(255,255,255,.15);border:none;color:white;padding:8px 13px;border-radius:8px;font-family:'Quicksand',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:background .2s;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.topbar-btn:hover{background:rgba(255,255,255,.28)}
.topbar-btn.active{background:rgba(255,255,255,.3);outline:2px solid rgba(255,255,255,.4)}
.topbar-user{font-size:12px;opacity:.75;margin-right:4px}

/* MAIN CONTENT */
.page{max-width:820px;margin:0 auto;padding:22px 18px;position:relative;z-index:1}

/* CARDS */
.card{background:var(--snow);border-radius:var(--radius);padding:24px;box-shadow:0 4px 20px var(--shadow);margin-bottom:18px}
.card-title{font-family:'Nunito',sans-serif;font-weight:900;font-size:18px;color:var(--forest-dark);margin-bottom:16px;display:flex;align-items:center;gap:8px}

/* FORMS */
.form-group{margin-bottom:15px}
.form-group label{display:block;font-size:13px;font-weight:700;color:var(--forest-dark);margin-bottom:7px;text-transform:uppercase;letter-spacing:.5px}
.form-group input,.form-group select{width:100%;padding:12px 16px;border:2px solid #e0ece7;border-radius:var(--radius-sm);font-family:'Quicksand',sans-serif;font-size:15px;font-weight:600;color:var(--text);background:var(--mist);transition:border-color .2s,box-shadow .2s;outline:none}
.form-group input:focus,.form-group select:focus{border-color:var(--forest-light);box-shadow:0 0 0 3px rgba(64,145,108,.15);background:white}
.form-row{display:flex;gap:12px;flex-wrap:wrap}
.form-row .form-group{flex:1;min-width:140px}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:var(--radius-sm);font-family:'Quicksand',sans-serif;font-size:14px;font-weight:700;cursor:pointer;border:none;transition:all .18s;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,var(--forest-light),var(--forest-dark));color:white}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 18px var(--shadow-deep)}
.btn-secondary{background:var(--mist);border:2px solid var(--leaf-dark);color:var(--forest)}
.btn-secondary:hover{background:var(--forest);color:white}
.btn-danger{background:#fff0f0;border:2px solid var(--berry-light);color:var(--berry)}
.btn-danger:hover{background:var(--berry);color:white}
.btn-warning{background:#fff8e6;border:2px solid var(--sun);color:var(--bark)}
.btn-warning:hover{background:var(--sun);color:var(--forest-dark)}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-full{width:100%;justify-content:center;padding:14px}

/* ALERTS */
.alert{padding:12px 18px;border-radius:var(--radius-sm);margin-bottom:16px;font-weight:600;font-size:14px}
.alert-error{background:#fff0f0;border:2px solid var(--berry-light);color:var(--berry)}
.alert-success{background:#f0faf5;border:2px solid var(--leaf-dark);color:var(--forest-dark)}
.alert-info{background:#f0f4ff;border:2px solid #a0b0f0;color:#3040a0}

/* TABLES */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:14px}
th{background:var(--mist);padding:10px 14px;text-align:left;font-weight:800;color:var(--forest-dark);font-size:12px;text-transform:uppercase;letter-spacing:.5px}
td{padding:10px 14px;border-bottom:1px solid #f0f0f0;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#f9fdf9}

/* BADGES */
.badge{display:inline-block;padding:3px 9px;border-radius:100px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.5px}
.badge-admin{background:var(--forest-dark);color:white}
.badge-ohjaaja{background:var(--leaf);color:var(--forest-dark)}
.badge-inactive{background:#eee;color:var(--text-soft)}

/* RATIO SPECIFIC */
.summary-bar{background:linear-gradient(135deg,var(--forest-dark),var(--forest));border-radius:var(--radius);padding:20px;margin-bottom:18px;color:white;display:flex;gap:12px;flex-wrap:wrap}
.summary-item{flex:1;min-width:100px;text-align:center}
.summary-number{font-family:'Nunito',sans-serif;font-size:30px;font-weight:900;display:block;line-height:1}
.summary-label{font-size:11px;opacity:.8;margin-top:3px;font-weight:600}

.group-card{background:var(--snow);border-radius:var(--radius);margin-bottom:14px;box-shadow:0 4px 20px var(--shadow);overflow:hidden}
.group-header{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;border-bottom:1px solid transparent;transition:border-color .2s}
.group-header:hover{background:#fafdf9}
.group-card.expanded .group-header{border-color:#e8f0ec}
.group-title{display:flex;align-items:center;gap:12px}
.group-emoji{font-size:26px}
.group-name-text{font-family:'Nunito',sans-serif;font-size:19px;font-weight:900;color:var(--forest-dark)}
.group-count{font-size:12px;color:var(--text-soft);font-weight:600;margin-top:2px}
.ratio-badge{background:var(--mist);border-radius:10px;padding:8px 14px;text-align:right;min-width:80px}
.ratio-num{font-family:'Nunito',sans-serif;font-size:20px;font-weight:900;color:var(--forest)}
.ratio-lbl{font-size:10px;color:var(--text-soft);font-weight:600}
.ratio-adults{background:var(--forest);border-radius:10px;padding:8px 14px;text-align:center;min-width:64px}
.ratio-adults .ratio-num{color:white}
.ratio-adults .ratio-lbl{color:rgba(255,255,255,.75)}

.children-list{padding:10px 14px;display:none}
.group-card.expanded .children-list{display:block}
.child-row{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;transition:background .15s}
.child-row:hover{background:var(--mist)}
.child-avatar{width:38px;height:38px;border-radius:50%;background:var(--leaf);display:flex;align-items:center;justify-content:center;font-family:'Nunito',sans-serif;font-weight:900;font-size:15px;color:var(--forest-dark);flex-shrink:0}
.child-avatar.absent{background:#f0f0f0;filter:grayscale(1);opacity:.45}
.child-name{font-weight:700;font-size:15px}
.child-name.absent{text-decoration:line-through;color:var(--text-soft)}
.child-meta{font-size:11px;color:var(--text-soft);display:flex;gap:6px;margin-top:2px;align-items:center}
.age-badge{background:var(--forest);color:white;padding:2px 7px;border-radius:5px;font-size:10px;font-weight:700}
.age-badge.young{background:var(--sun);color:var(--forest-dark)}
.absence-lbl{background:#fff0f0;color:var(--berry);padding:2px 7px;border-radius:5px;font-size:10px;font-weight:700}

.expand-arrow{transition:transform .25s;font-size:16px;color:var(--text-soft);margin-left:4px}
.group-card.expanded .expand-arrow{transform:rotate(180deg)}

.date-banner{background:linear-gradient(135deg,var(--sun-light),var(--sun));border-radius:var(--radius);padding:14px 20px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 4px 14px rgba(244,162,97,.3)}
.date-text{font-family:'Nunito',sans-serif;font-weight:800;font-size:15px;color:var(--forest-dark)}
.date-sub{font-size:11px;color:var(--bark);margin-top:2px}

/* ADMIN USER MANAGEMENT */
.user-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f0f0f0;flex-wrap:wrap}
.user-row:last-child{border-bottom:none}
.user-avatar{width:36px;height:36px;border-radius:50%;background:var(--purple);color:white;display:flex;align-items:center;justify-content:center;font-family:'Nunito',sans-serif;font-weight:900;font-size:15px;flex-shrink:0}
.user-info{flex:1;min-width:120px}
.user-name{font-weight:700;font-size:14px}
.user-sub{font-size:11px;color:var(--text-soft);margin-top:1px}

/* INSTALL BUTTON */
.install-btn{background:var(--sun-light);color:var(--forest-dark);border:none;padding:8px 13px;border-radius:8px;font-family:'Quicksand',sans-serif;font-size:13px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:5px;min-height:38px}
.install-btn[hidden]{display:none}
.install-btn.block{width:100%;justify-content:center;margin-top:14px;padding:12px;font-size:14px;background:var(--mist);border:2px solid var(--leaf-dark)}

/* BOTTOM NAV (mobile only) */
.topbar-nav{display:contents}
.bottom-nav{display:none}
@media(max-width:700px){
  .topbar{padding:0 14px;padding-top:env(safe-area-inset-top);height:calc(58px + env(safe-area-inset-top))}
  .topbar-nav,.topbar-user{display:none}
  .install-btn:not(.block) .inst-txt{display:none}
  .topbar-btn,.install-btn{min-height:42px;min-width:42px;justify-content:center;font-size:15px}
  body.has-nav{padding-bottom:calc(72px + env(safe-area-inset-bottom))}
  .bottom-nav{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:200;background:white;border-top:1px solid #dfece6;box-shadow:0 -4px 18px var(--shadow);padding-bottom:env(safe-area-inset-bottom)}
  .bottom-nav a{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;min-height:64px;text-decoration:none;color:var(--text-soft);font-size:11px;font-weight:700;-webkit-tap-highlight-color:transparent}
  .bottom-nav a .ico{font-size:22px;line-height:1}
  .bottom-nav a.active{color:var(--forest);background:var(--mist)}
  .page{padding:16px 12px}
  .btn{min-height:42px}
  .card{padding:18px}
}
@media(max-width:480px){
  .topbar-logo span.logo-text{display:none}
  .summary-number{font-size:24px}
  .form-row{flex-direction:column}
}
</style>
<?php }

function installButton(bool $block = false): void { ?>
<button type="button" class="install-btn<?= $block ? ' block' : '' ?>" data-install hidden aria-label="Asenna sovellus">📲<span class="inst-txt"> Asenna sovellus</span></button>
<?php }

function topbar(array $user, string $active = ''): void {
    // APP_ROOT is defined in db.php as the project root directory (filesystem).
    // We derive the web base path by comparing SCRIPT_FILENAME to DOCUMENT_ROOT.
    $docRoot   = rtrim(str_replace('\\','/',realpath($_SERVER['DOCUMENT_ROOT'])), '/');
    $appRoot   = rtrim(str_replace('\\','/',realpath(__DIR__ . '/../')), '/');
    $base      = str_replace($docRoot, '', $appRoot); // e.g. "/paivakoti" or ""
?>
<div class="topbar">
  <a href="<?= $base ?>/dashboard.php" class="topbar-logo">🌳 <span class="logo-text">Päiväkoti</span></a>
  <div class="topbar-actions">
    <span class="topbar-user">👤 <?= htmlspecialchars($user['full_name']) ?></span>
    <span class="topbar-nav">
    <a href="<?= $base ?>/dashboard.php" class="topbar-btn <?= $active==='dashboard'?'active':'' ?>">📊 Ryhmät</a>
    <?php if ($user['role']==='admin'): ?>
    <a href="<?= $base ?>/admin/groups.php" class="topbar-btn <?= $active==='groups'?'active':'' ?>">🗂️ Hallinta</a>
    <a href="<?= $base ?>/admin/users.php" class="topbar-btn <?= $active==='users'?'active':'' ?>">👥 Käyttäjät</a>
    <?php endif; ?>
    <a href="<?= $base ?>/profile.php" class="topbar-btn <?= $active==='profile'?'active':'' ?>">⚙️</a>
    </span>
    <a href="<?= $base ?>/help.php" class="topbar-btn <?= $active==='help'?'active':'' ?>" aria-label="Ohje">❓</a>
    <?php installButton(); ?>
    <a href="<?= $base ?>/logout.php" class="topbar-btn" aria-label="Kirjaudu ulos">🚪</a>
  </div>
</div>
<nav class="bottom-nav" aria-label="Päävalikko">
  <a href="<?= $base ?>/dashboard.php" class="<?= $active==='dashboard'?'active':'' ?>"><span class="ico">📊</span>Ryhmät</a>
  <?php if ($user['role']==='admin'): ?>
  <a href="<?= $base ?>/admin/groups.php" class="<?= $active==='groups'?'active':'' ?>"><span class="ico">🗂️</span>Hallinta</a>
  <a href="<?= $base ?>/admin/users.php" class="<?= $active==='users'?'active':'' ?>"><span class="ico">👥</span>Käyttäjät</a>
  <?php endif; ?>
  <a href="<?= $base ?>/profile.php" class="<?= $active==='profile'?'active':'' ?>"><span class="ico">⚙️</span>Profiili</a>
</nav>
<script>document.body.classList.add('has-nav');</script>
<?php }

function htmlFoot(): void { ?>
</body></html>
<?php }

function flash(string $key): ?string {
    startSession();
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}
function setFlash(string $key, string $msg): void {
    startSession();
    $_SESSION['flash'][$key] = $msg;
}
