# Migrate site to Eleventy + TinaCMS (Tina Cloud) — design

Date: 2026-07-17
Status: approved by user (revised after a mid-plan blocker — see Revision
history at the bottom), not yet implemented

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

## Non-goal: visual live editing

An earlier version of this spec chose TinaCMS's "visual" live-editing mode
(edit text directly on a rendered preview of the real page). That turned
out to require Astro's `output: 'server'` mode plus a server adapter
(Node/Vercel/Netlify/Cloudflare) — visual editing works through an
on-demand server endpoint that has to run at request time, which **GitHub
Pages cannot do at all** (pure static file hosting, no server execution).
Since keeping the site on GitHub Pages is a hard requirement, visual
editing is out of scope. Annabelle gets Tina's plain form-based admin
screen instead — functionally the same experience Decap/Sveltia already
gave her (a list of text fields, edit, save), just with Tina Cloud's
separate-identity login instead of a GitHub-tied one.

## Architecture

- **Framework**: the site moves from hand-written HTML + a
  `content.yml`→`content.js` build script to **Eleventy (11ty)**, a static
  site generator with zero server runtime — it only ever produces plain
  HTML files, which is exactly why it's compatible with both GitHub Pages
  and Tina's non-visual admin (frameworks incapable of SSR, like 11ty,
  Hugo, and Jekyll, are the ones Tina supports without requiring a server
  adapter).
- **Homepage** (`index.html` → `src/index.njk` or similar 11ty template):
  same sections as today (Hero, Welkom, Over mij, Diensten, Contact),
  rebuilt as 11ty template(s) reading from the Tina-managed content file
  instead of `data-content` attributes populated by client-side JS.
- **Legal pages** (`algemene-voorwaarden.html`, `privacyverklaring.html`,
  `cookieverklaring.html`, `disclaimer.html`): converted to 11ty templates
  too, for one consistent build pipeline — **not** wired into Tina, same
  static text as today (these were never in `admin/config.yml` either).
- **Nav & footer** (`nav.js`): becomes a shared 11ty layout/include,
  rendered at build time instead of injected by client-side JS at
  runtime. Same visual output, less runtime JS shipped to visitors.
- **Content storage**: `content/home.json` replaces `content.yml` as the
  source of truth — same shape/fields as `admin/config.yml` today (see
  Content model below), edited as a single Tina "document" (not a folder
  of many entries — there is and only ever will be this one file).
- **Hosting**: unchanged — GitHub Pages via the existing
  `.github/workflows/pages.yml`. Only the build command changes (see
  Deployment pipeline below). Domain, DNS, and SSL are untouched.
- **Editing flow**: Annabelle logs into Tina Cloud with her own
  email+password → opens the site's `/admin` → sees a form listing every
  editable field → edits, saves → Tina Cloud commits to `main` → GitHub
  Actions rebuilds the static site → live in roughly 1-2 minutes.

## Content model

Directly maps every field currently in `admin/config.yml` — nothing
added, nothing removed:

- `bedrijf` — bedrijfsnaam, slogan, telefoon, telefoon_href, e-mail,
  straat, postcode_plaats, openingstijden
- `navigatie` — menu labels (home, over_ons, diensten, contact) + CTA-knop
- `home` — hero titel/ondertitel/knop, welkomsttitel/tekst,
  diensten-titel/tekst/knop, over-titel/tekst/knop, voor-wie-titel/tekst,
  cta-titel/tekst/knop
- `over_ons` — titel, intro, paragrafen, missie_titel/tekst,
  cta_titel/knop
- `diensten` — titel, intro, pakket_1 and pakket_2 (each: naam, prijs,
  beschrijving, items — a repeatable list), op_maat_titel/tekst,
  doelgroepen_titel/tekst, cta_titel/tekst/knop
- `contact` — titel, intro, formulier_titel, label_naam/email/telefoon/
  bericht, knop_verzenden, gegevens_titel, kaart_titel
- `footer` — tekst, copyright

Field types mirror what Decap already used: `string` for short text,
multiline `string` for longer text, a repeatable list field for each
pricing package's `items`.

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
   site config; this is what lets the deployed static site (and the Tina
   admin bundle) talk to Tina Cloud's content API.

## Deployment pipeline

`.github/workflows/pages.yml` changes minimally:
- `npm install` stays.
- Build command changes from `node scripts/build-content.js` to the
  Eleventy build (plus the Tina admin bundle build).
- Publish directory changes from `.` (repo root) to Eleventy's output
  directory (`_site` by default).
- Everything else (trigger on push to `main`, GitHub Pages publish step,
  custom domain via `CNAME`) is unchanged.

## Error handling

- Tina Cloud being briefly unreachable does not affect the public site —
  it's a static build already deployed; only the `/admin` editing screen
  would be degraded.
- A failed build after a save leaves GitHub Pages serving the last
  successful deploy — same safety net as today.

## Testing / verification plan

Because the Eleventy+Tina+GitHub-Pages combination hasn't been proven
end-to-end in this project before (only reasoned about from how each tool
works), the implementation plan front-loads a **minimal smoke test**
before the full content migration: get a bare-bones Eleventy site with
Tina's admin wired in actually building to fully static output and
confirm nothing in that output requires a running server. If that smoke
test reveals a blocker, stop and revisit before doing the full rewrite.

Beyond that:
- Local: Eleventy build succeeds; output renders identically to the
  current live site (visual comparison), zero console errors.
- Local: Tina's local dev mode shows every field listed in the Content
  model section above in the admin form.
- Deployed: GitHub Actions build succeeds and the live site is visually
  unchanged; Annabelle's Tina Cloud login reaches the admin form; a real
  edit-save-rebuild cycle updates the live site end to end.

## Not yet verified (requires live accounts, same caveat as every prior
CMS iteration on this site)

- Tina Cloud project creation and GitHub App authorization against the
  real repo.
- Annabelle's real Tina Cloud signup and editor invite.
- A real end-to-end save-and-rebuild cycle on the live site.

## Revision history

- **2026-07-17, initial**: Astro + TinaCMS visual live editing, chosen
  explicitly by the user despite the higher setup cost of wiring an
  editing bridge into every page.
- **2026-07-17, revised**: discovered mid-plan that visual editing
  requires server-side hosting, incompatible with the hard requirement to
  stay on GitHub Pages. Switched to Eleventy + Tina's non-visual
  form-based admin, which stays fully static.
