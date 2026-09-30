# 🌳 Päiväkoti Suhdelaskuri

PHP + SQLite -pohjainen päiväkodin suhdelaskuri ja hallintajärjestelmä.

## Vaatimukset

- PHP 8.0+ (PDO SQLite -tuki)
- Apache / Nginx (tai PHP:n sisäänrakennettu palvelin)
- SQLite3

## Asennus

1. Kopioi kaikki tiedostot web-palvelimellesi (esim. `/var/www/html/paivakoti/`)
2. Varmista että `data/`-kansio on kirjoitettavissa:
   ```
   chmod 755 data/
   ```
3. Avaa selaimella: `http://palvelimesi/paivakoti/`

### PHP:n kehityspalvelin (testaus)
```bash
cd paivakoti/
php -S localhost:8080
# Avaa: http://localhost:8080
```

## Oletustunnukset

| Käyttäjätunnus | Salasana   | Rooli |
|----------------|------------|-------|
| `admin`        | `admin1234`| Admin |

**Ensimmäisellä kirjautumisella sovellus pakottaa vaihtamaan oletussalasanan** ennen kuin muuta voi käyttää.

## Tiedostorakenne

```
paivakoti/
├── index.php              # Kirjautumissivu
├── dashboard.php          # Ryhmänäkymä + suhdelaskuri
├── profile.php            # Oman salasanan vaihto
├── logout.php
├── reset_request.php      # Salasanan nollaus (pyyntö)
├── reset_password.php     # Salasanan nollaus (uusi salasana)
├── includes/
│   ├── db.php             # SQLite-yhteys ja schema
│   ├── auth.php           # Kirjautuminen ja sessiot
│   └── layout.php         # Yhteinen HTML-pohja
├── admin/
│   ├── groups.php         # Ryhmien ja lasten hallinta (admin)
│   └── users.php          # Käyttäjien hallinta (admin)
└── data/
    └── paivakoti.db       # SQLite-tietokanta (luodaan automaattisesti)
```

## Roolien oikeudet

| Toiminto                  | Ohjaaja | Admin |
|---------------------------|---------|-------|
| Nähdä ryhmät ja lapset    | ✅      | ✅    |
| Merkitä poissaolot        | ✅      | ✅    |
| Toistaiseksi-poissaolot   | ✅      | ✅    |
| Muokata lasten tietoja    | ❌      | ✅    |
| Hallita ryhmiä            | ❌      | ✅    |
| Hallita käyttäjiä         | ❌      | ✅    |
| Nollata päivä             | ❌      | ✅    |

## Suhdeluku

- **Alle 3-vuotiaat**: kerroin 1.75
- **3-vuotiaat ja vanhemmat**: kerroin 1.0
- Tarvittavien aikuisten määrä = `⌈yhteissuhde / 7⌉`
- Poissaolot nollautuvat automaattisesti uuden kalenteripäivän alkaessa
  (pois lukien "toistaiseksi poissa" -merkinnät)

## Salasanan nollaus

Koska kyseessä on intranet-käyttö ilman sähköpostipalvelinta,
nollauslinkki näytetään suoraan näytöllä admin-sivulla tai
`reset_request.php`-sivulla. Tuotantokäytössä voit lisätä
`mail()`-funktion `generateResetToken()`-funktion yhteyteen.
