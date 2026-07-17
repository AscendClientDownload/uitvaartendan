# Migrate site to Astro + TinaCMS (Tina Cloud) — design

Date: 2026-07-17
Status: approved by user, not yet implemented

## Why

`/admin` (Decap CMS, then Sveltia CMS) authenticated via Netlify Identity /
GitHub personal access tokens. Both broke or created an identity problem
once the site moved from Netlify to GitHub Pages:

- Netlify Identity stopped working outright once Netlify was no longer
  hosting the site ("Unable to access identity settings").
- Switching to Sveltia CMS with a GitHub access-token login worked, but
  the token is always tied to whoever generated it — the user didn't want
  Annabelle's edits (or a token capable of writing to the repo) tied to
  his own personal GitHub account, and didn't want to create a separate
  throwaway GitHub account either.

This is the third CMS iteration on this site (see
`2026-07-11-admin-panel-design.md` for the original custom-PHP plan,
superseded by WonderCMS; `2026-07-12-wondercms-integration.md` for why
WonderCMS failed on two real hosts; `2026-07-12-netlify-decap-cms.md` for
the Decap+Netlify version this replaces). The hard constraint has held
across all three: **v1 scope is text only** — photos, colors, and layout
are not exposed to the editor.

TinaCMS via Tina Cloud solves the identity problem directly: Annabelle
gets her own Tina Cloud account (her own email+password), separate from
GitHub entirely. Tina Cloud handles the GitHub connection on the backend
via its own GitHub App, installed once against the repo owner's account —
not a personal access token embedded in the CMS itself.

## What was explicitly chosen, and why it costs more than the previous setup

TinaCMS offers two editing experiences:
1. A plain form-based admin screen (low setup cost, equivalent to what
   Decap/Sveltia already provided).
2. "Visual" live editing — Annabelle edits text directly on a live preview
   of the real page, seeing the site update as she types.

The user explicitly chose (2), the visual editor, despite it requiring an
editing bridge (`@tinacms/astro`) wired into every page template — a real
rewrite, not a config swap. This was surfaced and confirmed before
committing to the larger scope.

TinaCMS's visual editing requires a JS framework (no plain-HTML path
exists for it). Astro was chosen as that framework because it's the
framework TinaCMS's own docs treat as first-class for non-React visual
editing (`@tinacms/astro` renders static HTML by default, with editing
capability layered on top only inside the Tina iframe — the public site
stays static, no React shipped to visitors).

## Architecture

- **Homepage** (`index.html` → `src/pages/index.astro`): rebuilt as Astro
  components, one per section (Hero, Welkom, OverMij, Diensten, Contact,
  etc.), each field wrapped with `tinaField()` markers so Tina's visual
  overlay can target it.
- **Legal pages** (`algemene-voorwaarden.html`, `privacyverklaring.html`,
  `cookieverklaring.html`, `disclaimer.html`): converted to plain Astro
  pages for framework consistency, but **not** wired into Tina — same
  static text as today, matching current scope (these were never in
  `admin/config.yml` either).
- **Nav & footer** (`nav.js`): becomes a shared Astro layout component,
  rendered at build time instead of injected by client-side JS at
  runtime. Same visual output, less runtime JS shipped to visitors.
- **Content storage**: `content/home.json` replaces `content.yml` as the
  source of truth — same shape/fields as `admin/config.yml` today (see
  Content model below), stored as a single Tina "document" (not a folder
  of many entries — there is and only ever will be this one file).
- **Hosting**: unchanged — GitHub Pages via the existing
  `.github/workflows/pages.yml`. Only the build command changes (see
  Deployment pipeline below). Domain, DNS, and SSL are untouched.
- **Editing flow**: Annabelle logs into Tina Cloud with her own
  email+password → opens the site's `/admin` → sees the real homepage
  rendered live → clicks text, edits in place → saves → Tina Cloud commits
  to `main` → GitHub Actions rebuilds → live in roughly 1-2 minutes.

## Content model

Directly maps every field currently in `admin/config.yml` — nothing
added, nothing removed:

- `bedrijf` — bedrijfsnaam, slogan, telefoon, telefoon_href, e-mail,
  straat, postcode_plaats, openingstijden
- `navigatie` — menu labels (home, over_ons, diensten, contact) + CTA-knop
- `home` — hero titel/ondertitel/knop, welkomsttitel/tekst,
  diensten-titel/tekst/knop, over-titel/tekst/knop, voor-wie-titel/tekst,
  cta-titel/tekst/knop
- `over_ons` — titel, intro, paragraaf_1/2/3, missie_titel/tekst,
  cta_titel/knop
- `diensten` — titel, intro, pakket_1 and pakket_2 (each: naam, prijs,
  beschrijving, items — a repeatable list), op_maat_titel/tekst,
  doelgroepen_titel/tekst, cta_titel/tekst/knop
- `contact` — titel, intro, formulier_titel, label_naam/email/telefoon/
  bericht, knop_verzenden, gegevens_titel, kaart_titel
- `footer` — tekst, copyright

Field types mirror what Decap already used: `string` for short text,
multiline `string`/`rich-text` for longer text, a repeatable list field
for each pricing package's `items`.

**Out of scope, unchanged from every prior version of this admin panel:**
photos, colors/theme (`style.css`), layout, and the Formspree contact-form
endpoint. Editing those still means editing code directly and pushing to
GitHub.

## Setup & authentication

1. **Tina Cloud project**: created connected to
   `AscendClientDownload/uitvaartendan` via Tina's GitHub App, authorized
   once using the existing repo-owner GitHub account (not a new account,
   not a personal access token embedded anywhere in the CMS).
2. **Annabelle's account**: she signs up for her own free Tina Cloud
   account and is added as an editor on the project. Her saves are
   attributed to her own identity, not the repo owner's.
3. **Client ID + read-only content token**: both safe to commit into the
   Astro config; this is what lets the deployed static site talk to Tina
   Cloud's content API.

## Deployment pipeline

`.github/workflows/pages.yml` changes minimally:
- `npm install` stays.
- Build command changes from `node scripts/build-content.js` to
  `npx astro build`.
- Publish directory changes from `.` (repo root) to Astro's `dist/`
  output.
- Everything else (trigger on push to `main`, GitHub Pages publish step,
  custom domain via `CNAME`) is unchanged.

## Error handling

- Tina Cloud being briefly unreachable does not affect the public site —
  it's a static build already deployed; only the `/admin` editing screen
  would be degraded.
- A failed build after a save leaves GitHub Pages serving the last
  successful deploy — same safety net as today.

## Testing / verification plan

- Local: `astro build` succeeds; output renders identically to the
  current live site (visual comparison), zero console errors.
- Local: `tina dev` confirms the visual editing overlay correctly marks
  every field listed in the Content model section above.
- Deployed: GitHub Actions build succeeds and the live site is visually
  unchanged; Annabelle's Tina Cloud login reaches the visual editor; a
  real edit-save-rebuild cycle updates the live site end to end.

## Not yet verified (requires live accounts, same caveat as every prior
CMS iteration on this site)

- Tina Cloud project creation and GitHub App authorization against the
  real repo.
- Annabelle's real Tina Cloud signup and editor invite.
- A real end-to-end save-and-rebuild cycle on the live site.
