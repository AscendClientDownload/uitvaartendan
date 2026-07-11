# /admin Content-Editing Panel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give Annabelle a password-protected `/admin` panel where she edits the site's text through a web form, and hitting "Alles opslaan" publishes the change immediately on Yourhosting (PHP-enabled shared hosting) — no FTP, no code.

**Architecture:** A single custom PHP admin app (`admin/`) with no framework/database. `admin/data/content.data.php` (a PHP file that `return`s an array) is the source of truth; every save regenerates the public `content.js` from it via `json_encode`. Sensitive files (`content.data.php`, `config.php`, `lockout.data.php`) are PHP files that only `return` a value with no `echo` — requesting them directly over HTTP always yields a blank response, regardless of `.htaccess`/server config, which is the primary protection layer. `admin/data/.htaccess` (`Require all denied`) is a second, defense-in-depth layer.

**Tech Stack:** Plain PHP 8.x (no Composer, no framework), vanilla JS for the repeatable pricing-item rows, plain CSS. Local testing via PHP's built-in server (`php -S`) and Playwright (already used elsewhere in this project).

## Global Constraints

- No server-side framework, no Composer, no database — matches the rest of this project's zero-dependency approach (from the original site brief).
- All admin-facing text is Dutch, written for a non-technical user (from HANDLEIDING.md's existing tone).
- v1 is text-only — no photo upload, no color/theme editing (per approved spec, `docs/superpowers/specs/2026-07-11-admin-panel-design.md`).
- This project directory is **not a git repository** (`git status` confirms `fatal: not a git repository`). Steps below that would normally end in `git add`/`git commit` are replaced with a verification step instead — do not run git commands in this project unless the user has explicitly asked for a repo to be initialized.
- PHP is installed locally at `C:\Users\Micha\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe`, reachable as plain `php` via the wrapper script `C:\Users\Micha\bin\php`. Use bare `php ...` in all commands below.
- Project root for all relative paths below: `C:\Users\Micha\source\repos\uitvaartendan` (referred to as `<root>`).

---

### Task 1: Split `content.js` into data (`content.js`) + logic (`render.js`)

**Files:**
- Modify: `<root>/content.js` (strip everything after the `SITE_CONTENT` object; add a "this file is generated" header comment)
- Create: `<root>/render.js` (the `getContentValue`/`applyContent`/`DOMContentLoaded` code removed from `content.js`)
- Modify: `<root>/index.html` (add `<script src="render.js" defer></script>` between the existing `content.js` and `nav.js` script tags)

**Interfaces:**
- Consumes: nothing new — `SITE_CONTENT` global defined by `content.js`, same as today.
- Produces: `render.js` defines global functions `getContentValue(path)` and `applyContent()`, both used by nothing else in this task, but `applyContent` is what makes `data-content`/`data-content-list`/`data-content-href` attributes work — every later task's admin form relies on the public site continuing to render correctly via this file.

- [ ] **Step 1: Create `render.js` with the logic moved out of `content.js`**

Write `<root>/render.js`:

```js
// ============================================================
// TOONT DE TEKST UIT content.js OP DE PAGINA
// ============================================================
// Dit bestand hoef je niet aan te passen. Teksten wijzig je via
// /admin (of, als noodgeval, via content.js).

function getContentValue(path) {
  return path.split(".").reduce((obj, key) => (obj == null ? undefined : obj[key]), SITE_CONTENT);
}

function applyContent() {
  document.querySelectorAll("[data-content]").forEach((el) => {
    const value = getContentValue(el.getAttribute("data-content"));
    if (value !== undefined) {
      el.textContent = value;
    }
  });

  document.querySelectorAll("[data-content-list]").forEach((el) => {
    const value = getContentValue(el.getAttribute("data-content-list"));
    if (Array.isArray(value)) {
      el.innerHTML = "";
      value.forEach((item) => {
        const li = document.createElement("li");
        li.textContent = item;
        el.appendChild(li);
      });
    }
  });

  document.querySelectorAll("[data-content-href]").forEach((el) => {
    const [path, attr] = el.getAttribute("data-content-href").split("|");
    const value = getContentValue(path);
    if (value !== undefined) {
      el.setAttribute(attr || "href", value);
    }
  });
}

document.addEventListener("DOMContentLoaded", applyContent);
```

- [ ] **Step 2: Replace `content.js` with the data-only version**

Write `<root>/content.js` (identical `SITE_CONTENT` values as today, just the trailing logic removed and the header comment updated):

```js
// ============================================================
// INHOUD BEWERKEN — HIER KAN JE ALLES AANPASSEN
// ============================================================
// Verander gewoon de tekst tussen de aanhalingstekens ("...").
// Laat de aanhalingstekens zelf en de komma's aan het eind van de
// regel staan, anders werkt de website niet meer goed.
//
// Sla het bestand op en herlaad de pagina om de wijzigingen te zien.
//
// Tekst met [INVULLEN] is nog niet compleet — vul deze aan zodra
// je weet wat erin moet komen.
//
// LET OP: zodra /admin in gebruik is, wordt dit bestand daar
// automatisch opnieuw gegenereerd. Handmatige aanpassingen hier
// gaan dan verloren bij de eerstvolgende keer opslaan in /admin.
// ============================================================

const SITE_CONTENT = {

  // Algemene bedrijfsgegevens — worden overal op de site gebruikt
  bedrijf: {
    naam: "Uitvaart en dan?",
    slogan: "Praktische begeleiding voor nabestaanden",
    telefoon: "06-19421856",
    telefoon_href: "0619421856",
    email: "info@uitvaartendan.nl",
    straat: "Riddersborch 5",
    postcode_plaats: "3992 BG Houten",
    openingstijden: "Maandag t/m zondag, 09:00 – 21:00",
  },

  // Navigatiemenu (bovenin elke pagina)
  navigatie: {
    home: "Home",
    over_ons: "Over mij",
    diensten: "Diensten",
    contact: "Contact",
    cta_knop: "Neem contact op",
  },

  // ---------------- HOMEPAGINA ----------------
  home: {
    hero_titel: "Je bent niet alleen. Ik loop met je mee.",
    hero_ondertitel: "Praktische en persoonlijke ondersteuning voor nabestaanden, in de periode na een uitvaart.",
    hero_knop: "Neem contact op",

    intro_titel: "Na de uitvaart begint het pas",
    intro_tekst: "Welkom bij Uitvaart en dan? Ontstaan vanuit liefde voor de mens en het ontzorgen in een kwetsbare periode. Na een overlijden is er veel te bieden op het gebied van emotionele ondersteuning — echter de praktische en vooral ook persoonlijke begeleiding ontbreekt als schakel na een uitvaart. Je bent niet alleen, ik loop met je mee.",

    diensten_titel: "Hoe ik je kan helpen",
    diensten_tekst: "Van het opstellen van een overzicht met alles wat geregeld moet worden, tot persoonlijke begeleiding bij elke stap. Ik bied duidelijke pakketten, aangepast aan wat jij op dit moment nodig hebt.",
    diensten_knop: "Bekijk de diensten",

    over_titel: "Over Annabelle",
    over_tekst: "Ik ben Annabelle Zaal, 48 jaar, woonachtig in Houten. Vanuit mijn eigen ervaringen weet ik hoeveel er op nabestaanden afkomt nadat een uitvaart voorbij is — en hoe fijn het is om daarin niet alleen te staan.",
    over_knop: "Lees meer over mij",

    voor_wie_titel: "Ook voor organisaties",
    voor_wie_tekst: "Naast particulieren werk ik ook samen met uitvaartorganisaties, notarissen, mantelzorgorganisaties, hospices, verpleegtehuizen en WMO-loketten die hun cliënten graag goed doorverwijzen.",

    cta_titel: "Zullen we kennismaken?",
    cta_tekst: "Neem gerust contact op voor een vrijblijvend gesprek. Samen kijken we wat jij nodig hebt.",
    cta_knop: "Neem contact op",
  },

  // ---------------- OVER ONS ----------------
  over_ons: {
    titel: "Over mij",
    intro: "Ik ben Annabelle Zaal, 48 jaar, en samen met mijn man en drie kinderen woon ik in Houten.",
    paragraaf_1: "Vanuit mijn eigen ervaringen weet ik hoeveel er op nabestaanden afkomt nadat een uitvaart voorbij is. Naast verdriet en machteloosheid moeten er ook behoorlijk wat praktische zaken worden geregeld. Juist in die periode, waarin je eigenlijk alleen maar rust nodig hebt, wordt er veel van je gevraagd.",
    paragraaf_2: "[INVULLEN — Annabelle vult hier haar persoonlijke verhaal verder aan]",
    paragraaf_3: "[INVULLEN]",
    missie_titel: "Waarom ik dit doe",
    missie_tekst: "Het persoonlijke contact, de praktische organisatie en de mogelijkheid om alles aan te passen per situatie — dat is wat Uitvaart en dan? anders maakt. Ik loop met je mee, stap voor stap, in het tempo dat bij jou past.",
    citaat: "Je bent niet alleen. Ik loop met je mee.",
    cta_titel: "Nieuwsgierig wat ik voor jou kan betekenen?",
    cta_knop: "Neem contact op",
  },

  // ---------------- DIENSTEN ----------------
  diensten: {
    titel: "Diensten",
    intro: "Ik werk met duidelijke pakketten als basis, maar elke situatie is anders. In een kennismakingsgesprek bekijken we samen wat het beste bij jouw situatie past.",

    pakket_1: {
      naam: "Basispakket",
      prijs: "€ 395,-",
      beschrijving: "Een overzichtelijk startpunt: samen brengen we in kaart wat er geregeld moet worden, en zet ik de eerste stappen met je uit.",
      items: [
        "Intakegesprek (1 uur)",
        "Twee begeleidingsgesprekken van 2 uur",
        "Het opstellen van een persoonlijke actielijst",
      ],
    },

    pakket_2: {
      naam: "[INVULLEN]",
      prijs: "[INVULLEN]",
      beschrijving: "[INVULLEN — Annabelle vult hier de inhoud van het tweede pakket aan]",
      items: [
        "[INVULLEN]",
      ],
    },

    op_maat_titel: "Altijd op maat",
    op_maat_tekst: "Geen enkele situatie is hetzelfde. Naast de vaste pakketten is er altijd ruimte om onderdelen toe te voegen of aan te passen, zodat de begeleiding precies aansluit bij wat jij nodig hebt.",

    doelgroepen_titel: "Ook voor professionals",
    doelgroepen_tekst: "Bent u werkzaam bij een uitvaartorganisatie, notariskantoor, mantelzorgorganisatie, hospice, verpleeghuis of WMO-loket? Ik denk graag mee over hoe ik als vaste partner nabestaanden praktisch kan ondersteunen na een overlijden.",

    cta_titel: "Benieuwd wat bij jou past?",
    cta_tekst: "Neem contact op voor een vrijblijvend gesprek.",
    cta_knop: "Neem contact op",
  },

  // ---------------- CONTACT ----------------
  contact: {
    titel: "Contact",
    intro: "Heb je een vraag of wil je kennismaken? Neem gerust contact op — telefonisch, per e-mail of via het formulier hieronder.",
    formulier_titel: "Stuur een bericht",
    label_naam: "Naam",
    label_email: "E-mailadres",
    label_telefoon: "Telefoonnummer",
    label_bericht: "Bericht",
    knop_verzenden: "Verstuur bericht",
    gegevens_titel: "Contactgegevens",
    kaart_titel: "Werkgebied",
  },

  // ---------------- FOOTER ----------------
  footer: {
    tekst: "Praktische en persoonlijke begeleiding voor nabestaanden in Houten en omgeving.",
    copyright: "Alle rechten voorbehouden.",
  },
};
```

- [ ] **Step 3: Add `render.js` to `index.html`**

In `<root>/index.html`, find:

```html
  <script src="content.js" defer></script>
  <script src="nav.js" defer></script>
```

Replace with:

```html
  <script src="content.js" defer></script>
  <script src="render.js" defer></script>
  <script src="nav.js" defer></script>
```

- [ ] **Step 4: Verify the public site still renders correctly**

Run from `<root>`:

```bash
npx --yes serve -l 5522 . &
sleep 2
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:5522/render.js
```

Expected: `200`

Then run a Playwright smoke check (same pattern used earlier in this project):

```bash
node -e "
const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', (e) => errors.push(String(e)));
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
  await page.goto('http://localhost:5522/', { waitUntil: 'networkidle' });
  const heroText = await page.textContent('#home h1');
  console.log('Hero text:', heroText);
  console.log('Errors:', JSON.stringify(errors));
  await browser.close();
})();
"
```

Expected: `Hero text: Je bent niet alleen. Ik loop met je mee.` and `Errors: []`

Stop the server: find the PID listening on 5522 (`netstat -ano | grep 5522` on Windows, or `lsof -i :5522` elsewhere) and kill it.

- [ ] **Step 5: Verification checkpoint (no git repo — skip commit)**

Confirm Step 4's output matches exactly before moving to Task 2. This project has no git repository, so there is nothing to commit — just proceed.

---

### Task 2: Admin data layer (`admin/lib.php`, seed data, initial password, `.htaccess`)

**Files:**
- Create: `<root>/admin/lib.php`
- Create: `<root>/admin/data/content.data.php` (seeded with today's `SITE_CONTENT` values)
- Create: `<root>/admin/data/config.php` (bcrypt hash of a freshly generated password)
- Create: `<root>/admin/data/.htaccess`
- Create: `<root>/admin/data/backups/.gitkeep` (empty placeholder so the folder exists; content is irrelevant)

**Interfaces:**
- Produces (used by every later task): `admin_session_start()`, `admin_is_logged_in()`, `admin_require_login()`, `admin_csrf_token()`, `admin_csrf_check($token)`, `admin_load_content()` (returns assoc array), `admin_save_content(array $data)` (backs up, writes `content.data.php`, regenerates `content.js`), `admin_current_password_hash()` (returns string|null), `admin_set_password(string $plain)`, `admin_lockout_seconds_remaining()` (int), `admin_lockout_record_failure()`, `admin_lockout_reset()`.

- [ ] **Step 1: Write `admin/lib.php`**

Write `<root>/admin/lib.php`:

```php
<?php
// ============================================================
// GEDEELDE FUNCTIES VOOR HET ADMIN-PANEEL
// ============================================================
// Wordt geladen door de andere bestanden in deze map. Niet
// rechtstreeks vanuit de browser aanroepen.

define('ADMIN_DATA_DIR', __DIR__ . '/data');
define('ADMIN_CONTENT_FILE', ADMIN_DATA_DIR . '/content.data.php');
define('ADMIN_BACKUP_DIR', ADMIN_DATA_DIR . '/backups');
define('ADMIN_LOCKOUT_FILE', ADMIN_DATA_DIR . '/lockout.data.php');
define('ADMIN_CONFIG_FILE', ADMIN_DATA_DIR . '/config.php');
define('SITE_ROOT', dirname(__DIR__));
define('SITE_CONTENT_JS', SITE_ROOT . '/content.js');

function admin_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/admin/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $isHttps,
    ]);
    session_start();
}

function admin_is_logged_in() {
    admin_session_start();
    return !empty($_SESSION['admin_logged_in']);
}

function admin_require_login() {
    if (!admin_is_logged_in()) {
        header('Location: /admin/');
        exit;
    }
}

function admin_csrf_token() {
    admin_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function admin_csrf_check($token) {
    admin_session_start();
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function admin_export_php_array($data) {
    return "<?php\nreturn " . var_export($data, true) . ";\n";
}

function admin_load_content() {
    if (!file_exists(ADMIN_CONTENT_FILE)) {
        return [];
    }
    return include ADMIN_CONTENT_FILE;
}

function admin_backup_current_content() {
    if (!file_exists(ADMIN_CONTENT_FILE)) {
        return;
    }
    if (!is_dir(ADMIN_BACKUP_DIR)) {
        mkdir(ADMIN_BACKUP_DIR, 0755, true);
    }
    $timestamp = date('Ymd-His');
    copy(ADMIN_CONTENT_FILE, ADMIN_BACKUP_DIR . "/content-{$timestamp}.data.php");

    $backups = glob(ADMIN_BACKUP_DIR . '/content-*.data.php');
    sort($backups);
    $excess = count($backups) - 10;
    for ($i = 0; $i < $excess; $i++) {
        unlink($backups[$i]);
    }
}

function admin_regenerate_content_js($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $output = "// ============================================================\n"
        . "// LET OP: dit bestand wordt automatisch gegenereerd door /admin.\n"
        . "// Wijzig dit bestand niet handmatig — je aanpassingen gaan verloren\n"
        . "// zodra er opnieuw wordt opgeslagen via /admin. Pas teksten aan via\n"
        . "// /admin/\n"
        . "// ============================================================\n\n"
        . "const SITE_CONTENT = {$json};\n";
    file_put_contents(SITE_CONTENT_JS, $output);
}

function admin_save_content($data) {
    admin_backup_current_content();
    file_put_contents(ADMIN_CONTENT_FILE, admin_export_php_array($data));
    admin_regenerate_content_js($data);
}

function admin_load_lockout() {
    if (!file_exists(ADMIN_LOCKOUT_FILE)) {
        return ['attempts' => 0, 'locked_until' => 0];
    }
    return include ADMIN_LOCKOUT_FILE;
}

function admin_save_lockout($state) {
    if (!is_dir(ADMIN_DATA_DIR)) {
        mkdir(ADMIN_DATA_DIR, 0755, true);
    }
    file_put_contents(ADMIN_LOCKOUT_FILE, admin_export_php_array($state));
}

function admin_lockout_seconds_remaining() {
    $state = admin_load_lockout();
    $remaining = $state['locked_until'] - time();
    return $remaining > 0 ? $remaining : 0;
}

function admin_lockout_record_failure() {
    $state = admin_load_lockout();
    $state['attempts'] = ($state['attempts'] ?? 0) + 1;
    if ($state['attempts'] >= 5) {
        $state['locked_until'] = time() + 300;
        $state['attempts'] = 0;
    }
    admin_save_lockout($state);
}

function admin_lockout_reset() {
    admin_save_lockout(['attempts' => 0, 'locked_until' => 0]);
}

function admin_current_password_hash() {
    if (!file_exists(ADMIN_CONFIG_FILE)) {
        return null;
    }
    return include ADMIN_CONFIG_FILE;
}

function admin_set_password($plainPassword) {
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
    $content = "<?php\n"
        . "// Wachtwoord-hash voor /admin. Wijzig dit bestand niet handmatig —\n"
        . "// gebruik de \"Wachtwoord wijzigen\"-pagina in /admin.\n"
        . "return " . var_export($hash, true) . ";\n";
    file_put_contents(ADMIN_CONFIG_FILE, $content);
}
```

- [ ] **Step 2: Generate the seed data file from today's `content.js` values**

Write `<root>/admin/data/content.data.php` (mirrors the `SITE_CONTENT` object written in Task 1 Step 2, as a PHP array):

```php
<?php
// Dit bestand is de brondata voor /admin. Wijzig dit bestand niet
// handmatig — gebruik /admin/ om teksten aan te passen.
return array (
  'bedrijf' =>
  array (
    'naam' => 'Uitvaart en dan?',
    'slogan' => 'Praktische begeleiding voor nabestaanden',
    'telefoon' => '06-19421856',
    'telefoon_href' => '0619421856',
    'email' => 'info@uitvaartendan.nl',
    'straat' => 'Riddersborch 5',
    'postcode_plaats' => '3992 BG Houten',
    'openingstijden' => 'Maandag t/m zondag, 09:00 – 21:00',
  ),
  'navigatie' =>
  array (
    'home' => 'Home',
    'over_ons' => 'Over mij',
    'diensten' => 'Diensten',
    'contact' => 'Contact',
    'cta_knop' => 'Neem contact op',
  ),
  'home' =>
  array (
    'hero_titel' => 'Je bent niet alleen. Ik loop met je mee.',
    'hero_ondertitel' => 'Praktische en persoonlijke ondersteuning voor nabestaanden, in de periode na een uitvaart.',
    'hero_knop' => 'Neem contact op',
    'intro_titel' => 'Na de uitvaart begint het pas',
    'intro_tekst' => 'Welkom bij Uitvaart en dan? Ontstaan vanuit liefde voor de mens en het ontzorgen in een kwetsbare periode. Na een overlijden is er veel te bieden op het gebied van emotionele ondersteuning — echter de praktische en vooral ook persoonlijke begeleiding ontbreekt als schakel na een uitvaart. Je bent niet alleen, ik loop met je mee.',
    'diensten_titel' => 'Hoe ik je kan helpen',
    'diensten_tekst' => 'Van het opstellen van een overzicht met alles wat geregeld moet worden, tot persoonlijke begeleiding bij elke stap. Ik bied duidelijke pakketten, aangepast aan wat jij op dit moment nodig hebt.',
    'diensten_knop' => 'Bekijk de diensten',
    'over_titel' => 'Over Annabelle',
    'over_tekst' => 'Ik ben Annabelle Zaal, 48 jaar, woonachtig in Houten. Vanuit mijn eigen ervaringen weet ik hoeveel er op nabestaanden afkomt nadat een uitvaart voorbij is — en hoe fijn het is om daarin niet alleen te staan.',
    'over_knop' => 'Lees meer over mij',
    'voor_wie_titel' => 'Ook voor organisaties',
    'voor_wie_tekst' => 'Naast particulieren werk ik ook samen met uitvaartorganisaties, notarissen, mantelzorgorganisaties, hospices, verpleegtehuizen en WMO-loketten die hun cliënten graag goed doorverwijzen.',
    'cta_titel' => 'Zullen we kennismaken?',
    'cta_tekst' => 'Neem gerust contact op voor een vrijblijvend gesprek. Samen kijken we wat jij nodig hebt.',
    'cta_knop' => 'Neem contact op',
  ),
  'over_ons' =>
  array (
    'titel' => 'Over mij',
    'intro' => 'Ik ben Annabelle Zaal, 48 jaar, en samen met mijn man en drie kinderen woon ik in Houten.',
    'paragraaf_1' => 'Vanuit mijn eigen ervaringen weet ik hoeveel er op nabestaanden afkomt nadat een uitvaart voorbij is. Naast verdriet en machteloosheid moeten er ook behoorlijk wat praktische zaken worden geregeld. Juist in die periode, waarin je eigenlijk alleen maar rust nodig hebt, wordt er veel van je gevraagd.',
    'paragraaf_2' => '[INVULLEN — Annabelle vult hier haar persoonlijke verhaal verder aan]',
    'paragraaf_3' => '[INVULLEN]',
    'missie_titel' => 'Waarom ik dit doe',
    'missie_tekst' => 'Het persoonlijke contact, de praktische organisatie en de mogelijkheid om alles aan te passen per situatie — dat is wat Uitvaart en dan? anders maakt. Ik loop met je mee, stap voor stap, in het tempo dat bij jou past.',
    'citaat' => 'Je bent niet alleen. Ik loop met je mee.',
    'cta_titel' => 'Nieuwsgierig wat ik voor jou kan betekenen?',
    'cta_knop' => 'Neem contact op',
  ),
  'diensten' =>
  array (
    'titel' => 'Diensten',
    'intro' => 'Ik werk met duidelijke pakketten als basis, maar elke situatie is anders. In een kennismakingsgesprek bekijken we samen wat het beste bij jouw situatie past.',
    'pakket_1' =>
    array (
      'naam' => 'Basispakket',
      'prijs' => '€ 395,-',
      'beschrijving' => 'Een overzichtelijk startpunt: samen brengen we in kaart wat er geregeld moet worden, en zet ik de eerste stappen met je uit.',
      'items' =>
      array (
        0 => 'Intakegesprek (1 uur)',
        1 => 'Twee begeleidingsgesprekken van 2 uur',
        2 => 'Het opstellen van een persoonlijke actielijst',
      ),
    ),
    'pakket_2' =>
    array (
      'naam' => '[INVULLEN]',
      'prijs' => '[INVULLEN]',
      'beschrijving' => '[INVULLEN — Annabelle vult hier de inhoud van het tweede pakket aan]',
      'items' =>
      array (
        0 => '[INVULLEN]',
      ),
    ),
    'op_maat_titel' => 'Altijd op maat',
    'op_maat_tekst' => 'Geen enkele situatie is hetzelfde. Naast de vaste pakketten is er altijd ruimte om onderdelen toe te voegen of aan te passen, zodat de begeleiding precies aansluit bij wat jij nodig hebt.',
    'doelgroepen_titel' => 'Ook voor professionals',
    'doelgroepen_tekst' => 'Bent u werkzaam bij een uitvaartorganisatie, notariskantoor, mantelzorgorganisatie, hospice, verpleeghuis of WMO-loket? Ik denk graag mee over hoe ik als vaste partner nabestaanden praktisch kan ondersteunen na een overlijden.',
    'cta_titel' => 'Benieuwd wat bij jou past?',
    'cta_tekst' => 'Neem contact op voor een vrijblijvend gesprek.',
    'cta_knop' => 'Neem contact op',
  ),
  'contact' =>
  array (
    'titel' => 'Contact',
    'intro' => 'Heb je een vraag of wil je kennismaken? Neem gerust contact op — telefonisch, per e-mail of via het formulier hieronder.',
    'formulier_titel' => 'Stuur een bericht',
    'label_naam' => 'Naam',
    'label_email' => 'E-mailadres',
    'label_telefoon' => 'Telefoonnummer',
    'label_bericht' => 'Bericht',
    'knop_verzenden' => 'Verstuur bericht',
    'gegevens_titel' => 'Contactgegevens',
    'kaart_titel' => 'Werkgebied',
  ),
  'footer' =>
  array (
    'tekst' => 'Praktische en persoonlijke begeleiding voor nabestaanden in Houten en omgeving.',
    'copyright' => 'Alle rechten voorbehouden.',
  ),
);
```

- [ ] **Step 3: Generate the initial admin password and write `config.php`**

Run from `<root>`:

```bash
php -r "
require 'admin/lib.php';
\$password = bin2hex(random_bytes(5));
admin_set_password(\$password);
echo 'INITIAL ADMIN PASSWORD: ' . \$password . PHP_EOL;
"
```

Expected output: a line like `INITIAL ADMIN PASSWORD: a1b2c3d4e5` — **write this down**, it is needed in Task 8 and must be relayed to the user at the end (it will not be shown again; if lost, re-run this command to generate a new one).

Verify it was written correctly:

```bash
php -r "
require 'admin/lib.php';
var_dump(admin_current_password_hash() !== null);
var_dump(password_verify('wrong-password', admin_current_password_hash()));
"
```

Expected: `bool(true)` then `bool(false)`.

- [ ] **Step 4: Block direct web access to `admin/data/`**

Write `<root>/admin/data/.htaccess`:

```apache
# Blokkeert alle directe HTTP-toegang tot deze map. Werkt alleen op
# Apache (zoals Yourhosting). De echte bescherming zit al in het feit
# dat deze bestanden .php-bestanden zijn die niets teruggeven bij
# direct opvragen — dit is een extra, tweede beveiligingslaag.
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Order deny,allow
  Deny from all
</IfModule>
```

- [ ] **Step 5: Create the empty backups directory**

```bash
mkdir -p "<root>/admin/data/backups"
```

(Replace `<root>` with the actual path when running.)

- [ ] **Step 6: Verify the data layer end-to-end**

```bash
php -r "
require 'admin/lib.php';
\$content = admin_load_content();
var_dump(\$content['bedrijf']['naam'] === 'Uitvaart en dan?');
var_dump(count(\$content['diensten']['pakket_1']['items']) === 3);

admin_save_content(\$content);
var_dump(file_exists('content.js'));
var_dump(strpos(file_get_contents('content.js'), 'Je bent niet alleen') !== false);
var_dump(count(glob('admin/data/backups/content-*.data.php')) === 1);

\$data = \$content;
\$data['bedrijf']['naam'] = 'Test \"quote\" and <tag> and back\\\\slash';
admin_save_content(\$data);
\$roundTrip = admin_load_content();
var_dump(\$roundTrip['bedrijf']['naam'] === 'Test \"quote\" and <tag> and back\\\\slash');
var_dump(strpos(file_get_contents('content.js'), 'Test \\\"quote\\\"') !== false || strpos(file_get_contents('content.js'), 'Test &quot;quote&quot;') !== false);
"
```

Expected: seven `bool(true)` lines. The last check confirms that a value containing `"`, `<`, and `\` round-trips through `content.data.php` and into `content.js` without breaking either file (because `var_export`/`json_encode` both escape it correctly).

Then restore the original seed content (the round-trip test above intentionally overwrote it with test data):

```bash
php -r "
require 'admin/lib.php';
\$backups = glob('admin/data/backups/content-*.data.php');
sort(\$backups);
copy(\$backups[0], 'admin/data/content.data.php');
admin_regenerate_content_js(include 'admin/data/content.data.php');
echo 'Restored.' . PHP_EOL;
"
```

Expected: `Restored.` — and re-run the `admin_load_content()['bedrijf']['naam'] === 'Uitvaart en dan?'` check from above to confirm it's back to `bool(true)`.

- [ ] **Step 7: Verify the sensitive-file protection works even without `.htaccess` support**

```bash
php -S localhost:8010 -t "<root>" &
sleep 1
curl -s -o /tmp/resp1.txt -w "content.data.php -> %{http_code}, %{size_download} bytes\n" http://localhost:8010/admin/data/content.data.php
curl -s -o /tmp/resp2.txt -w "config.php -> %{http_code}, %{size_download} bytes\n" http://localhost:8010/admin/data/config.php
cat /tmp/resp1.txt
```

Expected: both requests return `200` (since PHP's built-in dev server doesn't apply `.htaccess`) but **`0 bytes`** — confirming that even with no Apache-level protection at all, no data leaks, because the files only `return` a value with no `echo`. `/tmp/resp1.txt` should be empty. Stop the server afterward (`netstat -ano | grep 8010` then `taskkill //F //PID <pid>`, or `kill %1`).

- [ ] **Step 8: Verification checkpoint (no git repo — skip commit)**

Confirm all of Step 6 and Step 7's expected outputs matched before moving to Task 3.

---

### Task 3: Login screen + dashboard form (`admin/index.php`, `admin/admin.css`, `admin/admin.js`)

**Files:**
- Create: `<root>/admin/index.php`
- Create: `<root>/admin/admin.css`
- Create: `<root>/admin/admin.js`

**Interfaces:**
- Consumes: everything produced by `admin/lib.php` in Task 2.
- Produces: the form fields submitted by this page (see the `name="..."` attributes below) are exactly what `admin/save.php` (Task 4) must read from `$_POST`. Field-name contract (must match Task 4 exactly): `bedrijf_naam`, `bedrijf_slogan`, `bedrijf_telefoon`, `bedrijf_telefoon_href`, `bedrijf_email`, `bedrijf_straat`, `bedrijf_postcode_plaats`, `bedrijf_openingstijden`, `navigatie_home`, `navigatie_over_ons`, `navigatie_diensten`, `navigatie_contact`, `navigatie_cta_knop`, `home_hero_titel`, `home_hero_ondertitel`, `home_hero_knop`, `home_intro_titel`, `home_intro_tekst`, `home_diensten_titel`, `home_diensten_tekst`, `home_diensten_knop`, `home_over_titel`, `home_over_tekst`, `home_over_knop`, `home_voor_wie_titel`, `home_voor_wie_tekst`, `home_cta_titel`, `home_cta_tekst`, `home_cta_knop`, `over_ons_titel`, `over_ons_intro`, `over_ons_paragraaf_1`, `over_ons_paragraaf_2`, `over_ons_paragraaf_3`, `over_ons_missie_titel`, `over_ons_missie_tekst`, `over_ons_citaat`, `over_ons_cta_titel`, `over_ons_cta_knop`, `diensten_titel`, `diensten_intro`, `pakket_1_naam`, `pakket_1_prijs`, `pakket_1_beschrijving`, `pakket_1_items[]`, `pakket_2_naam`, `pakket_2_prijs`, `pakket_2_beschrijving`, `pakket_2_items[]`, `diensten_op_maat_titel`, `diensten_op_maat_tekst`, `diensten_doelgroepen_titel`, `diensten_doelgroepen_tekst`, `diensten_cta_titel`, `diensten_cta_tekst`, `diensten_cta_knop`, `contact_titel`, `contact_intro`, `contact_formulier_titel`, `contact_label_naam`, `contact_label_email`, `contact_label_telefoon`, `contact_label_bericht`, `contact_knop_verzenden`, `contact_gegevens_titel`, `contact_kaart_titel`, `footer_tekst`, `footer_copyright`, plus hidden `csrf_token`.

- [ ] **Step 1: Write `admin/admin.css`**

Write `<root>/admin/admin.css`:

```css
:root {
  --color-sage-dark: #52675A;
  --color-sage-darker: #3F4F45;
  --color-bg: #FBF9F5;
  --color-white: #FFFFFF;
  --color-text: #303B37;
  --color-text-light: #5C6864;
  --color-border: #E4DFD5;
  --color-error-bg: #FBEAEA;
  --color-error-text: #8A2E2E;
  --color-success-bg: #E6EDE7;
  --color-success-text: #33513F;
  --color-warn-bg: #FFF8E8;
  --color-warn-border: #D8B36A;
  --font-body: "Segoe UI", Arial, sans-serif;
}

* { box-sizing: border-box; }

body.admin-body {
  margin: 0;
  font-family: var(--font-body);
  background: var(--color-bg);
  color: var(--color-text);
  line-height: 1.5;
}

.admin-body--login {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}

.admin-login-card {
  background: var(--color-white);
  padding: 40px;
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(48, 59, 55, 0.12);
  width: 100%;
  max-width: 360px;
}

.admin-subtitle {
  color: var(--color-text-light);
  margin-bottom: 24px;
}

.admin-header {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 20px 32px;
  background: var(--color-white);
  border-bottom: 1px solid var(--color-border);
}

.admin-header nav a {
  margin-left: 16px;
  color: var(--color-sage-dark);
  text-decoration: none;
  font-weight: 600;
}

.admin-header nav a:hover { text-decoration: underline; }

.admin-form {
  max-width: 760px;
  margin: 32px auto;
  padding: 0 24px 64px;
}

.admin-form fieldset {
  background: var(--color-white);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
}

.admin-form legend {
  font-weight: 700;
  padding: 0 8px;
}

.admin-form label {
  display: block;
  margin-bottom: 16px;
  font-weight: 600;
  font-size: 0.9rem;
}

.admin-form input[type="text"],
.admin-form input[type="email"],
.admin-form input[type="password"],
.admin-form textarea {
  display: block;
  width: 100%;
  margin-top: 6px;
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-family: inherit;
  font-size: 1rem;
  font-weight: 400;
}

.admin-form textarea {
  min-height: 90px;
  resize: vertical;
}

.admin-field--invulling input,
.admin-field--invulling textarea {
  border-color: var(--color-warn-border);
  background: var(--color-warn-bg);
}

.admin-badge {
  display: inline-block;
  background: var(--color-warn-border);
  color: var(--color-white);
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 999px;
  margin-left: 6px;
  vertical-align: middle;
}

.admin-item-row {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
}

.admin-item-row input {
  flex: 1;
  margin-top: 0;
}

.admin-item-remove {
  background: var(--color-error-bg);
  color: var(--color-error-text);
  border: none;
  border-radius: 8px;
  padding: 0 14px;
  cursor: pointer;
  font-weight: 700;
}

.admin-item-add {
  background: none;
  border: 1px dashed var(--color-sage-dark);
  color: var(--color-sage-dark);
  border-radius: 8px;
  padding: 8px 14px;
  cursor: pointer;
  font-weight: 600;
  margin-top: 4px;
}

.btn-primary {
  background: var(--color-sage-dark);
  color: var(--color-white);
  border: none;
  padding: 12px 28px;
  border-radius: 999px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
}

.btn-primary:hover { background: var(--color-sage-darker); }

.btn-block { display: block; width: 100%; margin-top: 8px; }

.admin-banner {
  max-width: 760px;
  margin: 24px auto 0;
  padding: 16px 20px;
  border-radius: 10px;
}

.admin-banner--success {
  background: var(--color-success-bg);
  color: var(--color-success-text);
}

.admin-banner--error {
  background: var(--color-error-bg);
  color: var(--color-error-text);
}

.admin-error {
  background: var(--color-error-bg);
  color: var(--color-error-text);
  padding: 10px 14px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 0.9rem;
}
```

- [ ] **Step 2: Write `admin/admin.js`**

Write `<root>/admin/admin.js`:

```js
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.admin-item-group').forEach((group) => {
    const fieldName = group.getAttribute('data-item-name');
    const rows = group.querySelector('.admin-item-rows');
    const addBtn = group.querySelector('.admin-item-add');

    function wireRemove(row) {
      row.querySelector('.admin-item-remove').addEventListener('click', () => row.remove());
    }

    rows.querySelectorAll('.admin-item-row').forEach(wireRemove);

    addBtn.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'admin-item-row';
      row.innerHTML = '<input type="text" name="' + fieldName + '[]" value=""><button type="button" class="admin-item-remove">Verwijderen</button>';
      wireRemove(row);
      rows.appendChild(row);
    });
  });
});
```

- [ ] **Step 3: Write `admin/index.php`**

Write `<root>/admin/index.php`:

```php
<?php
require __DIR__ . '/lib.php';

admin_session_start();

$loginErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $remaining = admin_lockout_seconds_remaining();
    if ($remaining > 0) {
        $loginErrors[] = 'Te veel mislukte pogingen. Probeer het over ' . ceil($remaining / 60) . ' minuut/minuten opnieuw.';
    } else {
        $password = $_POST['password'] ?? '';
        $hash = admin_current_password_hash();
        if ($hash && password_verify($password, $hash)) {
            admin_lockout_reset();
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            header('Location: /admin/');
            exit;
        }
        admin_lockout_record_failure();
        $loginErrors[] = 'Onjuiste inloggegevens.';
    }
}

if (!admin_is_logged_in()) {
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Inloggen | Uitvaart en dan? beheer</title>
      <link rel="stylesheet" href="admin.css">
    </head>
    <body class="admin-body admin-body--login">
      <main class="admin-login-card">
        <h1>Uitvaart en dan?</h1>
        <p class="admin-subtitle">Log in om de website te beheren</p>
        <?php foreach ($loginErrors as $error): ?>
          <p class="admin-error"><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
        <form method="post" action="/admin/">
          <input type="hidden" name="action" value="login">
          <label for="password">Wachtwoord</label>
          <input type="password" id="password" name="password" required autofocus>
          <button type="submit" class="btn-primary btn-block">Inloggen</button>
        </form>
      </main>
    </body>
    </html>
    <?php
    exit;
}

function admin_field($content, $path) {
    $keys = explode('.', $path);
    $value = $content;
    foreach ($keys as $key) {
        if (!isset($value[$key])) {
            return '';
        }
        $value = $value[$key];
    }
    return is_string($value) ? $value : '';
}

function admin_field_list($content, $path) {
    $keys = explode('.', $path);
    $value = $content;
    foreach ($keys as $key) {
        if (!isset($value[$key])) {
            return [];
        }
        $value = $value[$key];
    }
    return is_array($value) ? $value : [];
}

function admin_label($label, $value) {
    if (strpos((string) $value, '[INVULLEN]') !== false) {
        return htmlspecialchars($label) . ' <span class="admin-badge">nog invullen</span>';
    }
    return htmlspecialchars($label);
}

function admin_field_class($value) {
    return (strpos((string) $value, '[INVULLEN]') !== false) ? ' admin-field--invulling' : '';
}

$content = admin_load_content();
$flashErrors = $_SESSION['form_errors'] ?? [];
$flashData = $_SESSION['form_data'] ?? null;
unset($_SESSION['form_errors'], $_SESSION['form_data']);
if ($flashData !== null) {
    $content = $flashData;
}
$saved = isset($_GET['opgeslagen']);
$csrfToken = admin_csrf_token();
$p1Items = admin_field_list($content, 'diensten.pakket_1.items');
$p2Items = admin_field_list($content, 'diensten.pakket_2.items');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Beheer | Uitvaart en dan?</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
  <header class="admin-header">
    <h1>Uitvaart en dan? — beheer</h1>
    <nav>
      <a href="/admin/wachtwoord.php">Wachtwoord wijzigen</a>
      <a href="/admin/logout.php">Uitloggen</a>
      <a href="/" target="_blank" rel="noopener">Bekijk website</a>
    </nav>
  </header>

  <?php if ($saved): ?>
    <p class="admin-banner admin-banner--success">Opgeslagen! De website is bijgewerkt.</p>
  <?php endif; ?>

  <?php if ($flashErrors): ?>
    <div class="admin-banner admin-banner--error">
      <p>Er ging iets mis, controleer de volgende velden:</p>
      <ul><?php foreach ($flashErrors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" action="/admin/save.php" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <fieldset>
      <legend>Bedrijfsgegevens</legend>
      <label>Bedrijfsnaam<input type="text" name="bedrijf_naam" value="<?= htmlspecialchars(admin_field($content, 'bedrijf.naam')) ?>" required></label>
      <label>Slogan<input type="text" name="bedrijf_slogan" value="<?= htmlspecialchars(admin_field($content, 'bedrijf.slogan')) ?>"></label>
      <label>Telefoonnummer (zoals getoond, bv. 06-19421856)<input type="text" name="bedrijf_telefoon" value="<?= htmlspecialchars(admin_field($content, 'bedrijf.telefoon')) ?>" required></label>
      <label>Telefoonnummer (voor de belknop, alleen cijfers, bv. 0619421856)<input type="text" name="bedrijf_telefoon_href" value="<?= htmlspecialchars(admin_field($content, 'bedrijf.telefoon_href')) ?>" required></label>
      <label>E-mailadres<input type="email" name="bedrijf_email" value="<?= htmlspecialchars(admin_field($content, 'bedrijf.email')) ?>" required></label>
      <label>Straat en huisnummer<input type="text" name="bedrijf_straat" value="<?= htmlspecialchars(admin_field($content, 'bedrijf.straat')) ?>" required></label>
      <label>Postcode en plaats<input type="text" name="bedrijf_postcode_plaats" value="<?= htmlspecialchars(admin_field($content, 'bedrijf.postcode_plaats')) ?>" required></label>
      <label>Openingstijden<input type="text" name="bedrijf_openingstijden" value="<?= htmlspecialchars(admin_field($content, 'bedrijf.openingstijden')) ?>" required></label>
    </fieldset>

    <fieldset>
      <legend>Menu (bovenin de website)</legend>
      <label>"Home"<input type="text" name="navigatie_home" value="<?= htmlspecialchars(admin_field($content, 'navigatie.home')) ?>"></label>
      <label>"Over mij"<input type="text" name="navigatie_over_ons" value="<?= htmlspecialchars(admin_field($content, 'navigatie.over_ons')) ?>"></label>
      <label>"Diensten"<input type="text" name="navigatie_diensten" value="<?= htmlspecialchars(admin_field($content, 'navigatie.diensten')) ?>"></label>
      <label>"Contact"<input type="text" name="navigatie_contact" value="<?= htmlspecialchars(admin_field($content, 'navigatie.contact')) ?>"></label>
      <label>Knop rechtsboven<input type="text" name="navigatie_cta_knop" value="<?= htmlspecialchars(admin_field($content, 'navigatie.cta_knop')) ?>"></label>
    </fieldset>

    <fieldset>
      <legend>Home</legend>
      <label>Hoofdtitel (hero)<input type="text" name="home_hero_titel" value="<?= htmlspecialchars(admin_field($content, 'home.hero_titel')) ?>"></label>
      <label>Ondertitel (hero)<textarea name="home_hero_ondertitel"><?= htmlspecialchars(admin_field($content, 'home.hero_ondertitel')) ?></textarea></label>
      <label>Knoptekst (hero)<input type="text" name="home_hero_knop" value="<?= htmlspecialchars(admin_field($content, 'home.hero_knop')) ?>"></label>
      <label>Titel "Welkom"-sectie<input type="text" name="home_intro_titel" value="<?= htmlspecialchars(admin_field($content, 'home.intro_titel')) ?>"></label>
      <label>Tekst "Welkom"-sectie<textarea name="home_intro_tekst"><?= htmlspecialchars(admin_field($content, 'home.intro_tekst')) ?></textarea></label>
      <label>Titel "Diensten"-sectie<input type="text" name="home_diensten_titel" value="<?= htmlspecialchars(admin_field($content, 'home.diensten_titel')) ?>"></label>
      <label>Tekst "Diensten"-sectie<textarea name="home_diensten_tekst"><?= htmlspecialchars(admin_field($content, 'home.diensten_tekst')) ?></textarea></label>
      <label>Knoptekst "Diensten"-sectie<input type="text" name="home_diensten_knop" value="<?= htmlspecialchars(admin_field($content, 'home.diensten_knop')) ?>"></label>
      <label>Titel "Over Annabelle"-sectie<input type="text" name="home_over_titel" value="<?= htmlspecialchars(admin_field($content, 'home.over_titel')) ?>"></label>
      <label>Tekst "Over Annabelle"-sectie<textarea name="home_over_tekst"><?= htmlspecialchars(admin_field($content, 'home.over_tekst')) ?></textarea></label>
      <label>Knoptekst "Over Annabelle"-sectie<input type="text" name="home_over_knop" value="<?= htmlspecialchars(admin_field($content, 'home.over_knop')) ?>"></label>
      <label>Titel "Ook voor organisaties"<input type="text" name="home_voor_wie_titel" value="<?= htmlspecialchars(admin_field($content, 'home.voor_wie_titel')) ?>"></label>
      <label>Tekst "Ook voor organisaties"<textarea name="home_voor_wie_tekst"><?= htmlspecialchars(admin_field($content, 'home.voor_wie_tekst')) ?></textarea></label>
      <label>Titel afsluitende oproep<input type="text" name="home_cta_titel" value="<?= htmlspecialchars(admin_field($content, 'home.cta_titel')) ?>"></label>
      <label>Tekst afsluitende oproep<textarea name="home_cta_tekst"><?= htmlspecialchars(admin_field($content, 'home.cta_tekst')) ?></textarea></label>
      <label>Knoptekst afsluitende oproep<input type="text" name="home_cta_knop" value="<?= htmlspecialchars(admin_field($content, 'home.cta_knop')) ?>"></label>
    </fieldset>

    <fieldset>
      <legend>Over mij</legend>
      <label>Titel<input type="text" name="over_ons_titel" value="<?= htmlspecialchars(admin_field($content, 'over_ons.titel')) ?>"></label>
      <label>Introzin<textarea name="over_ons_intro"><?= htmlspecialchars(admin_field($content, 'over_ons.intro')) ?></textarea></label>
      <label class="<?= trim(admin_field_class(admin_field($content, 'over_ons.paragraaf_1'))) ?>">Paragraaf 1<textarea name="over_ons_paragraaf_1"><?= htmlspecialchars(admin_field($content, 'over_ons.paragraaf_1')) ?></textarea></label>
      <label class="<?= trim(admin_field_class(admin_field($content, 'over_ons.paragraaf_2'))) ?>"><?= admin_label('Paragraaf 2', admin_field($content, 'over_ons.paragraaf_2')) ?><textarea name="over_ons_paragraaf_2"><?= htmlspecialchars(admin_field($content, 'over_ons.paragraaf_2')) ?></textarea></label>
      <label class="<?= trim(admin_field_class(admin_field($content, 'over_ons.paragraaf_3'))) ?>"><?= admin_label('Paragraaf 3', admin_field($content, 'over_ons.paragraaf_3')) ?><textarea name="over_ons_paragraaf_3"><?= htmlspecialchars(admin_field($content, 'over_ons.paragraaf_3')) ?></textarea></label>
      <label>Titel missie<input type="text" name="over_ons_missie_titel" value="<?= htmlspecialchars(admin_field($content, 'over_ons.missie_titel')) ?>"></label>
      <label>Tekst missie<textarea name="over_ons_missie_tekst"><?= htmlspecialchars(admin_field($content, 'over_ons.missie_tekst')) ?></textarea></label>
      <label>Citaat<input type="text" name="over_ons_citaat" value="<?= htmlspecialchars(admin_field($content, 'over_ons.citaat')) ?>"></label>
      <label>Titel afsluitende oproep<input type="text" name="over_ons_cta_titel" value="<?= htmlspecialchars(admin_field($content, 'over_ons.cta_titel')) ?>"></label>
      <label>Knoptekst afsluitende oproep<input type="text" name="over_ons_cta_knop" value="<?= htmlspecialchars(admin_field($content, 'over_ons.cta_knop')) ?>"></label>
    </fieldset>

    <fieldset>
      <legend>Diensten</legend>
      <label>Titel<input type="text" name="diensten_titel" value="<?= htmlspecialchars(admin_field($content, 'diensten.titel')) ?>"></label>
      <label>Introzin<textarea name="diensten_intro"><?= htmlspecialchars(admin_field($content, 'diensten.intro')) ?></textarea></label>

      <h3>Pakket 1</h3>
      <label>Naam<input type="text" name="pakket_1_naam" value="<?= htmlspecialchars(admin_field($content, 'diensten.pakket_1.naam')) ?>"></label>
      <label>Prijs<input type="text" name="pakket_1_prijs" value="<?= htmlspecialchars(admin_field($content, 'diensten.pakket_1.prijs')) ?>"></label>
      <label>Beschrijving<textarea name="pakket_1_beschrijving"><?= htmlspecialchars(admin_field($content, 'diensten.pakket_1.beschrijving')) ?></textarea></label>
      <label>Onderdelen van dit pakket</label>
      <div class="admin-item-group" data-item-name="pakket_1_items">
        <div class="admin-item-rows">
          <?php foreach ($p1Items as $item): ?>
            <div class="admin-item-row">
              <input type="text" name="pakket_1_items[]" value="<?= htmlspecialchars($item) ?>">
              <button type="button" class="admin-item-remove">Verwijderen</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="admin-item-add">+ Regel toevoegen</button>
      </div>

      <h3><?= admin_label('Pakket 2', admin_field($content, 'diensten.pakket_2.naam')) ?></h3>
      <label>Naam<input type="text" name="pakket_2_naam" value="<?= htmlspecialchars(admin_field($content, 'diensten.pakket_2.naam')) ?>"></label>
      <label>Prijs<input type="text" name="pakket_2_prijs" value="<?= htmlspecialchars(admin_field($content, 'diensten.pakket_2.prijs')) ?>"></label>
      <label>Beschrijving<textarea name="pakket_2_beschrijving"><?= htmlspecialchars(admin_field($content, 'diensten.pakket_2.beschrijving')) ?></textarea></label>
      <label>Onderdelen van dit pakket</label>
      <div class="admin-item-group" data-item-name="pakket_2_items">
        <div class="admin-item-rows">
          <?php foreach ($p2Items as $item): ?>
            <div class="admin-item-row">
              <input type="text" name="pakket_2_items[]" value="<?= htmlspecialchars($item) ?>">
              <button type="button" class="admin-item-remove">Verwijderen</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="admin-item-add">+ Regel toevoegen</button>
      </div>

      <label style="margin-top: 16px;">Titel "Altijd op maat"<input type="text" name="diensten_op_maat_titel" value="<?= htmlspecialchars(admin_field($content, 'diensten.op_maat_titel')) ?>"></label>
      <label>Tekst "Altijd op maat"<textarea name="diensten_op_maat_tekst"><?= htmlspecialchars(admin_field($content, 'diensten.op_maat_tekst')) ?></textarea></label>
      <label>Titel "Ook voor professionals"<input type="text" name="diensten_doelgroepen_titel" value="<?= htmlspecialchars(admin_field($content, 'diensten.doelgroepen_titel')) ?>"></label>
      <label>Tekst "Ook voor professionals"<textarea name="diensten_doelgroepen_tekst"><?= htmlspecialchars(admin_field($content, 'diensten.doelgroepen_tekst')) ?></textarea></label>
      <label>Titel afsluitende oproep<input type="text" name="diensten_cta_titel" value="<?= htmlspecialchars(admin_field($content, 'diensten.cta_titel')) ?>"></label>
      <label>Tekst afsluitende oproep<textarea name="diensten_cta_tekst"><?= htmlspecialchars(admin_field($content, 'diensten.cta_tekst')) ?></textarea></label>
      <label>Knoptekst afsluitende oproep<input type="text" name="diensten_cta_knop" value="<?= htmlspecialchars(admin_field($content, 'diensten.cta_knop')) ?>"></label>
    </fieldset>

    <fieldset>
      <legend>Contact</legend>
      <label>Titel<input type="text" name="contact_titel" value="<?= htmlspecialchars(admin_field($content, 'contact.titel')) ?>"></label>
      <label>Introzin<textarea name="contact_intro"><?= htmlspecialchars(admin_field($content, 'contact.intro')) ?></textarea></label>
      <label>Titel formulier<input type="text" name="contact_formulier_titel" value="<?= htmlspecialchars(admin_field($content, 'contact.formulier_titel')) ?>"></label>
      <label>Label "Naam"-veld<input type="text" name="contact_label_naam" value="<?= htmlspecialchars(admin_field($content, 'contact.label_naam')) ?>"></label>
      <label>Label "E-mailadres"-veld<input type="text" name="contact_label_email" value="<?= htmlspecialchars(admin_field($content, 'contact.label_email')) ?>"></label>
      <label>Label "Telefoonnummer"-veld<input type="text" name="contact_label_telefoon" value="<?= htmlspecialchars(admin_field($content, 'contact.label_telefoon')) ?>"></label>
      <label>Label "Bericht"-veld<input type="text" name="contact_label_bericht" value="<?= htmlspecialchars(admin_field($content, 'contact.label_bericht')) ?>"></label>
      <label>Knoptekst versturen<input type="text" name="contact_knop_verzenden" value="<?= htmlspecialchars(admin_field($content, 'contact.knop_verzenden')) ?>"></label>
      <label>Titel "Contactgegevens"<input type="text" name="contact_gegevens_titel" value="<?= htmlspecialchars(admin_field($content, 'contact.gegevens_titel')) ?>"></label>
      <label>Titel kaart<input type="text" name="contact_kaart_titel" value="<?= htmlspecialchars(admin_field($content, 'contact.kaart_titel')) ?>"></label>
    </fieldset>

    <fieldset>
      <legend>Footer</legend>
      <label>Tekst<textarea name="footer_tekst"><?= htmlspecialchars(admin_field($content, 'footer.tekst')) ?></textarea></label>
      <label>Auteursrecht-tekst<input type="text" name="footer_copyright" value="<?= htmlspecialchars(admin_field($content, 'footer.copyright')) ?>"></label>
    </fieldset>

    <button type="submit" class="btn-primary btn-block">Alles opslaan</button>
  </form>

  <script src="admin.js"></script>
</body>
</html>
```

- [ ] **Step 4: Verify the dashboard renders and is pre-filled**

```bash
php -S localhost:8010 -t "<root>" &
sleep 1
curl -s -c /tmp/cookies.txt -o /dev/null -w "GET /admin/ (not logged in) -> %{http_code}\n" http://localhost:8010/admin/
curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt -o /tmp/login_resp.txt -w "POST login -> %{http_code}\n" \
  -d "action=login&password=REPLACE_WITH_PASSWORD_FROM_TASK_2_STEP_3" http://localhost:8010/admin/
curl -s -b /tmp/cookies.txt -o /tmp/dashboard.txt http://localhost:8010/admin/
grep -o 'value="Uitvaart en dan?"' /tmp/dashboard.txt
grep -o 'value="Intakegesprek (1 uur)"' /tmp/dashboard.txt
grep -c 'admin-badge' /tmp/dashboard.txt
```

Expected: first `curl` returns `200` (the login form itself is a 200, it just shows the login screen instead of the dashboard); the login `curl` returns `200` if a `Location` header isn't followed — that's expected since `curl` doesn't follow redirects by default (confirm with `-i` if you want to see the `302 Location: /admin/` header explicitly); the two `grep -o` lines each print one match, confirming the form is pre-filled with real content; `grep -c admin-badge` prints a number `>= 1` confirming the `[INVULLEN]` fields are flagged. Stop the server afterward.

- [ ] **Step 5: Verification checkpoint (no git repo — skip commit)**

Confirm Step 4's greps matched before moving to Task 4.

---

### Task 4: Save handler (`admin/save.php`)

**Files:**
- Create: `<root>/admin/save.php`

**Interfaces:**
- Consumes: `$_POST` field names defined in Task 3's Interfaces block; `admin_require_login()`, `admin_csrf_check()`, `admin_save_content()` from `admin/lib.php`.
- Produces: on success, redirects to `/admin/?opgeslagen=1` and `content.js`/`content.data.php` reflect the new values (consumed by the public site and by `admin/index.php`'s next GET). On validation failure, sets `$_SESSION['form_errors']` and `$_SESSION['form_data']` (consumed by `admin/index.php`, per Task 3 Step 3) and redirects to `/admin/`.

- [ ] **Step 1: Write `admin/save.php`**

Write `<root>/admin/save.php`:

```php
<?php
require __DIR__ . '/lib.php';
admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/');
    exit;
}

if (!admin_csrf_check($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo 'Ongeldige aanvraag (csrf-token klopt niet). Ga terug en probeer het opnieuw.';
    exit;
}

function clean_text($value) {
    return trim(str_replace(["\r\n", "\r"], "\n", (string) $value));
}

function clean_list($values) {
    $result = [];
    foreach ((array) $values as $value) {
        $value = clean_text($value);
        if ($value !== '') {
            $result[] = $value;
        }
    }
    return $result;
}

$data = [
    'bedrijf' => [
        'naam' => clean_text($_POST['bedrijf_naam'] ?? ''),
        'slogan' => clean_text($_POST['bedrijf_slogan'] ?? ''),
        'telefoon' => clean_text($_POST['bedrijf_telefoon'] ?? ''),
        'telefoon_href' => clean_text($_POST['bedrijf_telefoon_href'] ?? ''),
        'email' => clean_text($_POST['bedrijf_email'] ?? ''),
        'straat' => clean_text($_POST['bedrijf_straat'] ?? ''),
        'postcode_plaats' => clean_text($_POST['bedrijf_postcode_plaats'] ?? ''),
        'openingstijden' => clean_text($_POST['bedrijf_openingstijden'] ?? ''),
    ],
    'navigatie' => [
        'home' => clean_text($_POST['navigatie_home'] ?? ''),
        'over_ons' => clean_text($_POST['navigatie_over_ons'] ?? ''),
        'diensten' => clean_text($_POST['navigatie_diensten'] ?? ''),
        'contact' => clean_text($_POST['navigatie_contact'] ?? ''),
        'cta_knop' => clean_text($_POST['navigatie_cta_knop'] ?? ''),
    ],
    'home' => [
        'hero_titel' => clean_text($_POST['home_hero_titel'] ?? ''),
        'hero_ondertitel' => clean_text($_POST['home_hero_ondertitel'] ?? ''),
        'hero_knop' => clean_text($_POST['home_hero_knop'] ?? ''),
        'intro_titel' => clean_text($_POST['home_intro_titel'] ?? ''),
        'intro_tekst' => clean_text($_POST['home_intro_tekst'] ?? ''),
        'diensten_titel' => clean_text($_POST['home_diensten_titel'] ?? ''),
        'diensten_tekst' => clean_text($_POST['home_diensten_tekst'] ?? ''),
        'diensten_knop' => clean_text($_POST['home_diensten_knop'] ?? ''),
        'over_titel' => clean_text($_POST['home_over_titel'] ?? ''),
        'over_tekst' => clean_text($_POST['home_over_tekst'] ?? ''),
        'over_knop' => clean_text($_POST['home_over_knop'] ?? ''),
        'voor_wie_titel' => clean_text($_POST['home_voor_wie_titel'] ?? ''),
        'voor_wie_tekst' => clean_text($_POST['home_voor_wie_tekst'] ?? ''),
        'cta_titel' => clean_text($_POST['home_cta_titel'] ?? ''),
        'cta_tekst' => clean_text($_POST['home_cta_tekst'] ?? ''),
        'cta_knop' => clean_text($_POST['home_cta_knop'] ?? ''),
    ],
    'over_ons' => [
        'titel' => clean_text($_POST['over_ons_titel'] ?? ''),
        'intro' => clean_text($_POST['over_ons_intro'] ?? ''),
        'paragraaf_1' => clean_text($_POST['over_ons_paragraaf_1'] ?? ''),
        'paragraaf_2' => clean_text($_POST['over_ons_paragraaf_2'] ?? ''),
        'paragraaf_3' => clean_text($_POST['over_ons_paragraaf_3'] ?? ''),
        'missie_titel' => clean_text($_POST['over_ons_missie_titel'] ?? ''),
        'missie_tekst' => clean_text($_POST['over_ons_missie_tekst'] ?? ''),
        'citaat' => clean_text($_POST['over_ons_citaat'] ?? ''),
        'cta_titel' => clean_text($_POST['over_ons_cta_titel'] ?? ''),
        'cta_knop' => clean_text($_POST['over_ons_cta_knop'] ?? ''),
    ],
    'diensten' => [
        'titel' => clean_text($_POST['diensten_titel'] ?? ''),
        'intro' => clean_text($_POST['diensten_intro'] ?? ''),
        'pakket_1' => [
            'naam' => clean_text($_POST['pakket_1_naam'] ?? ''),
            'prijs' => clean_text($_POST['pakket_1_prijs'] ?? ''),
            'beschrijving' => clean_text($_POST['pakket_1_beschrijving'] ?? ''),
            'items' => clean_list($_POST['pakket_1_items'] ?? []),
        ],
        'pakket_2' => [
            'naam' => clean_text($_POST['pakket_2_naam'] ?? ''),
            'prijs' => clean_text($_POST['pakket_2_prijs'] ?? ''),
            'beschrijving' => clean_text($_POST['pakket_2_beschrijving'] ?? ''),
            'items' => clean_list($_POST['pakket_2_items'] ?? []),
        ],
        'op_maat_titel' => clean_text($_POST['diensten_op_maat_titel'] ?? ''),
        'op_maat_tekst' => clean_text($_POST['diensten_op_maat_tekst'] ?? ''),
        'doelgroepen_titel' => clean_text($_POST['diensten_doelgroepen_titel'] ?? ''),
        'doelgroepen_tekst' => clean_text($_POST['diensten_doelgroepen_tekst'] ?? ''),
        'cta_titel' => clean_text($_POST['diensten_cta_titel'] ?? ''),
        'cta_tekst' => clean_text($_POST['diensten_cta_tekst'] ?? ''),
        'cta_knop' => clean_text($_POST['diensten_cta_knop'] ?? ''),
    ],
    'contact' => [
        'titel' => clean_text($_POST['contact_titel'] ?? ''),
        'intro' => clean_text($_POST['contact_intro'] ?? ''),
        'formulier_titel' => clean_text($_POST['contact_formulier_titel'] ?? ''),
        'label_naam' => clean_text($_POST['contact_label_naam'] ?? ''),
        'label_email' => clean_text($_POST['contact_label_email'] ?? ''),
        'label_telefoon' => clean_text($_POST['contact_label_telefoon'] ?? ''),
        'label_bericht' => clean_text($_POST['contact_label_bericht'] ?? ''),
        'knop_verzenden' => clean_text($_POST['contact_knop_verzenden'] ?? ''),
        'gegevens_titel' => clean_text($_POST['contact_gegevens_titel'] ?? ''),
        'kaart_titel' => clean_text($_POST['contact_kaart_titel'] ?? ''),
    ],
    'footer' => [
        'tekst' => clean_text($_POST['footer_tekst'] ?? ''),
        'copyright' => clean_text($_POST['footer_copyright'] ?? ''),
    ],
];

$requiredFields = [
    'Bedrijfsnaam' => $data['bedrijf']['naam'],
    'Telefoonnummer (zoals getoond)' => $data['bedrijf']['telefoon'],
    'Telefoonnummer (belknop)' => $data['bedrijf']['telefoon_href'],
    'E-mailadres' => $data['bedrijf']['email'],
    'Straat en huisnummer' => $data['bedrijf']['straat'],
    'Postcode en plaats' => $data['bedrijf']['postcode_plaats'],
    'Openingstijden' => $data['bedrijf']['openingstijden'],
];

$errors = [];
foreach ($requiredFields as $label => $value) {
    if ($value === '') {
        $errors[] = "\"$label\" mag niet leeg zijn.";
    }
}
if ($data['bedrijf']['email'] !== '' && !filter_var($data['bedrijf']['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = '"E-mailadres" is geen geldig e-mailadres.';
}

if ($errors) {
    admin_session_start();
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $data;
    header('Location: /admin/');
    exit;
}

admin_save_content($data);

header('Location: /admin/?opgeslagen=1');
exit;
```

- [ ] **Step 2: Verify a successful save round-trips correctly**

```bash
php -S localhost:8010 -t "<root>" &
sleep 1
curl -s -c /tmp/cookies.txt -o /dev/null http://localhost:8010/admin/
curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt -o /dev/null \
  -d "action=login&password=REPLACE_WITH_PASSWORD_FROM_TASK_2_STEP_3" http://localhost:8010/admin/
CSRF=$(curl -s -b /tmp/cookies.txt http://localhost:8010/admin/ | grep -o 'name="csrf_token" value="[^"]*"' | sed 's/.*value="//;s/"//')
echo "CSRF token: $CSRF"

curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt -i -o /tmp/save_resp.txt \
  --data-urlencode "csrf_token=$CSRF" \
  --data-urlencode "bedrijf_naam=Uitvaart en dan? TEST" \
  --data-urlencode "bedrijf_slogan=Praktische begeleiding voor nabestaanden" \
  --data-urlencode "bedrijf_telefoon=06-19421856" \
  --data-urlencode "bedrijf_telefoon_href=0619421856" \
  --data-urlencode "bedrijf_email=info@uitvaartendan.nl" \
  --data-urlencode "bedrijf_straat=Riddersborch 5" \
  --data-urlencode "bedrijf_postcode_plaats=3992 BG Houten" \
  --data-urlencode "bedrijf_openingstijden=Maandag t/m zondag, 09:00 - 21:00" \
  http://localhost:8010/admin/save.php
head -5 /tmp/save_resp.txt

grep -o 'Uitvaart en dan? TEST' "<root>/content.js"
```

Expected: response headers show `302` (or `Location: /admin/?opgeslagen=1`, depending on how curl reports it with `-i`), and the final `grep` finds `Uitvaart en dan? TEST` inside the live `content.js` — proving the save wrote through end-to-end. Note this test only sent the `bedrijf` fieldset; every other field will have been saved as empty strings, which is fine for this test but means **Step 3 below must restore the real content** before moving on.

- [ ] **Step 3: Restore the real seed content after the round-trip test**

```bash
php -r "
require '<root>/admin/lib.php';
\$backups = glob('<root>/admin/data/backups/content-*.data.php');
sort(\$backups);
copy(\$backups[0], '<root>/admin/data/content.data.php');
admin_regenerate_content_js(include '<root>/admin/data/content.data.php');
echo 'Restored from ' . \$backups[0] . PHP_EOL;
"
grep -o 'Je bent niet alleen' "<root>/content.js"
```

Expected: `Restored from ...` followed by a path, and the `grep` finds `Je bent niet alleen` in `content.js` again (confirms the real content is back, not the `TEST` value from Step 2). Stop the PHP server.

- [ ] **Step 4: Verification checkpoint (no git repo — skip commit)**

Confirm Steps 2 and 3's expected output matched before moving to Task 5.

---

### Task 5: Password change + logout (`admin/wachtwoord.php`, `admin/logout.php`)

**Files:**
- Create: `<root>/admin/wachtwoord.php`
- Create: `<root>/admin/logout.php`

**Interfaces:**
- Consumes: `admin_require_login()`, `admin_csrf_token()`, `admin_csrf_check()`, `admin_current_password_hash()`, `admin_set_password()` from `admin/lib.php`.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Write `admin/wachtwoord.php`**

Write `<root>/admin/wachtwoord.php`:

```php
<?php
require __DIR__ . '/lib.php';
admin_require_login();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_check($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo 'Ongeldige aanvraag (csrf-token klopt niet). Ga terug en probeer het opnieuw.';
        exit;
    }

    $current = $_POST['huidig_wachtwoord'] ?? '';
    $new = $_POST['nieuw_wachtwoord'] ?? '';
    $confirm = $_POST['nieuw_wachtwoord_bevestig'] ?? '';

    $hash = admin_current_password_hash();
    if (!$hash || !password_verify($current, $hash)) {
        $errors[] = 'Huidig wachtwoord klopt niet.';
    }
    if (strlen($new) < 8) {
        $errors[] = 'Nieuw wachtwoord moet minstens 8 tekens lang zijn.';
    }
    if ($new !== $confirm) {
        $errors[] = 'De twee nieuwe wachtwoorden komen niet overeen.';
    }

    if (!$errors) {
        admin_set_password($new);
        $success = true;
    }
}

$csrfToken = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wachtwoord wijzigen | Uitvaart en dan? beheer</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
  <header class="admin-header">
    <h1>Wachtwoord wijzigen</h1>
    <nav>
      <a href="/admin/">Terug naar beheer</a>
      <a href="/admin/logout.php">Uitloggen</a>
    </nav>
  </header>

  <?php if ($success): ?>
    <p class="admin-banner admin-banner--success">Je wachtwoord is gewijzigd.</p>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="admin-banner admin-banner--error">
      <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" action="/admin/wachtwoord.php" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <fieldset>
      <legend>Wachtwoord wijzigen</legend>
      <label>Huidig wachtwoord<input type="password" name="huidig_wachtwoord" required></label>
      <label>Nieuw wachtwoord (minstens 8 tekens)<input type="password" name="nieuw_wachtwoord" required minlength="8"></label>
      <label>Nieuw wachtwoord (nogmaals)<input type="password" name="nieuw_wachtwoord_bevestig" required minlength="8"></label>
      <button type="submit" class="btn-primary">Wachtwoord wijzigen</button>
    </fieldset>
  </form>
</body>
</html>
```

- [ ] **Step 2: Write `admin/logout.php`**

Write `<root>/admin/logout.php`:

```php
<?php
require __DIR__ . '/lib.php';
admin_session_start();
$_SESSION = [];
session_destroy();
header('Location: /admin/');
exit;
```

- [ ] **Step 3: Verify the password-change flow, then change it back**

```bash
php -S localhost:8010 -t "<root>" &
sleep 1
curl -s -c /tmp/cookies.txt -o /dev/null http://localhost:8010/admin/
curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt -o /dev/null \
  -d "action=login&password=REPLACE_WITH_PASSWORD_FROM_TASK_2_STEP_3" http://localhost:8010/admin/
CSRF=$(curl -s -b /tmp/cookies.txt http://localhost:8010/admin/wachtwoord.php | grep -o 'name="csrf_token" value="[^"]*"' | sed 's/.*value="//;s/"//')

curl -s -b /tmp/cookies.txt -o /tmp/pw_resp.txt \
  --data-urlencode "csrf_token=$CSRF" \
  --data-urlencode "huidig_wachtwoord=REPLACE_WITH_PASSWORD_FROM_TASK_2_STEP_3" \
  --data-urlencode "nieuw_wachtwoord=temporary-test-pw-123" \
  --data-urlencode "nieuw_wachtwoord_bevestig=temporary-test-pw-123" \
  http://localhost:8010/admin/wachtwoord.php
grep -o 'Je wachtwoord is gewijzigd' /tmp/pw_resp.txt

php -r "
require '<root>/admin/lib.php';
var_dump(password_verify('temporary-test-pw-123', admin_current_password_hash()));
"
```

Expected: `grep` finds `Je wachtwoord is gewijzigd`, and `var_dump` prints `bool(true)`.

Then change it back to the original password recorded in Task 2 Step 3, so it matches what will be relayed to the user:

```bash
php -r "
require '<root>/admin/lib.php';
admin_set_password('REPLACE_WITH_PASSWORD_FROM_TASK_2_STEP_3');
var_dump(password_verify('REPLACE_WITH_PASSWORD_FROM_TASK_2_STEP_3', admin_current_password_hash()));
"
```

Expected: `bool(true)`. Verify `logout.php` too:

```bash
curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt -o /dev/null -w "%{http_code}\n" http://localhost:8010/admin/logout.php
curl -s -b /tmp/cookies.txt -o /tmp/after_logout.txt http://localhost:8010/admin/
grep -o 'Log in om de website te beheren' /tmp/after_logout.txt
```

Expected: `302` (or similar redirect code), then the grep confirms the login screen shows again (session was destroyed). Stop the PHP server.

- [ ] **Step 4: Verification checkpoint (no git repo — skip commit)**

Confirm Step 3's expected output matched before moving to Task 6.

---

### Task 6: Lockout and backup-rotation edge-case tests

These behaviors are already implemented inside `admin/lib.php` (Task 2). This task specifically exercises the edge cases the earlier tasks didn't cover: what happens on repeated failed logins, and what happens once more than 10 backups exist.

**Files:**
- None created or modified — this task is pure verification of existing code.

**Interfaces:**
- Consumes: `admin_lockout_record_failure()`, `admin_lockout_seconds_remaining()`, `admin_lockout_reset()`, `admin_backup_current_content()` from `admin/lib.php`.

- [ ] **Step 1: Verify lockout triggers after 5 failed attempts and blocks a 6th**

```bash
php -S localhost:8010 -t "<root>" &
sleep 1
php -r "require '<root>/admin/lib.php'; admin_lockout_reset();"

for i in 1 2 3 4 5; do
  curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -o /tmp/attempt$i.txt \
    -d "action=login&password=wrong-password-$i" http://localhost:8010/admin/
done

curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -o /tmp/attempt6.txt \
  -d "action=login&password=still-wrong" http://localhost:8010/admin/
grep -o 'Te veel mislukte pogingen' /tmp/attempt6.txt
```

Expected: the `grep` on attempt 6 finds `Te veel mislukte pogingen` — confirming the lockout engaged after 5 failures, even though attempt 6 used yet another wrong password (proving it's the lockout blocking it, not just another wrong-password message — attempts 1-5 would each show `Onjuiste inloggegevens` instead, check `/tmp/attempt5.txt` to confirm that distinction):

```bash
grep -o 'Onjuiste inloggegevens' /tmp/attempt5.txt
```

Expected: matches (attempt 5 was a normal wrong-password rejection, not yet locked out).

- [ ] **Step 2: Reset the lockout so the real password works again**

```bash
php -r "require '<root>/admin/lib.php'; admin_lockout_reset();"
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -i -o /tmp/real_login.txt \
  -d "action=login&password=REPLACE_WITH_PASSWORD_FROM_TASK_2_STEP_3" http://localhost:8010/admin/
head -3 /tmp/real_login.txt
```

Expected: a `302` redirect (not another lockout message) — confirms the reset worked and the real password logs in successfully.

- [ ] **Step 3: Verify backup rotation keeps only the 10 most recent files**

```bash
php -r "
require '<root>/admin/lib.php';
\$content = admin_load_content();
for (\$i = 0; \$i < 13; \$i++) {
    admin_save_content(\$content);
    usleep(1100000);
}
\$backups = glob('<root>/admin/data/backups/content-*.data.php');
echo 'Backup count: ' . count(\$backups) . PHP_EOL;
"
```

Expected: `Backup count: 10` (13 saves happened, but only the 10 most recent backups survive — the `usleep(1100000)` between saves ensures each backup gets a distinct one-second timestamp, since `admin_backup_current_content()` names backups by `date('Ymd-His')`). This step is slow (~14 seconds) by design.

Restore the real content afterward (this test looped `admin_save_content` on already-correct data, so no restore is actually needed here — but confirm nothing drifted):

```bash
grep -o 'Je bent niet alleen' "<root>/content.js"
```

Expected: match found. Stop the PHP server.

- [ ] **Step 4: Verification checkpoint (no git repo — skip commit)**

Confirm Steps 1-3's expected output matched before moving to Task 7.

---

### Task 7: Update `HANDLEIDING.md` for `/admin`

**Files:**
- Modify: `<root>/HANDLEIDING.md`

**Interfaces:**
- None — documentation only.

- [ ] **Step 1: Add a new primary section pointing to `/admin`, demote the Notepad method to a fallback**

Read the current `<root>/HANDLEIDING.md` first (it was last updated in an earlier session of this project — re-read it fresh rather than assuming its exact current wording), then make these changes:

1. Near the top (right after the existing intro paragraph about the site being one scrolling page), insert a new section titled `## Teksten aanpassen via /admin (aanbevolen)` explaining: the login URL is `https://www.uitvaartendan.nl/admin/`, log in with the password Micha gave her, every field on that page matches a piece of text on the website, click "Alles opslaan" at the bottom and the website updates immediately — no FTP needed. Mention the "Wachtwoord wijzigen" link so she can set her own password the first time she logs in, and that fields marked "nog invullen" are the `[INVULLEN]` placeholders still waiting for her text/photos.
2. In the existing "Een tekst aanpassen" section (the Notepad/`content.js` walkthrough), add a note at the top: *"Sinds /admin bestaat, hoef je dit eigenlijk niet meer te doen — gebruik liever /admin hierboven. Deze uitleg blijft staan voor noodgevallen (bijvoorbeeld als /admin een keer niet bereikbaar is). Let op: als je hier handmatig iets aanpast, wordt dat overschreven zodra iemand daarna opslaat via /admin."*
3. Add a short "Wachtwoord vergeten?" note: if she forgets her password, she should ask Micha, who can reset it via FTP by re-running the password-generation step against `admin/lib.php` (Micha will know what this means).

Write out the complete updated file (don't leave the rest of the existing content out — preserve every other section as-is, including the "Overige bestanden in de map" section from the previous session, adding one more bullet there for `admin/` explaining it's the beheerpaneel and should never be deleted or renamed).

- [ ] **Step 2: Verify the file is well-formed Markdown**

```bash
grep -c '^## ' "<root>/HANDLEIDING.md"
grep -o '/admin/' "<root>/HANDLEIDING.md" | head -3
```

Expected: a heading count consistent with the previous file plus one new `##` section, and at least one match for `/admin/`.

- [ ] **Step 3: Verification checkpoint (no git repo — skip commit)**

Confirm Step 2's expected output matched before moving to Task 8.

---

### Task 8: Full end-to-end Playwright pass + final security spot-checks

**Files:**
- None created or modified — this task is pure verification, mirroring the Playwright-based verification approach used earlier in this project (see `docs/superpowers/specs/2026-07-11-admin-panel-design.md`'s Testing section).

**Interfaces:**
- Consumes: the complete admin app from Tasks 1-7, running under `php -S`.

- [ ] **Step 1: Run the full login → edit → save → verify-on-public-site → logout flow in a real browser**

```bash
php -S localhost:8010 -t "<root>" &
sleep 1
node -e "
const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', (e) => errors.push(String(e)));
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });

  await page.goto('http://localhost:8010/admin/', { waitUntil: 'networkidle' });
  await page.fill('#password', 'REPLACE_WITH_PASSWORD_FROM_TASK_2_STEP_3');
  await page.click('button[type=submit]');
  await page.waitForSelector('.admin-form');

  const heroValue = await page.inputValue('input[name=home_hero_titel]');
  console.log('Prefilled hero value:', heroValue);

  await page.fill('input[name=home_hero_titel]', 'Playwright test titel');
  await page.click('.admin-form button.btn-primary');
  await page.waitForSelector('.admin-banner--success');
  console.log('Save confirmation shown: yes');

  await page.goto('http://localhost:8010/', { waitUntil: 'networkidle' });
  const publicHero = await page.textContent('#home h1');
  console.log('Public site hero after save:', publicHero);

  await page.goto('http://localhost:8010/admin/');
  await page.fill('input[name=home_hero_titel]', 'Je bent niet alleen. Ik loop met je mee.');
  await page.click('.admin-form button.btn-primary');
  await page.waitForSelector('.admin-banner--success');

  await page.goto('http://localhost:8010/admin/logout.php');
  const loginVisible = await page.isVisible('#password');
  console.log('Back at login after logout:', loginVisible);

  console.log('Console/page errors:', JSON.stringify(errors));
  await browser.close();
})();
"
```

Expected: `Prefilled hero value: Je bent niet alleen. Ik loop met je mee.`, `Save confirmation shown: yes`, `Public site hero after save: Playwright test titel` (proving the save actually changed the live public page), then after the restore-and-logout steps, `Back at login after logout: true`, and `Console/page errors: []`.

- [ ] **Step 2: Confirm the restored hero title matches the original**

```bash
curl -s http://localhost:8010/ | grep -o 'Je bent niet alleen. Ik loop met je mee.'
```

Expected: one match — confirms Step 1's restore-to-original at the end left the live site correct.

- [ ] **Step 3: Re-run the Task 1 Step 4 smoke check against the PHP server to confirm nothing regressed**

```bash
node -e "
const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', (e) => errors.push(String(e)));
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
  await page.goto('http://localhost:8010/', { waitUntil: 'networkidle' });
  const emptyContent = await page.evaluate(() => {
    const empties = [];
    document.querySelectorAll('[data-content]').forEach((el) => {
      if (!el.textContent || !el.textContent.trim()) empties.push(el.getAttribute('data-content'));
    });
    return empties;
  });
  console.log('Empty content fields:', JSON.stringify(emptyContent));
  console.log('Errors:', JSON.stringify(errors));
  await browser.close();
})();
"
```

Expected: `Empty content fields: []` and `Errors: []`.

- [ ] **Step 4: Final CSRF spot-check**

```bash
curl -s -c /tmp/cookies.txt -o /dev/null http://localhost:8010/admin/
curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt -o /dev/null \
  -d "action=login&password=REPLACE_WITH_PASSWORD_FROM_TASK_2_STEP_3" http://localhost:8010/admin/
curl -s -b /tmp/cookies.txt -o /tmp/csrf_test.txt -w "%{http_code}\n" \
  -d "csrf_token=wrong-token&bedrijf_naam=Should Not Save" http://localhost:8010/admin/save.php
grep -o 'csrf-token klopt niet' /tmp/csrf_test.txt
grep -c 'Should Not Save' "<root>/content.js"
```

Expected: HTTP status `403`, the grep for the Dutch error message matches, and the final grep count is `0` — confirming a forged request without a valid CSRF token is rejected and never touches `content.js`. Stop the PHP server (`netstat -ano | grep 8010`, then `taskkill //F //PID <pid>`).

- [ ] **Step 5: Verification checkpoint (no git repo — skip commit)**

Confirm all of Task 8's expected outputs matched. This is the last task — once it passes, the feature is complete and ready to report to the user, including:
- The final admin password (from Task 2 Step 3, unless changed again in testing — Task 5 Step 3 already restores it).
- A reminder that `/admin` only works once these files are uploaded to Yourhosting via FTP (PHP doesn't run under `npx serve`, only under a real PHP-enabled host or `php -S` locally) — so the delivery message must include the full updated file list to upload, matching what's in `<root>` now.

---

## Post-plan note for whoever reports results to the user

After Task 8 passes, tell the user:
1. The admin password (only shown once during Task 2 Step 3 / confirmed restored in Task 5 Step 3).
2. The login URL will be `https://www.uitvaartendan.nl/admin/` once deployed.
3. The full file list that needs to go to Yourhosting via FTP now includes the `admin/` folder (with its `data/` subfolder, including the `.htaccess` and the seeded `content.data.php`/`config.php`) alongside the existing root files — all of it needs to be uploaded, not just the changed root files.
4. `admin/data/backups/` will accumulate files over time; that's expected and self-pruning (Task 2/6 confirmed it caps at 10).
5. One check that can only happen after the real deploy: PHP's built-in server used throughout this plan (`php -S`) doesn't read `.htaccess` files at all — only real Apache (Yourhosting) does. Task 2 Step 7 already proved the data files leak nothing even without `.htaccess` support (they're PHP files that return a value with no `echo`), so this isn't a launch blocker — but once it's live, it's worth one manual check: visit `https://www.uitvaartendan.nl/admin/data/content.data.php` directly in a browser and confirm it shows a blank page or a 403 (either is fine; a visible PHP source dump would not be, and would mean PHP isn't executing in that folder on the host, which would be a hosting-configuration problem worth flagging to Yourhosting support).
