# /admin via Decap CMS on Netlify — what was built

Date: 2026-07-12
Status: implemented and tested locally; deployment (GitHub push, Netlify
site creation, Identity/Git Gateway setup, custom domain DNS) guided
interactively with the user, not something a local test can verify.

## Why this replaced WonderCMS

WonderCMS (see `2026-07-12-wondercms-integration.md`) worked correctly as
software — verified end-to-end locally — but failed on two real hosts in a
row for reasons outside the code's control:

1. **Yourhosting** — the purchased package ("Start mail") turned out to be
   email-hosting only; Yourhosting's own product page states outright "je
   kunt geen website maken met dit pakket" (you cannot build a website
   with this package). No PHP execution at all, at any setting.
2. **InfinityFree** (free tier) — PHP executes fine, and the public page
   rendered correctly, but every POST request (including the `/admin`
   login) got intercepted by an anti-bot JavaScript challenge
   (`Server: openresty`, an AES-decrypt-and-cookie challenge) that
   redirects via `location.href` — which silently converts the POST to a
   GET, discarding the submitted password before WonderCMS ever sees it.
   Confirmed via curl (challenge page returned instantly, every time, even
   with valid prior-request cookies) and Playwright (real browser also
   never completed the login, consistent with the POST body being lost).

Both are hosting-environment problems, not application bugs — but two
failures in a row on PHP shared hosting was reason enough for the user to
ask for a different, more reliable path: Netlify, which the user already
had a paid/free account on, with Decap CMS (formerly Netlify CMS) as the
`/admin`-equivalent editor. This also sidesteps the failure mode above
entirely: Decap CMS doesn't submit a form POST to the hosting server — it
authenticates via Netlify Identity and commits content changes through
Git Gateway's API, which isn't the kind of traffic anti-bot POST-challenges
are built to intercept.

## Architecture

Reverted to the original static-site structure (from before the admin-panel
work started, restored via `git show` from the baseline commit) — plain
`index.html` + `content.js` + `render`-logic + `nav.js`, no server-side
language at all. Netlify serves this as a static site; there is no runtime
backend.

**New content pipeline:**
- `content.yml` is now the source of truth (was `content.js` directly
  before). Same shape as the old `SITE_CONTENT` object, just YAML instead
  of a JS literal.
- `scripts/build-content.js` (Node, using `js-yaml`) reads `content.yml`
  and regenerates `content.js` — the data half via `JSON.stringify`, the
  rendering-logic half (`getContentValue`/`applyContent`/the
  `DOMContentLoaded` listener) as a fixed template, identical to what was
  hand-written before. `netlify.toml` runs `npm run build` on every
  deploy, so this always happens automatically; `content.js` itself is
  fully regenerated, never hand-edited once this is live.
- `admin/index.html` + `admin/config.yml` are Decap CMS: a `files`-type
  collection (not `folder`, since this is one structured file, not a list
  of posts) with one `object` field per top-level `content.yml` section
  (`bedrijf`, `navigatie`, `home`, `over_ons`, `diensten` — including a
  `list` widget for each pricing package's `items` — `contact`, `footer`),
  field-for-field matching what was in the site's data model already.
- Auth: Netlify Identity (widget loaded only on `/admin/index.html`) +
  Git Gateway (so editors never need real GitHub credentials). The
  invite-email link lands on the site root with a token in the URL hash;
  `identity-redirect.js` (a small external file, not inline — the site's
  CSP is `script-src 'self'` with no `'unsafe-inline'`, so this had to be
  a real file, not a `<script>` block) detects that and forwards to
  `/admin/`.

**Save flow:** edit a field in Decap CMS → "Publish" → Git Gateway commits
the change to `content.yml` on the `main` branch → Netlify's build hook
fires → `npm run build` regenerates `content.js` → site redeploys. Typically
30-90 seconds from save to live, a real and unavoidable difference from
WonderCMS's instant AJAX save — documented clearly in `HANDLEIDING.md` so
it isn't mistaken for the save having failed.

## What's out of scope for the CMS (same limitation as before, different cause)

Photos, colors (`style.css`), and the Formspree endpoint are not part of
the Decap CMS field set — editing those means editing the file directly
and pushing to GitHub (Micha's task, not exposed to Annabelle). This
mirrors WonderCMS's same limitation for a different structural reason:
there, it was about not exposing risky free-text HTML editing; here, it's
that Decap CMS only manages the fields defined in `admin/config.yml`, and
photo/color changes aren't modeled as content fields at all.

## Verified locally

- `npm run build` regenerates `content.js` correctly from `content.yml`
  (confirmed valid JS — Node's module loader parsed the whole file before
  hitting the expected `document is not defined`, which only happens
  after successful parsing).
- Public site served via `npx serve`: renders identically to the
  pre-admin-panel static site, zero console errors, zero empty
  `data-content` fields, pricing list checkmarks intact.
- `/admin/` loads Decap CMS with no config errors, shows the expected
  "Login with Netlify Identity" screen (full login flow requires a real
  Netlify site + Git Gateway backend, which only exists once deployed —
  not testable from a local static file server).

## Not yet verified (requires the user's live accounts)

- GitHub push, Netlify site creation and build success.
- Netlify Identity + Git Gateway enabled and working end-to-end (invite
  email, first login, password set, an actual save-and-rebuild cycle).
- Custom domain (`uitvaartendan.nl`) pointed at Netlify via DNS at
  Yourhosting (same CNAME-only approach used for InfinityFree, to avoid
  disturbing the existing MX records for `info@uitvaartendan.nl`).
