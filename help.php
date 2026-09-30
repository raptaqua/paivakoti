<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user    = currentUser();
$isAdmin = $user && $user['role'] === 'admin';

htmlHead('Ohje');
?>
<style>
.help h2{font-family:'Nunito',sans-serif;font-weight:900;font-size:18px;color:var(--forest-dark);margin-bottom:10px}
.help p,.help li{font-size:15px;line-height:1.6;font-weight:600}
.help ul,.help ol{padding-left:22px;margin:8px 0}
.help li{margin-bottom:6px}
.help .toc{display:flex;flex-wrap:wrap;gap:8px}
.help .toc a{background:var(--mist);border:2px solid var(--leaf-dark);color:var(--forest);padding:8px 14px;border-radius:10px;font-weight:700;font-size:13px;text-decoration:none}
.help code{background:var(--mist);padding:2px 6px;border-radius:5px;font-size:13px}
.help table td,.help table th{padding:8px 10px}
</style>
<?php if ($user) topbar($user, 'help'); ?>

<div class="page help" style="max-width:720px">

  <div class="card">
    <div class="card-title">❓ Ohje</div>
    <p>Päiväkodin suhdelaskuri näyttää ryhmittäin, kuinka monta lasta on paikalla ja montako aikuista tarvitaan.</p>
    <div class="toc" style="margin-top:14px">
      <a href="#kirjautuminen">Kirjautuminen</a>
      <a href="#suhdeluku">Suhdeluku</a>
      <a href="#poissaolot">Poissaolot</a>
      <a href="#asennus">Asennus puhelimeen</a>
      <a href="#salasana">Salasana</a>
      <?php if ($isAdmin): ?><a href="#hallinta">Hallinta (admin)</a><?php endif; ?>
    </div>
  </div>

  <div class="card" id="kirjautuminen">
    <h2>🔐 Kirjautuminen</h2>
    <ul>
      <li>Kirjaudu käyttäjätunnuksella ja salasanalla. Tunnukset saat pääkäyttäjältä.</li>
      <li>Ensimmäisellä kirjautumisella oletussalasana on vaihdettava uuteen.</li>
      <li>Kirjaudu ulos yläpalkin 🚪-painikkeesta, varsinkin jaetulla laitteella.</li>
    </ul>
  </div>

  <div class="card" id="suhdeluku">
    <h2>📊 Suhdeluku ja aikuisten tarve</h2>
    <p>Etusivulla (Ryhmät) näkyy jokaisen ryhmän tilanne. Ylhäällä on koko päiväkodin yhteenveto: lapsia paikalla, yhteissuhde ja aikuisten tarve.</p>
    <ul>
      <li>Kerroin määräytyy lapsen iän mukaan. Oletuksena <strong>alle 3-vuotiaat 1,75</strong> ja <strong>3-vuotiaat ja vanhemmat 1,0</strong>.</li>
      <li>Kertoimet ja ikäluokat ovat pääkäyttäjän muokattavissa (Kertoimet-sivu). Ikäluokkiin kuulumaton ikä lasketaan kertoimella 1,0.</li>
      <li>Vain paikalla olevat lapset lasketaan mukaan.</li>
      <li>Tarvittavien aikuisten määrä = yhteissuhde ÷ 7, pyöristettynä ylöspäin.</li>
    </ul>
    <p>Esimerkki oletuskertoimilla: 4 alle 3-vuotiasta (4 × 1,75 = 7) ja 3 isompaa (3 × 1,0 = 3) → suhde 10 → 10 ÷ 7 → <strong>2 aikuista</strong>.</p>
    <p style="margin-top:8px">Avaa ryhmä napauttamalla sen otsikkoa, niin näet lapset.</p>
  </div>

  <div class="card" id="poissaolot">
    <h2>🙋 Poissaolojen merkitseminen</h2>
    <ul>
      <li><strong>Merkitse poissa</strong> – lapsi on poissa vain tänään. Painamalla uudelleen (“✓ Poissa tänään”) merkintä poistuu.</li>
      <li><strong>Toistaiseksi</strong> – lapsi on poissa pidempään (esim. loma tai sairaus). Merkintä säilyy päivästä toiseen, kunnes painat “↩ Peruuta toistaiseksi”.</li>
      <li>Päivän poissaolot nollautuvat automaattisesti uuden päivän alkaessa. Toistaiseksi-merkinnät eivät nollaudu.</li>
      <li>Poissaoleva lapsi näkyy harmaana, eikä hän vaikuta suhdelukuun.</li>
    </ul>
  </div>

  <div class="card" id="asennus">
    <h2>📲 Asennus puhelimen kotinäytölle</h2>
    <p>Sovelluksen voi asentaa puhelimeen, jolloin se avautuu omana sovelluksenaan ilman selaimen osoiteriviä.</p>
    <p style="margin-top:10px"><strong>Android (Chrome)</strong></p>
    <ol>
      <li>Paina yläpalkin tai kirjautumissivun <strong>📲 Asenna sovellus</strong> -painiketta.</li>
      <li>Vahvista asennus. Jos painiketta ei näy, avaa Chromen valikko (⋮) ja valitse “Asenna sovellus”.</li>
    </ol>
    <p style="margin-top:10px"><strong>iPhone / iPad (Safari)</strong></p>
    <ol>
      <li>Avaa sivusto Safarilla.</li>
      <li>Paina <strong>Jaa</strong>-painiketta (neliö ja nuoli).</li>
      <li>Valitse <strong>Lisää Koti-valikkoon</strong>.</li>
    </ol>
    <p style="margin-top:10px">Asennus toimii vain suojatulla (HTTPS) yhteydellä. Sovellus tarvitsee verkkoyhteyden.</p>
  </div>

  <div class="card" id="salasana">
    <h2>🔑 Salasana</h2>
    <ul>
      <li><strong>Vaihda salasana:</strong> Profiili (⚙️) → Vaihda salasana. Uuden salasanan on oltava vähintään 6 merkkiä.</li>
      <li><strong>Unohtuiko salasana?</strong> Kirjautumissivun “Unohditko salasanan?” -linkistä saat nollauslinkin. Jos se ei toimi, pyydä pääkäyttäjää nollaamaan salasana.</li>
    </ul>
  </div>

  <?php if ($isAdmin): ?>
  <div class="card" id="hallinta">
    <h2>🛠️ Hallinta (pääkäyttäjä)</h2>
    <p><strong>Ryhmät ja lapset</strong> (Hallinta)</p>
    <ul>
      <li>Lisää, muokkaa ja poista ryhmiä. Ryhmälle valitaan nimi ja emoji.</li>
      <li>Lisää lapsia ryhmään nimellä ja iällä. Ikä määrää kertoimen ikäluokkien mukaan.</li>
      <li>Ryhmän tai lapsen poisto on pysyvä. Ryhmän poisto poistaa myös sen lapset.</li>
    </ul>
    <p style="margin-top:10px"><strong>Kertoimet</strong></p>
    <ul>
      <li>Kertoimet-sivulla voit muokata, lisätä ja poistaa ikäluokkia: ikäväli (iästä–ikään) ja kerroin, esim. 1–2 v → 1,5.</li>
      <li>Jätä “Ikään” tyhjäksi, jos ikäluokalla ei ole ylärajaa. Ikävälit eivät saa mennä päällekkäin.</li>
      <li>Muutos vaikuttaa heti kaikkiin laskelmiin.</li>
    </ul>
    <p style="margin-top:10px"><strong>Käyttäjät</strong></p>
    <ul>
      <li>Luo tunnuksia ja valitse rooli (Ohjaaja tai Admin).</li>
      <li>Muokkaa nimeä ja roolia, aseta tili ei-aktiiviseksi tai nollaa käyttäjän salasana.</li>
    </ul>
    <p style="margin-top:10px"><strong>Uusi päivä</strong></p>
    <ul>
      <li>Etusivun <strong>🔄 Uusi päivä</strong> -painike nollaa päivän poissaolot käsin (toistaiseksi-merkinnät säilyvät).</li>
    </ul>
    <p style="margin-top:10px"><strong>Oikeudet</strong></p>
    <div class="table-wrap"><table>
      <tr><th></th><th>Ohjaaja</th><th>Admin</th></tr>
      <tr><td>Ryhmät ja lapset näkyvillä</td><td>✅</td><td>✅</td></tr>
      <tr><td>Poissaolojen merkintä</td><td>✅</td><td>✅</td></tr>
      <tr><td>Lasten ja ryhmien muokkaus</td><td>❌</td><td>✅</td></tr>
      <tr><td>Käyttäjien hallinta</td><td>❌</td><td>✅</td></tr>
      <tr><td>Päivän nollaus</td><td>❌</td><td>✅</td></tr>
    </table></div>
  </div>
  <?php endif; ?>

  <?php if (!$user): ?>
  <p style="text-align:center"><a class="btn btn-primary" href="index.php">← Kirjautumiseen</a></p>
  <?php endif; ?>
</div>
<?php htmlFoot(); ?>
