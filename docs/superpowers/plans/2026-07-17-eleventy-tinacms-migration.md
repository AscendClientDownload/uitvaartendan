# Eleventy + TinaCMS Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the site's hand-rolled `content.yml`→`content.js` build with Eleventy (11ty) templates reading a Tina-managed JSON file, so Annabelle can edit site text through Tina Cloud's own login (not tied to any GitHub account), while the public site stays 100% static on GitHub Pages exactly as it is today.

**Architecture:** Eleventy (Nunjucks templates) replaces the current `data-content` attribute + client-side JS templating. `src/_data/site.json` replaces `content.yml` as the single source of truth, managed by TinaCMS's plain form-based admin (no visual live editing — that needs a server, which GitHub Pages can't run). Hosting, domain, DNS, and SSL are unchanged.

**Tech Stack:** Eleventy 3.x, TinaCMS + Tina Cloud, Nunjucks templates, GitHub Pages (unchanged), GitHub Actions (build command changes only).

## Global Constraints

- v1 scope is text only — photos, colors/theme (`style.css`), layout, and the Formspree endpoint are never exposed to the CMS. (Spec: "Content model")
- The public site must remain fully static with zero server runtime — this is a hard requirement, not a preference. (Spec: "Non-goal: visual live editing")
- Every field currently in `admin/config.yml` must have an equivalent Tina field — nothing added, nothing dropped. (Spec: "Content model")
- Annabelle's CMS login must be a Tina Cloud account, separate from the repo owner's GitHub identity. (Spec: "Setup & authentication")
- Domain (`uitvaartendan.nl`), DNS, and SSL are untouched by this migration. (Spec: "Architecture")

---

## Task 1: Smoke test — prove Eleventy + Tina admin builds to fully static output

This is a decision gate. The Eleventy+Tina+GitHub-Pages combination hasn't been proven in this project — only reasoned about from how each tool works (11ty has zero server capability, so whatever Tina does with it must be static-compatible). If this task fails to produce fully static output, **stop and report back** rather than continuing to Task 2 — do not build the rest of the migration on an unproven foundation.

**Files:**
- Create (temporary, in a scratch directory outside the repo — use whatever scratch/temp directory is available in the current environment, e.g. the session's scratchpad directory if one is provided, or `/tmp/tina-11ty-smoketest` otherwise): a throwaway minimal project, NOT inside the `uitvaartendan` repo. This is a spike — nothing here gets committed.

**Interfaces:** None — this task produces no code any later task depends on. It only produces a pass/fail decision.

- [ ] **Step 1: Scaffold a throwaway test project**

```bash
mkdir -p /tmp/tina-11ty-smoketest
cd /tmp/tina-11ty-smoketest
npm init -y
npm install @11ty/eleventy
npm install tinacms @tinacms/cli
```

(Substitute `/tmp/tina-11ty-smoketest` for your environment's actual scratch directory if `/tmp` isn't appropriate — the only requirement is that it's outside the `uitvaartendan` repo.)

Expected: all three installs complete with no errors. If `@tinacms/cli` fails to install or doesn't exist under that exact package name, run `npm search tinacms` and use whatever the current CLI package is actually called — note the real name for use in later tasks.

- [ ] **Step 2: Create a minimal Eleventy input file**

Create `index.njk`:

```njk
<!DOCTYPE html>
<html><body><h1>{{ test.message }}</h1></body></html>
```

- [ ] **Step 3: Create a minimal Tina schema pointing at a test data file**

Create `_data/test.json`:

```json
{
  "message": "hello from tina"
}
```

Create `tina/config.ts`:

```typescript
import { defineConfig } from "tinacms";

export default defineConfig({
  branch: "main",
  clientId: "",
  token: "",
  build: {
    outputDir: "admin",
    publicDir: "public",
  },
  schema: {
    collections: [
      {
        name: "test",
        label: "Test",
        path: "_data",
        format: "json",
        match: { include: "test" },
        fields: [{ type: "string", name: "message", label: "Message" }],
      },
    ],
  },
});
```

- [ ] **Step 4: Run the Tina admin build**

```bash
npx @tinacms/cli build
```

Expected: completes without error, produces an `admin/` directory (per `outputDir` above) containing static files (HTML/JS/CSS — check with `ls -la admin/`). Note exactly what got generated.

If this command doesn't exist or errors with "unknown command," check `npx @tinacms/cli --help` for the actual build subcommand name and use that instead — record the correct command for use in Task 8's GitHub Actions workflow.

- [ ] **Step 5: Run the Eleventy build**

```bash
npx @11ty/eleventy
```

Expected: completes without error, produces `_site/index.html` containing the rendered `<h1>hello from tina</h1>`.

- [ ] **Step 6: Verify the output requires no running server**

```bash
grep -rl "output.*server\|serverless\|edge-function" admin/ 2>/dev/null
find admin/ _site/ -name "*.json" -exec grep -l "runtime" {} \; 2>/dev/null
```

Then serve the combined output as pure static files and confirm it works with no Node process other than the static file server itself:

```bash
npx serve -l 5050 _site &
sleep 2
curl -s http://localhost:5050/ | grep "hello from tina"
kill %1
```

Expected: the `curl` output contains "hello from tina" — proving `_site/` is servable as plain static files with nothing but a dumb file server.

- [ ] **Step 7: Decide pass/fail**

**Pass** if: Step 4 produced a static `admin/` directory, Step 5 produced a static `_site/`, and Step 6's `curl` succeeded through a plain static file server with no Tina/Node process running behind it (other than the throwaway `serve` process itself, which is just serving files, not executing any app logic).

**Fail** if: any step required a persistent Node/Tina process to actually answer requests (not just to build), or produced output referencing server-only runtime APIs.

If **fail**: stop here. Report back exactly what broke and what the error/behavior was — do not proceed to Task 2. The migration needs a different approach (e.g., dropping Tina Cloud auth and using the bot-GitHub-account approach from earlier instead).

If **pass**: delete the scratch project (`rm -rf` the smoke-test directory — it was never part of the repo) and proceed to Task 2.

---

## Task 2: Tina Cloud project setup (manual, user-driven)

**This task cannot be scripted or executed by an agent.** It requires the user's own browser session to sign up for and configure Tina Cloud and GitHub's OAuth authorization screen. Present these steps to the user and wait for the values needed in later tasks (Client ID, Token) before proceeding to Task 4.

**Files:** None yet — this task only produces credentials used in Task 4 and Task 8.

- [ ] **Step 1 (user): Create a Tina Cloud project**

Go to `app.tina.io`, sign up or log in (using the repo owner's existing account is fine here — this is the project *owner* account, separate from Annabelle's *editor* account created in Step 3), create a new project, and connect it to the `AscendClientDownload/uitvaartendan` GitHub repository via Tina's GitHub App (this authorizes Tina Cloud to read/write that one repo — not a personal access token embedded in the CMS).

- [ ] **Step 2 (user): Copy the Client ID and Token**

Tina Cloud's project overview page shows a **Client ID** and a **Read Only Token** (sometimes called a content token). Copy both — these get committed into `tina/config.ts` in Task 4 (they're safe to commit; they're read-only content-fetch credentials, not write credentials) and set as GitHub Actions secrets in Task 8.

- [ ] **Step 3 (user): Invite Annabelle as an editor**

In the Tina Cloud project's team/members settings, invite Annabelle by email as an editor. She accepts the invite and sets her own password — this account is entirely separate from any GitHub account.

- [ ] **Step 4: Add the Client ID and Token as GitHub repo secrets**

Once the user provides the Client ID and Token values from Step 2, run (agent-executable):

```bash
cd "c:/Users/Micha/source/repos/uitvaartendan"
gh secret set TINA_CLIENT_ID --body "<the client ID the user provided>"
gh secret set TINA_TOKEN --body "<the token the user provided>"
```

Expected: both commands print `Set secret TINA_CLIENT_ID for AscendClientDownload/uitvaartendan` (and same for TINA_TOKEN).

- [ ] **Step 5: Verify secrets are set**

```bash
gh secret list --repo AscendClientDownload/uitvaartendan
```

Expected: output includes both `TINA_CLIENT_ID` and `TINA_TOKEN` in the list (values are never shown, just confirms they exist).

---

## Task 3: Eleventy scaffold + content data migration

**Files:**
- Create: `.eleventy.js`
- Create: `src/_data/site.json`
- Create: `src/index.njk` (placeholder, replaced fully in Task 6)
- Modify: `package.json`

**Interfaces:**
- Produces: global Nunjucks variable `site` (from `src/_data/site.json`), available in every template without importing. Produces global variable `year` (current year, computed at build time). Eleventy config: input dir `src`, output dir `_site`, includes dir `_includes`, data dir `_data`.

- [ ] **Step 1: Install Eleventy and remove the now-unneeded YAML parser**

```bash
cd "c:/Users/Micha/source/repos/uitvaartendan"
npm install @11ty/eleventy
npm uninstall js-yaml
```

- [ ] **Step 2: Create `.eleventy.js`**

```javascript
module.exports = function (eleventyConfig) {
  eleventyConfig.addPassthroughCopy({
    "style.css": "style.css",
    "images": "images",
    "robots.txt": "robots.txt",
    "sitemap.xml": "sitemap.xml",
    "CNAME": "CNAME",
    "src/assets": "assets",
  });

  eleventyConfig.addGlobalData("year", () => new Date().getFullYear());

  return {
    dir: {
      input: "src",
      output: "_site",
      includes: "_includes",
      data: "_data",
    },
  };
};
```

- [ ] **Step 3: Create `src/_data/site.json` from the current `content.yml`**

This is a direct, literal translation of `content.yml`'s current content (read `content.yml` in the repo to confirm it still matches this exactly before copying — if it's been edited since this plan was written, use the live file's values instead):

```json
{
  "bedrijf": {
    "naam": "Uitvaart en dan?",
    "slogan": "Praktische begeleiding voor nabestaanden",
    "telefoon": "06-19421856",
    "telefoon_href": "0619421856",
    "email": "info@uitvaartendan.nl",
    "straat": "Riddersborch 5",
    "postcode_plaats": "3992 BG Houten",
    "openingstijden": "Maandag t/m zondag, 09:00 – 21:00"
  },
  "navigatie": {
    "home": "Home",
    "over_ons": "Over mij",
    "diensten": "Diensten",
    "contact": "Contact",
    "cta_knop": "Neem contact op"
  },
  "home": {
    "hero_titel": "Je bent niet alleen,\nik loop met je mee.",
    "hero_ondertitel": "Praktische en persoonlijke ondersteuning voor nabestaanden, in de periode na een uitvaart.",
    "hero_knop": "Neem contact op",
    "intro_titel": "Na de uitvaart begint het pas",
    "intro_tekst": "Welkom bij Uitvaart en dan? Ontstaan vanuit liefde voor de mens en het ontzorgen in een kwetsbare periode. Na een overlijden is er veel te bieden op het gebied van emotionele ondersteuning — echter de praktische en vooral ook persoonlijke begeleiding ontbreekt als schakel na een uitvaart. Je bent niet alleen, ik loop met je mee.",
    "diensten_titel": "Hoe ik je kan helpen",
    "diensten_tekst": "Van het opstellen van een overzicht met alles wat geregeld moet worden, tot persoonlijke begeleiding bij elke stap. Ik bied duidelijke pakketten, aangepast aan wat jij op dit moment nodig hebt.",
    "diensten_knop": "Bekijk de diensten",
    "over_titel": "Over Annabelle",
    "over_tekst": "Ik ben Annabelle Zaal, 48 jaar, woonachtig in Houten. Vanuit mijn eigen ervaringen weet ik hoeveel er op nabestaanden afkomt nadat een uitvaart voorbij is — en hoe fijn het is om daarin niet alleen te staan.",
    "over_knop": "Lees meer over mij",
    "voor_wie_titel": "Ook voor organisaties",
    "voor_wie_tekst": "Naast particulieren werk ik ook samen met uitvaartorganisaties, notarissen, mantelzorgorganisaties, hospices, verpleegtehuizen en WMO-loketten die hun cliënten graag goed doorverwijzen.",
    "cta_titel": "Zullen we kennismaken?",
    "cta_tekst": "Neem dan contact op voor het plannen van een intakegesprek zodat we samen kunnen kijken wat er in jouw situatie nodig is.",
    "cta_knop": "Neem contact op"
  },
  "over_ons": {
    "titel": "Over mij",
    "intro": "Ik ben Annabelle Zaal, 48 jaar, en samen met mijn man en drie kinderen woon ik in Houten.",
    "paragraaf_1": "Vanuit mijn eigen ervaringen weet ik hoeveel er op nabestaanden afkomt nadat een uitvaart voorbij is. Naast verdriet en machteloosheid moeten er ook behoorlijk wat praktische zaken worden geregeld. Juist in die periode, waarin je eigenlijk alleen maar rust nodig hebt, wordt er veel van je gevraagd.",
    "paragraaf_2": "",
    "paragraaf_3": "",
    "missie_titel": "Waarom ik dit doe",
    "missie_tekst": "Het persoonlijke contact, de praktische organisatie en de mogelijkheid om alles aan te passen per situatie — dat is wat Uitvaart en dan? anders maakt. Ik help je, stap voor stap, in het tempo dat bij jou past.",
    "cta_titel": "Nieuwsgierig wat ik voor jou kan betekenen?",
    "cta_knop": "Neem contact op"
  },
  "diensten": {
    "titel": "Diensten",
    "intro": "Ik werk met duidelijke pakketten als basis, maar elke situatie is anders. Tijdens het intakegesprek brengen we samen in kaart wat er nodig is en wat het beste bij jouw situatie past.",
    "pakket_1": {
      "naam": "Basispakket",
      "prijs": "€ 395,-",
      "beschrijving": "Een overzichtelijk startpunt: samen brengen we in kaart wat er geregeld moet worden, en zet ik de eerste stappen met je uit.",
      "items": [
        "Intakegesprek (1 uur)",
        "Twee begeleidingsgesprekken van 2 uur",
        "Het opstellen van een persoonlijke actielijst",
        "Evaluatiegesprek (persoonlijk of telefonisch, half uur)"
      ]
    },
    "pakket_2": {
      "naam": "Uitgebreid pakket",
      "prijs": "€ 795,-",
      "beschrijving": "Van overzicht naar uitvoering: niet alleen een plan, maar ook praktische hulp om echt stappen te zetten. Ook geschikt om in te zetten voor ondersteunende diensten als het opruimen, sorteren en afvoeren van spullen.",
      "items": [
        "Intakegesprek (1 uur)",
        "Twee begeleidingsgesprekken van 2 uur",
        "Het opstellen van een persoonlijke actielijst",
        "2 extra afspraken van 2,5 uur",
        "Samen opruimen en organiseren",
        "Ondersteuning bij administratie",
        "Totaal 10,5 uur",
        "Evaluatiegesprek (persoonlijk of telefonisch, half uur)"
      ]
    },
    "op_maat_titel": "Altijd op maat",
    "op_maat_tekst": "Geen enkele situatie is hetzelfde. Naast de vaste pakketten is er altijd ruimte om onderdelen toe te voegen of aan te passen, zodat de begeleiding precies aansluit bij wat jij nodig hebt.",
    "doelgroepen_titel": "Ook voor professionals",
    "doelgroepen_tekst": "Bent u werkzaam bij een uitvaartorganisatie, notariskantoor, mantelzorgorganisatie, hospice, verpleeghuis of WMO-loket? Ik denk graag mee over hoe ik als vaste partner nabestaanden praktisch kan ondersteunen na een overlijden.",
    "cta_titel": "Benieuwd wat bij jou past?",
    "cta_tekst": "Neem contact op voor een vrijblijvend gesprek.",
    "cta_knop": "Neem contact op"
  },
  "contact": {
    "titel": "Contact",
    "intro": "Heb je een vraag of wil je kennismaken? Neem gerust contact op — telefonisch, per e-mail of via het formulier hieronder.",
    "formulier_titel": "Stuur een bericht",
    "label_naam": "Naam",
    "label_email": "E-mailadres",
    "label_telefoon": "Telefoonnummer",
    "label_bericht": "Bericht",
    "knop_verzenden": "Verstuur bericht",
    "gegevens_titel": "Contactgegevens",
    "kaart_titel": "Werkgebied"
  },
  "footer": {
    "tekst": "Praktische en persoonlijke begeleiding voor nabestaanden in Houten en omgeving.",
    "copyright": "Alle rechten voorbehouden."
  }
}
```

- [ ] **Step 4: Create a placeholder homepage to verify the data loads**

Create `src/index.njk`:

```njk
<!DOCTYPE html>
<html><body><h1>{{ site.home.hero_titel }}</h1></body></html>
```

- [ ] **Step 5: Update `package.json`**

```json
{
  "name": "uitvaartendan",
  "private": true,
  "scripts": {
    "build": "npx @tinacms/cli build && npx @11ty/eleventy",
    "dev": "npx tinacms dev -c \"npx @11ty/eleventy --serve\""
  },
  "dependencies": {
    "@11ty/eleventy": "^3.0.0",
    "tinacms": "^2.0.0",
    "@tinacms/cli": "^1.0.0"
  }
}
```

Adjust the exact `@tinacms/cli` and `tinacms` package names/versions if Task 1's Step 1 discovered different real package names — use whatever actually installed successfully there.

- [ ] **Step 6: Run the Eleventy build and verify**

```bash
cd "c:/Users/Micha/source/repos/uitvaartendan"
npx @11ty/eleventy
cat _site/index.html
```

Expected: output contains `<h1>Je bent niet alleen,\nik loop met je mee.</h1>` (the literal `\n` will render as an actual newline character in the HTML source, matching how `hero_titel` is stored — this gets handled properly once Task 6 wires up the real hero markup with the `white-space: pre-line` CSS already in `style.css`).

- [ ] **Step 7: Commit**

```bash
git add .eleventy.js src/_data/site.json src/index.njk package.json package-lock.json
git commit -m "Scaffold Eleventy and migrate content.yml data to src/_data/site.json"
```

---

## Task 4: Tina schema

**Files:**
- Create: `tina/config.ts`

**Interfaces:**
- Consumes: `src/_data/site.json` (Task 3) as the file it manages.
- Produces: the Tina admin form definition — every field listed here must exist for Task 2's Annabelle-editor account to actually see and edit it.

- [ ] **Step 1: Create `tina/config.ts`**

```typescript
import { defineConfig } from "tinacms";

export default defineConfig({
  branch: "main",
  clientId: process.env.TINA_CLIENT_ID,
  token: process.env.TINA_TOKEN,
  build: {
    outputDir: "admin",
    publicDir: "public",
  },
  schema: {
    collections: [
      {
        name: "site",
        label: "Website teksten",
        path: "src/_data",
        format: "json",
        match: {
          include: "site",
        },
        ui: {
          allowedActions: {
            create: false,
            delete: false,
          },
        },
        fields: [
          {
            type: "object",
            name: "bedrijf",
            label: "Bedrijfsgegevens",
            fields: [
              { type: "string", name: "naam", label: "Bedrijfsnaam" },
              { type: "string", name: "slogan", label: "Slogan" },
              { type: "string", name: "telefoon", label: "Telefoonnummer (zoals getoond, bv. 06-19421856)" },
              { type: "string", name: "telefoon_href", label: "Telefoonnummer (voor de belknop, alleen cijfers)" },
              { type: "string", name: "email", label: "E-mailadres" },
              { type: "string", name: "straat", label: "Straat en huisnummer" },
              { type: "string", name: "postcode_plaats", label: "Postcode en plaats" },
              { type: "string", name: "openingstijden", label: "Openingstijden" },
            ],
          },
          {
            type: "object",
            name: "navigatie",
            label: "Menu (bovenin de website)",
            fields: [
              { type: "string", name: "home", label: "\"Home\"" },
              { type: "string", name: "over_ons", label: "\"Over mij\"" },
              { type: "string", name: "diensten", label: "\"Diensten\"" },
              { type: "string", name: "contact", label: "\"Contact\"" },
              { type: "string", name: "cta_knop", label: "Knop rechtsboven" },
            ],
          },
          {
            type: "object",
            name: "home",
            label: "Home",
            fields: [
              { type: "string", name: "hero_titel", label: "Hoofdtitel (hero)" },
              { type: "string", name: "hero_ondertitel", label: "Ondertitel (hero)", ui: { component: "textarea" } },
              { type: "string", name: "hero_knop", label: "Knoptekst (hero)" },
              { type: "string", name: "intro_titel", label: "Titel \"Welkom\"-sectie" },
              { type: "string", name: "intro_tekst", label: "Tekst \"Welkom\"-sectie", ui: { component: "textarea" } },
              { type: "string", name: "diensten_titel", label: "Titel \"Diensten\"-sectie" },
              { type: "string", name: "diensten_tekst", label: "Tekst \"Diensten\"-sectie", ui: { component: "textarea" } },
              { type: "string", name: "diensten_knop", label: "Knoptekst \"Diensten\"-sectie" },
              { type: "string", name: "over_titel", label: "Titel \"Over Annabelle\"-sectie" },
              { type: "string", name: "over_tekst", label: "Tekst \"Over Annabelle\"-sectie", ui: { component: "textarea" } },
              { type: "string", name: "over_knop", label: "Knoptekst \"Over Annabelle\"-sectie" },
              { type: "string", name: "voor_wie_titel", label: "Titel \"Ook voor organisaties\"" },
              { type: "string", name: "voor_wie_tekst", label: "Tekst \"Ook voor organisaties\"", ui: { component: "textarea" } },
              { type: "string", name: "cta_titel", label: "Titel afsluitende oproep" },
              { type: "string", name: "cta_tekst", label: "Tekst afsluitende oproep", ui: { component: "textarea" } },
              { type: "string", name: "cta_knop", label: "Knoptekst afsluitende oproep" },
            ],
          },
          {
            type: "object",
            name: "over_ons",
            label: "Over mij",
            fields: [
              { type: "string", name: "titel", label: "Titel" },
              { type: "string", name: "intro", label: "Introzin", ui: { component: "textarea" } },
              { type: "string", name: "paragraaf_1", label: "Paragraaf 1", ui: { component: "textarea" } },
              { type: "string", name: "paragraaf_2", label: "Paragraaf 2", ui: { component: "textarea" } },
              { type: "string", name: "paragraaf_3", label: "Paragraaf 3", ui: { component: "textarea" } },
              { type: "string", name: "missie_titel", label: "Titel missie" },
              { type: "string", name: "missie_tekst", label: "Tekst missie", ui: { component: "textarea" } },
              { type: "string", name: "cta_titel", label: "Titel afsluitende oproep" },
              { type: "string", name: "cta_knop", label: "Knoptekst afsluitende oproep" },
            ],
          },
          {
            type: "object",
            name: "diensten",
            label: "Diensten",
            fields: [
              { type: "string", name: "titel", label: "Titel" },
              { type: "string", name: "intro", label: "Introzin", ui: { component: "textarea" } },
              {
                type: "object",
                name: "pakket_1",
                label: "Pakket 1",
                fields: [
                  { type: "string", name: "naam", label: "Naam" },
                  { type: "string", name: "prijs", label: "Prijs" },
                  { type: "string", name: "beschrijving", label: "Beschrijving", ui: { component: "textarea" } },
                  { type: "string", name: "items", label: "Onderdelen van dit pakket", list: true },
                ],
              },
              {
                type: "object",
                name: "pakket_2",
                label: "Pakket 2",
                fields: [
                  { type: "string", name: "naam", label: "Naam" },
                  { type: "string", name: "prijs", label: "Prijs" },
                  { type: "string", name: "beschrijving", label: "Beschrijving", ui: { component: "textarea" } },
                  { type: "string", name: "items", label: "Onderdelen van dit pakket", list: true },
                ],
              },
              { type: "string", name: "op_maat_titel", label: "Titel \"Altijd op maat\"" },
              { type: "string", name: "op_maat_tekst", label: "Tekst \"Altijd op maat\"", ui: { component: "textarea" } },
              { type: "string", name: "doelgroepen_titel", label: "Titel \"Ook voor professionals\"" },
              { type: "string", name: "doelgroepen_tekst", label: "Tekst \"Ook voor professionals\"", ui: { component: "textarea" } },
              { type: "string", name: "cta_titel", label: "Titel afsluitende oproep" },
              { type: "string", name: "cta_tekst", label: "Tekst afsluitende oproep", ui: { component: "textarea" } },
              { type: "string", name: "cta_knop", label: "Knoptekst afsluitende oproep" },
            ],
          },
          {
            type: "object",
            name: "contact",
            label: "Contact",
            fields: [
              { type: "string", name: "titel", label: "Titel" },
              { type: "string", name: "intro", label: "Introzin", ui: { component: "textarea" } },
              { type: "string", name: "formulier_titel", label: "Titel formulier" },
              { type: "string", name: "label_naam", label: "Label \"Naam\"-veld" },
              { type: "string", name: "label_email", label: "Label \"E-mailadres\"-veld" },
              { type: "string", name: "label_telefoon", label: "Label \"Telefoonnummer\"-veld" },
              { type: "string", name: "label_bericht", label: "Label \"Bericht\"-veld" },
              { type: "string", name: "knop_verzenden", label: "Knoptekst versturen" },
              { type: "string", name: "gegevens_titel", label: "Titel \"Contactgegevens\"" },
              { type: "string", name: "kaart_titel", label: "Titel kaart" },
            ],
          },
          {
            type: "object",
            name: "footer",
            label: "Footer",
            fields: [
              { type: "string", name: "tekst", label: "Tekst", ui: { component: "textarea" } },
              { type: "string", name: "copyright", label: "Auteursrecht-tekst" },
            ],
          },
        ],
      },
    ],
  },
});
```

- [ ] **Step 2: Set local environment variables and run the Tina admin build**

```bash
cd "c:/Users/Micha/source/repos/uitvaartendan"
export TINA_CLIENT_ID="<value from Task 2>"
export TINA_TOKEN="<value from Task 2>"
npx @tinacms/cli build
```

Expected: completes with no schema errors, produces `admin/` with a static bundle.

- [ ] **Step 3: Manually verify the admin form**

```bash
npx @tinacms/cli dev
```

Open the local URL it prints (typically `http://localhost:3000/admin/index.html` or similar — use whatever it actually prints), log in with the Tina Cloud account from Task 2, and confirm every section from Step 1 (Bedrijfsgegevens, Menu, Home, Over mij, Diensten with both pakketten and their item lists, Contact, Footer) appears with the correct current values from `src/_data/site.json`. Stop the dev server after confirming (Ctrl+C).

- [ ] **Step 4: Commit**

```bash
git add tina/config.ts
git commit -m "Add Tina schema matching admin/config.yml's field set"
```

---

## Task 5: Shared layout, nav, footer, and site interactivity script

**Files:**
- Create: `src/_includes/base.njk`
- Create: `src/_includes/nav.njk`
- Create: `src/_includes/footer.njk`
- Create: `src/assets/site.js`

**Interfaces:**
- Consumes: global `site` data (Task 3), global `year` (Task 3's `.eleventy.js`).
- Produces: `base.njk` layout consumed by every page template (Task 6, Task 7) via front-matter `layout: base.njk`, expecting front-matter variables `title`, `description`, `ogTitle` (optional), `ogDescription` (optional), `canonical` (optional), `isHome` (optional boolean).

- [ ] **Step 1: Create `src/_includes/nav.njk`**

```njk
{% set prefix = "" if page.url == "/" else "/" %}
<div class="nav-inner">
  <a href="{{ prefix }}#home" class="nav-logo">
    <img src="/images/logo-icon.png" alt="" class="nav-logo-icon" width="40" height="40">
    <span>{{ site.bedrijf.naam }}</span>
  </a>
  <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navMenu" aria-label="Menu openen">
    <span></span><span></span><span></span>
  </button>
  <nav id="navMenu" class="nav-menu">
    <ul class="nav-list">
      <li><a href="{{ prefix }}#home" class="nav-link" data-section="#home">{{ site.navigatie.home }}</a></li>
      <li><a href="{{ prefix }}#over-ons" class="nav-link" data-section="#over-ons">{{ site.navigatie.over_ons }}</a></li>
      <li><a href="{{ prefix }}#diensten" class="nav-link" data-section="#diensten">{{ site.navigatie.diensten }}</a></li>
      <li><a href="{{ prefix }}#contact" class="nav-link" data-section="#contact">{{ site.navigatie.contact }}</a></li>
    </ul>
    <a href="{{ prefix }}#contact" class="btn btn-primary nav-cta">{{ site.navigatie.cta_knop }}</a>
  </nav>
</div>
```

- [ ] **Step 2: Create `src/_includes/footer.njk`**

```njk
<div class="footer-inner">
  <div class="footer-col">
    <p class="footer-logo"><img src="/images/logo-icon.png" alt="" width="32" height="32">{{ site.bedrijf.naam }}</p>
    <p class="footer-text">{{ site.footer.tekst }}</p>
  </div>
  <div class="footer-col">
    <p class="footer-heading">Contact</p>
    <p><a href="tel:{{ site.bedrijf.telefoon_href }}">{{ site.bedrijf.telefoon }}</a></p>
    <p><a href="mailto:{{ site.bedrijf.email }}">{{ site.bedrijf.email }}</a></p>
    <p>{{ site.bedrijf.straat }}<br>{{ site.bedrijf.postcode_plaats }}</p>
  </div>
  <div class="footer-col">
    <p class="footer-heading">Openingstijden</p>
    <p>{{ site.bedrijf.openingstijden }}</p>
  </div>
</div>
<div class="footer-bottom">
  <p>&copy; {{ year }} {{ site.bedrijf.naam }} — {{ site.footer.copyright }}</p>
  <ul class="footer-legal-links">
    <li><a href="/algemene-voorwaarden/">Algemene voorwaarden</a></li>
    <li><a href="/privacyverklaring/">Privacyverklaring</a></li>
    <li><a href="/cookieverklaring/">Cookieverklaring</a></li>
    <li><a href="/disclaimer/">Disclaimer</a></li>
  </ul>
</div>
```

- [ ] **Step 3: Create `src/_includes/base.njk`**

```njk
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; frame-src https://www.google.com; form-action 'self' https://formspree.io; base-uri 'self';">
  <title>{{ title }}</title>
  <meta name="description" content="{{ description }}">

  <meta property="og:title" content="{{ ogTitle or title }}">
  <meta property="og:description" content="{{ ogDescription or description }}">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="nl_NL">
  <meta property="og:url" content="https://www.uitvaartendan.nl{{ page.url }}">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="{{ ogTitle or title }}">
  <meta name="twitter:description" content="{{ ogDescription or description }}">

  <link rel="canonical" href="https://www.uitvaartendan.nl{{ canonical or page.url }}">

  <link rel="icon" type="image/png" href="/images/favicon.png">
  <meta name="theme-color" content="#8FA593">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/style.css">

  {% if isHome %}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "ProfessionalService",
    "name": "Uitvaart en dan?",
    "description": "Praktische en persoonlijke begeleiding voor nabestaanden na een uitvaart.",
    "url": "https://www.uitvaartendan.nl/",
    "telephone": "+31619421856",
    "email": "info@uitvaartendan.nl",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Riddersborch 5",
      "postalCode": "3992 BG",
      "addressLocality": "Houten",
      "addressCountry": "NL"
    },
    "openingHoursSpecification": {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
      "opens": "09:00",
      "closes": "21:00"
    },
    "areaServed": "Houten en omgeving",
    "priceRange": "€€"
  }
  </script>
  {% endif %}
</head>
<body>

  <a href="#main-content" class="skip-link">Ga direct naar de inhoud</a>

  <header class="site-header">
    {% include "nav.njk" %}
  </header>

  <main id="main-content" tabindex="-1">
    {{ content | safe }}
  </main>

  <footer class="site-footer">
    {% include "footer.njk" %}
  </footer>

  <div class="cookie-banner" id="cookieBanner" role="dialog" aria-live="polite" aria-label="Cookiemelding" hidden>
    <p>Deze website gebruikt cookies om goed te werken. Meer weten? Lees de <a href="/cookieverklaring/">cookieverklaring</a>.</p>
    <button type="button" class="btn btn-primary" id="cookieAccept">Akkoord</button>
  </div>

  <script src="/assets/site.js" defer></script>
</body>
</html>
```

- [ ] **Step 4: Create `src/assets/site.js`**

```javascript
function initNavToggle() {
  const toggle = document.getElementById("navToggle");
  const menu = document.getElementById("navMenu");
  if (!toggle || !menu) return;

  toggle.addEventListener("click", () => {
    const isOpen = menu.classList.toggle("nav-menu--open");
    toggle.setAttribute("aria-expanded", String(isOpen));
    toggle.classList.toggle("nav-toggle--open", isOpen);
  });

  menu.querySelectorAll(".nav-link").forEach((link) => {
    link.addEventListener("click", () => {
      menu.classList.remove("nav-menu--open");
      toggle.setAttribute("aria-expanded", "false");
      toggle.classList.remove("nav-toggle--open");
    });
  });
}

function initScrollSpy() {
  const navLinks = document.querySelectorAll(".nav-link[data-section]");
  if (!navLinks.length) return;

  const sections = Array.from(navLinks)
    .map((link) => document.querySelector(link.getAttribute("data-section")))
    .filter(Boolean);
  if (!sections.length) return;

  const setActive = (id) => {
    navLinks.forEach((link) => {
      const isActive = link.getAttribute("data-section") === `#${id}`;
      link.classList.toggle("nav-link--active", isActive);
      if (isActive) {
        link.setAttribute("aria-current", "true");
      } else {
        link.removeAttribute("aria-current");
      }
    });
  };

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          setActive(entry.target.id);
        }
      });
    },
    { rootMargin: "-40% 0px -55% 0px" }
  );

  sections.forEach((section) => observer.observe(section));
}

function initCookieBanner() {
  const banner = document.getElementById("cookieBanner");
  if (!banner) return;
  if (localStorage.getItem("cookieConsent") === "true") return;

  banner.hidden = false;
  requestAnimationFrame(() => banner.classList.add("cookie-banner--visible"));

  document.getElementById("cookieAccept").addEventListener("click", () => {
    localStorage.setItem("cookieConsent", "true");
    banner.classList.remove("cookie-banner--visible");
    banner.addEventListener("transitionend", () => { banner.hidden = true; }, { once: true });
  });
}

document.addEventListener("DOMContentLoaded", () => {
  initNavToggle();
  initScrollSpy();
  initCookieBanner();
});
```

- [ ] **Step 5: Update `src/index.njk` to use the new layout, verify build**

```njk
---
title: "Test"
description: "Test"
isHome: true
layout: base.njk
---
<h1>{{ site.home.hero_titel }}</h1>
```

```bash
cd "c:/Users/Micha/source/repos/uitvaartendan"
npx @11ty/eleventy
grep -c "nav-logo\|footer-inner\|cookieBanner" _site/index.html
```

Expected: count of 3 or more (confirms nav, footer, and cookie banner all rendered into the page).

- [ ] **Step 6: Commit**

```bash
git add src/_includes/base.njk src/_includes/nav.njk src/_includes/footer.njk src/assets/site.js src/index.njk
git commit -m "Add shared Eleventy layout, nav, footer, and site interactivity script"
```

---

## Task 6: Homepage template

**Files:**
- Modify: `src/index.njk` (replacing the placeholder from Task 5)

**Interfaces:**
- Consumes: `base.njk` layout (Task 5), global `site` data (Task 3).

- [ ] **Step 1: Replace `src/index.njk` with the full homepage**

```njk
---
title: "Uitvaart en dan? | Praktische begeleiding voor nabestaanden in Houten"
description: "Na een uitvaart begint het pas. Annabelle Zaal helpt nabestaanden stap voor stap met de praktische zaken die na een overlijden geregeld moeten worden."
ogTitle: "Uitvaart en dan? | Praktische begeleiding voor nabestaanden"
ogDescription: "Persoonlijke en praktische ondersteuning voor nabestaanden in de periode na een uitvaart."
canonical: "/"
isHome: true
layout: base.njk
---
<section class="hero" id="home">
  <div class="hero-bg" aria-hidden="true"></div>
  <div class="container hero-inner">
    <div class="hero-text">
      <h1>{{ site.home.hero_titel }}</h1>
      <p>{{ site.home.hero_ondertitel }}</p>
      <div class="hero-actions">
        <a href="#contact" class="btn btn-primary">{{ site.home.hero_knop }}</a>
        <a href="#diensten" class="btn btn-secondary">{{ site.navigatie.diensten }}</a>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Welkom</span>
      <h2>{{ site.home.intro_titel }}</h2>
      <p>{{ site.home.intro_tekst }}</p>
    </div>

    <div class="card-grid">
      <div class="card">
        <div class="card-icon" aria-hidden="true"></div>
        <h3>Overzicht</h3>
        <p>Samen breng ik in kaart wat er allemaal geregeld moet worden, zodat jij ruimte hebt om te kunnen rouwen en je gesteund voelt.</p>
      </div>
      <div class="card">
        <div class="card-icon" aria-hidden="true" style="background: var(--color-gold-light);"></div>
        <h3>Persoonlijk contact</h3>
        <p>Geen protocol, maar een luisterend oor en begeleiding die past bij jouw tempo en situatie.</p>
      </div>
      <div class="card">
        <div class="card-icon" aria-hidden="true" style="background: var(--color-lavender-light);"></div>
        <h3>Praktische hulp</h3>
        <p>Van instanties bellen tot papieren op orde brengen — ik help je met de stappen die nu nodig zijn.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section--alt" id="over-ons">
  <div class="container">
    <div class="split">
      <div class="split-image split-image--sage" aria-hidden="true"></div>
      <div class="split-text">
        <h2>{{ site.over_ons.titel }}</h2>
        <p>{{ site.over_ons.intro }}</p>
      </div>
    </div>

    <div class="prose" style="max-width: 720px; margin: 48px auto 0;">
      <p>{{ site.over_ons.paragraaf_1 }}</p>
      {% if site.over_ons.paragraaf_2 %}<p>{{ site.over_ons.paragraaf_2 }}</p>{% endif %}
      {% if site.over_ons.paragraaf_3 %}<p>{{ site.over_ons.paragraaf_3 }}</p>{% endif %}
    </div>

    <div class="section-header">
      <span class="eyebrow">Missie</span>
      <h2>{{ site.over_ons.missie_titel }}</h2>
      <p>{{ site.over_ons.missie_tekst }}</p>
    </div>

    <div class="tag-list" style="justify-content: center;">
      <span class="tag">Persoonlijk contact</span>
      <span class="tag">Praktische organisatie</span>
      <span class="tag">Aanpasbaar per situatie</span>
    </div>
  </div>
</section>

<section class="section" id="diensten">
  <div class="container">
    <div class="section-header">
      <h2>{{ site.diensten.titel }}</h2>
      <p>{{ site.diensten.intro }}</p>
    </div>

    <div class="pricing-grid">

      <div class="pricing-card">
        <span class="eyebrow">{{ site.diensten.pakket_1.naam }}</span>
        <h3 class="pricing-price">{{ site.diensten.pakket_1.prijs }}</h3>
        <p>{{ site.diensten.pakket_1.beschrijving }}</p>
        <ul class="pricing-list">
          {% for item in site.diensten.pakket_1.items %}
          <li>{{ item }}</li>
          {% endfor %}
        </ul>
        <a href="#contact" class="btn btn-primary btn-block">Neem contact op</a>
      </div>

      <div class="pricing-card">
        <span class="eyebrow">{{ site.diensten.pakket_2.naam }}</span>
        <h3 class="pricing-price">{{ site.diensten.pakket_2.prijs }}</h3>
        <p>{{ site.diensten.pakket_2.beschrijving }}</p>
        <ul class="pricing-list">
          {% for item in site.diensten.pakket_2.items %}
          <li>{{ item }}</li>
          {% endfor %}
        </ul>
        <a href="#contact" class="btn btn-secondary btn-block">Neem contact op</a>
      </div>

    </div>

    <div class="note-box">
      <h3>{{ site.diensten.op_maat_titel }}</h3>
      <p style="margin-top: 10px;">{{ site.diensten.op_maat_tekst }}</p>
    </div>
  </div>
</section>

<section class="section section--alt">
  <div class="container">
    <div class="section-header">
      <h2>{{ site.diensten.doelgroepen_titel }}</h2>
      <p>{{ site.diensten.doelgroepen_tekst }}</p>
    </div>

    <div class="tag-list" style="justify-content: center;">
      <span class="tag">Uitvaartorganisaties</span>
      <span class="tag">Notarissen</span>
      <span class="tag">Mantelzorgorganisaties</span>
      <span class="tag">Hospices</span>
      <span class="tag">Verpleegtehuizen</span>
      <span class="tag">WMO-loketten</span>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band">
      <h2>{{ site.home.cta_titel }}</h2>
      <p>{{ site.home.cta_tekst }}</p>
      <a href="#contact" class="btn btn-primary">{{ site.home.cta_knop }}</a>
    </div>
  </div>
</section>

<section class="section section--alt" id="contact">
  <div class="container">
    <div class="section-header" style="margin-bottom: 48px;">
      <h2>{{ site.contact.titel }}</h2>
      <p>{{ site.contact.intro }}</p>
    </div>

    <div class="contact-grid">

      <div>
        <h3 style="margin-bottom: 28px;">{{ site.contact.gegevens_titel }}</h3>

        <div class="contact-info-item">
          <div class="contact-info-icon" aria-hidden="true"></div>
          <div>
            <h3>Telefoon</h3>
            <a href="tel:{{ site.bedrijf.telefoon_href }}">{{ site.bedrijf.telefoon }}</a>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon" aria-hidden="true" style="background: var(--color-gold-light);"></div>
          <div>
            <h3>E-mail</h3>
            <a href="mailto:{{ site.bedrijf.email }}">{{ site.bedrijf.email }}</a>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon" aria-hidden="true" style="background: var(--color-lavender-light);"></div>
          <div>
            <h3>Adres</h3>
            <p><span>{{ site.bedrijf.straat }}</span><br><span>{{ site.bedrijf.postcode_plaats }}</span></p>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon" aria-hidden="true"></div>
          <div>
            <h3>Openingstijden</h3>
            <p>{{ site.bedrijf.openingstijden }}</p>
          </div>
        </div>

        <h3 style="margin-top: 40px;">{{ site.contact.kaart_titel }}</h3>
        <div class="map-embed">
          <iframe
            src="https://www.google.com/maps?q=Riddersborch+5,+3992+BG+Houten&z=10&output=embed"
            title="Kaart met het werkgebied van Uitvaart en dan?, rondom Houten"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>

      <div class="form-card">
        <h3 style="margin-bottom: 24px;">{{ site.contact.formulier_titel }}</h3>

        <form action="https://formspree.io/f/xdaqakjb" method="POST">

          <input type="hidden" name="_subject" value="Nieuw bericht via website Uitvaart en dan?">

          <input type="text" name="_gotcha" class="form-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

          <div class="form-group">
            <label for="naam">{{ site.contact.label_naam }}</label>
            <input type="text" id="naam" name="naam" required autocomplete="name">
          </div>

          <div class="form-group">
            <label for="email">{{ site.contact.label_email }}</label>
            <input type="email" id="email" name="email" required autocomplete="email">
          </div>

          <div class="form-group">
            <label for="telefoon">{{ site.contact.label_telefoon }}</label>
            <input type="tel" id="telefoon" name="telefoon" autocomplete="tel">
          </div>

          <div class="form-group">
            <label for="bericht">{{ site.contact.label_bericht }}</label>
            <textarea id="bericht" name="bericht" required></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-block">{{ site.contact.knop_verzenden }}</button>
          <p class="form-note">Je bericht komt rechtstreeks in de mailbox van Annabelle terecht. Ze reageert zo snel mogelijk.</p>
        </form>
      </div>

    </div>
  </div>
</section>
```

- [ ] **Step 2: Build and diff against the live site**

```bash
cd "c:/Users/Micha/source/repos/uitvaartendan"
npx @11ty/eleventy
npx serve -l 5051 _site &
sleep 2
curl -s http://localhost:5051/ -o /tmp/new-homepage.html
curl -s https://uitvaartendan.nl/ -o /tmp/live-homepage.html
kill %1
```

Open both `/tmp/new-homepage.html` (rendered locally) and compare against the live site in a browser (`npx serve -l 5051 _site` then visit `http://localhost:5051/`, side by side with `https://uitvaartendan.nl/`). Confirm every section's text, the pricing package items (both packages, all items including "Totaal 10,5 uur" and the evaluatiegesprek line), the tags, and the contact form all match exactly.

- [ ] **Step 3: Commit**

```bash
git add src/index.njk
git commit -m "Build full homepage template in Eleventy, matching current live site"
```

---

## Task 7: Legal pages

**Files:**
- Create: `src/algemene-voorwaarden.njk`
- Create: `src/privacyverklaring.njk`
- Create: `src/cookieverklaring.njk`
- Create: `src/disclaimer.njk`

**Interfaces:**
- Consumes: `base.njk` layout (Task 5). These pages do **not** read from `site` data beyond what `base.njk`/`nav.njk`/`footer.njk` already use — their body content is static.

- [ ] **Step 1: Create `src/algemene-voorwaarden.njk`**

```njk
---
title: "Algemene Voorwaarden | Uitvaart en dan?"
description: "Algemene voorwaarden van Uitvaart en dan?, praktische begeleiding voor nabestaanden in Houten."
canonical: "/algemene-voorwaarden"
layout: base.njk
---
<section class="section">
  <div class="container">
    <div class="section-header">
      <h1>Algemene Voorwaarden – Uitvaart en dan?</h1>
    </div>

    <div class="prose" style="max-width: 720px; margin: 0 auto;">

      <h2>Artikel 1 – Onderneming</h2>
      <ol>
        <li>Uitvaart en dan? is gevestigd te Houten en verleent praktische ondersteuning aan nabestaanden na een overlijden.</li>
        <li>Deze algemene voorwaarden zijn van toepassing op alle offertes, overeenkomsten en diensten van Uitvaart en dan?.</li>
      </ol>

      <h2>Artikel 2 – Diensten</h2>
      <ol>
        <li>De dienstverlening bestaat uit praktische begeleiding en ondersteuning, waaronder:
          <ul>
            <li>het aanbrengen van overzicht;</li>
            <li>het opstellen van een persoonlijke actielijst;</li>
            <li>hulp bij administratieve zaken;</li>
            <li>ondersteuning bij contacten met instanties;</li>
            <li>begeleiding bij de afhandeling van praktische zaken na een overlijden.</li>
          </ul>
        </li>
        <li>Uitvaart en dan? verleent geen juridisch, notarieel, fiscaal, financieel of psychologisch advies.</li>
        <li>Indien specialistische hulp wenselijk is, zal de opdrachtgever worden doorverwezen naar een geschikte professional.</li>
      </ol>

      <h2>Artikel 3 – Totstandkoming van de overeenkomst</h2>
      <ol>
        <li>Een overeenkomst komt tot stand zodra de opdrachtgever akkoord geeft op een offerte, pakket of losse opdracht.</li>
        <li>Mondelinge afspraken zijn eveneens bindend.</li>
      </ol>

      <h2>Artikel 4 – Tarieven</h2>
      <ol>
        <li>De actuele tarieven staan vermeld op de website, offerte of prijslijst.</li>
        <li>Uitvaart en dan? neemt deel aan de Kleineondernemersregeling (KOR). Daarom wordt geen btw in rekening gebracht.</li>
        <li>Eventuele reiskosten en reistijd worden vooraf duidelijk vermeld.</li>
        <li>Tariefswijzigingen hebben geen invloed op reeds overeengekomen opdrachten.</li>
      </ol>

      <h2>Artikel 5 – Betaling</h2>
      <ol>
        <li>Facturen dienen binnen 14 dagen na factuurdatum te worden voldaan.</li>
        <li>Bij overschrijding van de betalingstermijn mag Uitvaart en dan? de werkzaamheden tijdelijk opschorten totdat de betaling is ontvangen.</li>
      </ol>

      <h2>Artikel 6 – Afspraken verzetten of annuleren</h2>
      <ol>
        <li>Annuleren of verzetten kan kosteloos tot 24 uur vóór de afspraak.</li>
        <li>Bij annulering binnen 24 uur wordt 50% van het afgesproken tarief in rekening gebracht.</li>
        <li>Bij annulering op de dag van de afspraak of wanneer de opdrachtgever zonder bericht afwezig is, kan 100% van het afgesproken tarief worden berekend.</li>
        <li>Wanneer Uitvaart en dan? door ziekte of overmacht verhinderd is, wordt in overleg zo snel mogelijk een nieuwe afspraak gemaakt.</li>
      </ol>

      <h2>Artikel 7 – Uitvoering van de werkzaamheden</h2>
      <ol>
        <li>Uitvaart en dan? voert de werkzaamheden zorgvuldig en naar beste inzicht uit.</li>
        <li>Er wordt gewerkt op basis van een inspanningsverplichting; een bepaald resultaat kan niet worden gegarandeerd.</li>
        <li>De opdrachtgever blijft verantwoordelijk voor de uiteindelijke keuzes en beslissingen.</li>
      </ol>

      <h2>Artikel 8 – Verplichtingen van de opdrachtgever</h2>
      <ol>
        <li>De opdrachtgever verstrekt tijdig alle informatie die nodig is om de opdracht goed uit te voeren.</li>
        <li>Indien noodzakelijke informatie ontbreekt, kan de uitvoering van de opdracht worden uitgesteld.</li>
      </ol>

      <h2>Artikel 9 – Aansprakelijkheid</h2>
      <ol>
        <li>Uitvaart en dan? is uitsluitend aansprakelijk voor directe schade die het gevolg is van opzet of grove nalatigheid.</li>
        <li>Iedere aansprakelijkheid is beperkt tot maximaal het factuurbedrag van de betreffende opdracht.</li>
        <li>Uitvaart en dan? is niet aansprakelijk voor schade die ontstaat door besluiten van de opdrachtgever of door handelen van derden.</li>
      </ol>

      <h2>Artikel 10 – Vertrouwelijkheid en privacy</h2>
      <ol>
        <li>Alle persoonlijke informatie wordt vertrouwelijk behandeld.</li>
        <li>Persoonsgegevens worden uitsluitend gebruikt voor de uitvoering van de overeenkomst.</li>
        <li>Uitvaart en dan? handelt overeenkomstig de Algemene Verordening Gegevensbescherming (AVG).</li>
      </ol>

      <h2>Artikel 11 – Intellectueel eigendom</h2>
      <p>Alle door Uitvaart en dan? verstrekte documenten, actielijsten, formulieren en andere materialen blijven eigendom van Uitvaart en dan? en mogen niet zonder schriftelijke toestemming worden gekopieerd of commercieel worden gebruikt.</p>

      <h2>Artikel 12 – Klachten</h2>
      <ol>
        <li>Klachten dienen binnen 14 dagen na het ontstaan schriftelijk kenbaar te worden gemaakt.</li>
        <li>Uitvaart en dan? zal zich inspannen om de klacht in goed overleg op te lossen.</li>
      </ol>

      <h2>Artikel 13 – Toepasselijk recht</h2>
      <p>Op alle overeenkomsten is uitsluitend Nederlands recht van toepassing. Geschillen worden voorgelegd aan de bevoegde rechter in Nederland.</p>

    </div>
  </div>
</section>
```

- [ ] **Step 2: Create `src/privacyverklaring.njk`**

```njk
---
title: "Privacyverklaring | Uitvaart en dan?"
description: "Privacyverklaring van Uitvaart en dan?: welke persoonsgegevens worden verwerkt, waarom, en welke rechten je hebt."
canonical: "/privacyverklaring"
layout: base.njk
---
<section class="section">
  <div class="container">
    <div class="section-header">
      <h1>Privacyverklaring – Uitvaart en dan?</h1>
    </div>

    <div class="prose" style="max-width: 720px; margin: 0 auto;">

      <p>Uitvaart en dan? hecht veel waarde aan de bescherming van jouw persoonsgegevens. In deze privacyverklaring leg ik uit welke gegevens ik verzamel, waarom ik dat doe en hoe ik daarmee omga.</p>

      <h2>Wie ben ik?</h2>
      <p>Uitvaart en dan? is gevestigd in Houten en biedt praktische ondersteuning aan nabestaanden na een overlijden.</p>

      <h2>Welke gegevens verwerk ik?</h2>
      <p>Ik verwerk alleen persoonsgegevens die nodig zijn om mijn dienstverlening uit te voeren, zoals:</p>
      <ul>
        <li>naam;</li>
        <li>adres;</li>
        <li>telefoonnummer;</li>
        <li>e-mailadres;</li>
        <li>factuurgegevens;</li>
        <li>gegevens die je vrijwillig met mij deelt tijdens onze samenwerking.</li>
      </ul>
      <p>Ik verwerk geen persoonsgegevens die niet noodzakelijk zijn voor mijn dienstverlening.</p>

      <h2>Waarom verwerk ik jouw gegevens?</h2>
      <p>Ik gebruik jouw gegevens uitsluitend voor:</p>
      <ul>
        <li>het uitvoeren van onze overeenkomst;</li>
        <li>het maken van afspraken;</li>
        <li>het opstellen en versturen van offertes en facturen;</li>
        <li>communicatie over de dienstverlening;</li>
        <li>het voldoen aan wettelijke administratieve verplichtingen.</li>
      </ul>

      <h2>Bewaartermijn</h2>
      <p>Persoonsgegevens worden niet langer bewaard dan noodzakelijk is. Gegevens die onderdeel zijn van mijn financiële administratie bewaar ik conform de wettelijke bewaartermijn van minimaal zeven jaar.</p>

      <h2>Delen met derden</h2>
      <p>Ik verkoop jouw persoonsgegevens nooit aan derden.</p>
      <p>Gegevens worden uitsluitend gedeeld wanneer dit noodzakelijk is voor de uitvoering van de overeenkomst of wanneer de wet mij daartoe verplicht.</p>

      <h2>Beveiliging</h2>
      <p>Ik neem passende technische en organisatorische maatregelen om jouw persoonsgegevens te beschermen tegen verlies of onbevoegde toegang.</p>

      <h2>Jouw rechten</h2>
      <p>Je hebt het recht om:</p>
      <ul>
        <li>jouw gegevens in te zien;</li>
        <li>gegevens te laten corrigeren;</li>
        <li>gegevens te laten verwijderen (voor zover wettelijk mogelijk);</li>
        <li>bezwaar te maken tegen verwerking;</li>
        <li>jouw gegevens over te dragen.</li>
      </ul>
      <p>Verzoeken kunnen worden ingediend via het contactadres van Uitvaart en dan?.</p>

      <h2>Klachten</h2>
      <p>Heb je een klacht over de verwerking van jouw persoonsgegevens, dan hoor ik dat graag. Je hebt daarnaast het recht een klacht in te dienen bij de Autoriteit Persoonsgegevens.</p>

      <h2>Wijzigingen</h2>
      <p>Uitvaart en dan? kan deze privacyverklaring aanpassen wanneer wetgeving of de dienstverlening daarom vraagt.</p>

    </div>
  </div>
</section>
```

- [ ] **Step 3: Create `src/cookieverklaring.njk`**

```njk
---
title: "Cookieverklaring | Uitvaart en dan?"
description: "Cookieverklaring van Uitvaart en dan?: welke cookies deze website gebruikt en waarom."
canonical: "/cookieverklaring"
layout: base.njk
---
<section class="section">
  <div class="container">
    <div class="section-header">
      <h1>Cookieverklaring – Uitvaart en dan?</h1>
    </div>

    <div class="prose" style="max-width: 720px; margin: 0 auto;">

      <p>Op de website van Uitvaart en dan? worden cookies gebruikt om de website goed te laten functioneren en de gebruikservaring te verbeteren.</p>

      <h2>Wat zijn cookies?</h2>
      <p>Cookies zijn kleine tekstbestanden die tijdens een bezoek aan de website op jouw apparaat worden geplaatst.</p>

      <h2>Functionele cookies</h2>
      <p>Deze cookies zijn noodzakelijk om de website goed te laten werken. Hiervoor is geen toestemming nodig.</p>

      <h2>Analytische cookies</h2>
      <p>Wanneer Uitvaart en dan? gebruikmaakt van Google Analytics, worden analytische cookies gebruikt om inzicht te krijgen in het gebruik van de website.</p>
      <p>Google Analytics wordt, waar mogelijk, privacyvriendelijk ingesteld. IP-adressen worden geanonimiseerd en gegevens worden niet gebruikt voor gepersonaliseerde advertenties.</p>

      <h2>Marketingcookies</h2>
      <p>Momenteel maakt Uitvaart en dan? geen gebruik van marketing- of trackingcookies.</p>
      <p>Mocht dit in de toekomst veranderen, dan zal hiervoor vooraf toestemming worden gevraagd.</p>

      <h2>Cookies verwijderen</h2>
      <p>Je kunt cookies altijd verwijderen of blokkeren via de instellingen van jouw internetbrowser. Houd er rekening mee dat sommige onderdelen van de website hierdoor mogelijk minder goed functioneren.</p>

      <h2>Contact</h2>
      <p>Heb je vragen over deze cookieverklaring, neem dan contact op met Uitvaart en dan?.</p>

    </div>
  </div>
</section>
```

- [ ] **Step 4: Create `src/disclaimer.njk`**

```njk
---
title: "Disclaimer | Uitvaart en dan?"
description: "Disclaimer van Uitvaart en dan? over het gebruik van de informatie op deze website."
canonical: "/disclaimer"
layout: base.njk
---
<section class="section">
  <div class="container">
    <div class="section-header">
      <h1>Disclaimer – Uitvaart en dan?</h1>
    </div>

    <div class="prose" style="max-width: 720px; margin: 0 auto;">

      <p>De informatie op deze website is met de grootst mogelijke zorg samengesteld.</p>

      <p>Ondanks deze zorg kan Uitvaart en dan? niet garanderen dat alle informatie volledig, juist of actueel is.</p>

      <p>Aan de inhoud van deze website kunnen geen rechten worden ontleend.</p>

      <p>Uitvaart en dan? biedt uitsluitend praktische ondersteuning aan nabestaanden en verstrekt geen juridisch, fiscaal, financieel of notarieel advies.</p>

      <p>Beslissingen die bezoekers nemen op basis van informatie op deze website zijn volledig voor eigen rekening en risico.</p>

      <p>Alle teksten, foto's, logo's en andere inhoud op deze website zijn eigendom van Uitvaart en dan?, tenzij anders vermeld. Het is niet toegestaan deze zonder voorafgaande schriftelijke toestemming te kopiëren, publiceren of commercieel te gebruiken.</p>

      <p>Uitvaart en dan? behoudt zich het recht voor de inhoud van deze website op ieder moment te wijzigen.</p>

    </div>
  </div>
</section>
```

- [ ] **Step 5: Build and verify all 4 pages**

```bash
cd "c:/Users/Micha/source/repos/uitvaartendan"
npx @11ty/eleventy
ls _site/algemene-voorwaarden/index.html _site/privacyverklaring/index.html _site/cookieverklaring/index.html _site/disclaimer/index.html
grep -c "Artikel 13" _site/algemene-voorwaarden/index.html
grep -c "Autoriteit Persoonsgegevens" _site/privacyverklaring/index.html
grep -c "Google Analytics" _site/cookieverklaring/index.html
grep -c "notarieel advies" _site/disclaimer/index.html
```

Expected: all 4 `index.html` files exist, and each `grep -c` returns `1`.

- [ ] **Step 6: Commit**

```bash
git add src/algemene-voorwaarden.njk src/privacyverklaring.njk src/cookieverklaring.njk src/disclaimer.njk
git commit -m "Convert 4 legal pages to Eleventy templates"
```

---

## Task 8: GitHub Actions workflow, deploy, and end-to-end verification

**Files:**
- Modify: `.github/workflows/pages.yml`

**Interfaces:**
- Consumes: `TINA_CLIENT_ID` and `TINA_TOKEN` repo secrets (Task 2), `npm run build` script (Task 3).

- [ ] **Step 1: Update `.github/workflows/pages.yml`**

```yaml
name: Deploy to GitHub Pages

on:
  push:
    branches: [main]
  workflow_dispatch:

permissions:
  contents: read
  pages: write
  id-token: write

concurrency:
  group: pages
  cancel-in-progress: true

jobs:
  build:
    runs-on: ubuntu-latest
    env:
      TINA_CLIENT_ID: ${{ secrets.TINA_CLIENT_ID }}
      TINA_TOKEN: ${{ secrets.TINA_TOKEN }}
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 20
      - run: npm install
      - run: npm run build
      - uses: actions/upload-pages-artifact@v3
        with:
          path: _site

  deploy:
    needs: build
    runs-on: ubuntu-latest
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - id: deployment
        uses: actions/deploy-pages@v4
```

- [ ] **Step 2: Commit and push**

```bash
cd "c:/Users/Micha/source/repos/uitvaartendan"
git add .github/workflows/pages.yml
git commit -m "Update GitHub Actions build command for Eleventy + Tina admin build"
git push origin main
```

- [ ] **Step 3: Watch the deploy**

```bash
sleep 5
gh run list --repo AscendClientDownload/uitvaartendan --limit 1
```

Take the run ID from the output, then:

```bash
gh run watch <run-id> --repo AscendClientDownload/uitvaartendan --exit-status
```

Expected: both `build` and `deploy` jobs complete successfully (green checkmarks).

If the build fails on the `npm run build` step, read the error carefully — this is the first time the *real* Tina Cloud credentials and the *real* content data run through the full pipeline together, so a failure here is expected to be diagnosable from the error message (e.g., a schema/data mismatch between `tina/config.ts` and `src/_data/site.json`). Fix and re-push; do not proceed to Step 4 until the workflow is green.

- [ ] **Step 4: Verify the live public site is unchanged**

```bash
curl -sI https://uitvaartendan.nl/ | head -5
curl -s https://uitvaartendan.nl/ | grep -c "Je bent niet alleen"
curl -s https://uitvaartendan.nl/algemene-voorwaarden/ | grep -c "Artikel 13"
```

Expected: `200 OK`, and both `grep -c` commands return `1` or more.

- [ ] **Step 5: Verify Annabelle's admin access end-to-end**

This step needs Annabelle (or the user, on her behalf for this one test) to actually log in:

1. Go to `https://uitvaartendan.nl/admin/index.html`
2. Log in with the Tina Cloud editor account from Task 2
3. Confirm the form shows the current live content correctly
4. Make one small test edit (e.g. append " (test)" to a field that's easy to revert, like `footer.copyright`), save
5. Confirm a new commit appears on `main` (check `git log --oneline -3` after pulling, or check the GitHub repo's commit history) attributed to Annabelle's identity, not the repo owner's
6. Confirm GitHub Actions runs again and the live site updates within a couple of minutes
7. Revert the test edit the same way (through the admin, not by hand-editing the repo) to confirm a second save also works

- [ ] **Step 6: Report results**

Report back: whether Step 5's edit-save-rebuild cycle worked, and whether the resulting git commit was attributed to Annabelle's account rather than the repo owner's — this is the actual requirement this whole migration exists to satisfy.

---

## Task 9: Remove dead files

**Files:**
- Delete: `content.yml`, `content.js`, `nav.js`, `scripts/build-content.js`, `scripts/` (if empty after), `admin/config.yml`, `admin/index.html`, `admin/` (if empty after), `netlify.toml`, `_headers`, `_redirects`

**Interfaces:** None — purely removes files nothing else in the repo references anymore after Task 3–8.

- [ ] **Step 1: Confirm nothing still references the old files**

```bash
cd "c:/Users/Micha/source/repos/uitvaartendan"
grep -rl "content\.js\|nav\.js\|content\.yml" src/ .github/ .eleventy.js package.json 2>/dev/null
```

Expected: no output (empty result). If anything shows up, stop and fix that reference before deleting — do not delete a file something still points at.

- [ ] **Step 2: Remove the old build pipeline and admin**

```bash
git rm content.yml content.js nav.js
git rm -r scripts/
git rm -r admin/
git rm netlify.toml _headers _redirects
```

- [ ] **Step 3: Remove the old root-level HTML pages**

These are now superseded by the `src/*.njk` templates from Task 6 and Task 7 (Eleventy builds `_site/index.html` etc. fresh on every deploy):

```bash
git rm index.html algemene-voorwaarden.html privacyverklaring.html cookieverklaring.html disclaimer.html
```

- [ ] **Step 4: Verify the build still succeeds with these removed**

```bash
npm run build
ls _site/index.html _site/algemene-voorwaarden/index.html
```

Expected: build succeeds, both files exist (generated fresh from the `.njk` sources, not the deleted `.html` files).

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Remove dead files from the pre-Eleventy build (old HTML, content.js/nav.js, Sveltia admin, Netlify configs)"
git push origin main
```

- [ ] **Step 6: Confirm the deploy triggered by this push also succeeds**

```bash
sleep 5
gh run list --repo AscendClientDownload/uitvaartendan --limit 1
```

Take the run ID and:

```bash
gh run watch <run-id> --repo AscendClientDownload/uitvaartendan --exit-status
```

Expected: green. Then re-run Task 8 Step 4's verification curls once more to confirm the live site is still correct after removing the old files.

---

## Self-Review Notes

**Spec coverage:**
- "Non-goal: visual live editing" — Task 1's smoke test and the whole plan's architecture (Eleventy, no `tinaField()`, no islands) honors this.
- "Architecture" (framework, homepage, legal pages, nav/footer, content storage, hosting, editing flow) — Tasks 3, 5, 6, 7, 8.
- "Content model" (every field) — Task 3 Step 3 (data) and Task 4 (schema) both enumerate every field from the spec's list; cross-checked field-by-field against `admin/config.yml`'s existing structure.
- "Setup & authentication" — Task 2.
- "Deployment pipeline" — Task 8.
- "Error handling" — inherent in the architecture (static build, GitHub Pages' existing last-good-deploy behavior); not a separate task since there's no new code path to test beyond what GitHub Pages already does.
- "Testing / verification plan" including the smoke test — Task 1 (smoke test), Task 6/7 (visual comparison), Task 8 (deployed end-to-end + real edit cycle).

**Placeholder scan:** no TBD/TODO; every code block is complete, runnable content, not a description of what to write.

**Type/naming consistency:** the global data variable `site` (from `src/_data/site.json`) is used identically across Task 5 (`base.njk`, `nav.njk`, `footer.njk`), Task 6 (`index.njk`), and Task 4 (Tina's `collections[0].name: "site"`, matching the filename `site.json` Tina manages). Field names (`hero_titel`, `pakket_1.items`, etc.) match 1:1 between Task 3's data file, Task 4's schema, and Task 6's template.

**Gap found and fixed during review:** the original `nav.js` also handled the mobile menu toggle and scroll-spy as runtime behavior, not just templating — Task 5 Step 4 (`site.js`) explicitly preserves this rather than dropping it, since it's real interactive functionality, not content.
