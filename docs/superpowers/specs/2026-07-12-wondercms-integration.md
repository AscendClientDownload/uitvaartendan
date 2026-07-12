# /admin via WonderCMS — what was built

Date: 2026-07-12
Status: implemented and locally tested

## Why this replaced the custom admin panel

The custom PHP admin panel (`2026-07-11-admin-panel-design.md`/`-plan.md`)
was fully spec'd and about to be built when the user asked to check for an
existing free tool first rather than build one from scratch. Researched and
compared:

- **SiteCake** ($99 one-time, closed-source) — inline WYSIWYG editing
  directly on existing static HTML, closest conceptual fit, but paid and
  the code can't be audited.
- **WonderCMS** (free, MIT license, ~50KB core, actively maintained,
  no known CVEs) — chosen by the user.
- The custom build — rejected in favor of not maintaining bespoke auth/CSRF/
  session code for something a well-maintained free project already solves.

## How it works

WonderCMS takes over routing at the domain root via `.htaccess`
(`RewriteRule ^(.+)$ index.php?page=$1`, with real static files still served
directly — confirmed the shipped `.htaccess` already blocks direct access to
its own data file). The site's `index.html` became a WonderCMS **theme**
(`themes/uitvaartendan/theme.php`): one single WonderCMS page ("home") holds
all of today's markup, so the site is still one scrolling page with the same
anchor-based nav (`nav.js` trimmed to just mobile-toggle + scroll-spy — the
content-injection logic it used to do is gone, since the markup is now
server-rendered PHP instead of client-side templated).

Every piece of copy that was in `content.js` is now a **block**
(`$Wcms->block('key')`) — WonderCMS's editable-region primitive. Blocks are
stored in `data/database.js` (JSON), which also holds the bcrypt password
hash and the login URL setting (set to `admin`, so the login lives at
`/admin` exactly as originally asked for).

### A real bug found and fixed during testing

WonderCMS wraps every block in `<div class="editText editable">...</div>`
**only while logged in**. Public visitors get zero extra markup — confirmed
`$Wcms->css()`/`$Wcms->js()` both return an empty string when not logged in,
so the public page is byte-for-byte what a plain static site would produce.

But HTML's parsing spec auto-closes an open `<p>` the instant it hits a
`<div>` (div is in the list of elements that implicitly close a paragraph).
Since every block becomes a `<div>` while logged in, any `<p><?= $Wcms->block(...) ?></p>`
in the theme got silently corrupted by the browser into
`<p></p><div>...</div><p></p>` — invisible on plain single-paragraph text,
but it visibly broke the footer's three-column grid (email text overlapping
the copyright line) because the reparsed, orphaned `<div>` became a stray
grid sibling. Public visitors were never affected (no `<div>` wrapper exists
for them), only Annabelle while editing.

Fix: every `<p>{block}</p>` in the theme became `<div class="wcms-text">{block}</div>`,
with `.wcms-text` given the same CSS as `p` (`style.css`). Verified via a
DOM dump before/after (`admin/data` — no, `data/database.js` — confirmed
correctly nested, no split elements) and a full Playwright screenshot pass
in the logged-in state.

### Verified locally

- Public homepage: pixel-identical to the pre-WonderCMS static site, zero
  console errors, zero extra markup.
- Login at `/admin`: works (verified via curl with a cookie jar, and via
  Playwright with an injected session cookie — clicking the login button in
  a live browser triggers a hang caused by a Windows-only `dirname($_SERVER['SCRIPT_NAME'])`
  quirk in PHP's built-in dev server that produces a literal backslash in
  the redirect `Location` header; confirmed via curl that the actual login
  response is correct and fast (302, valid session cookie) — this does not
  occur on Linux/Apache, the real Yourhosting target).
- Editing: clicked into a block, retyped its text, clicked away — the
  change persisted to `data/database.js` and appeared instantly for a fresh
  (logged-out) request, no rebuild step.
- Mobile (375px): renders correctly, matches the pre-existing design.

## What changed in the file tree

- Removed: `index.html`, `content.js` (superseded by `theme.php` + blocks),
  `htaccess-optioneel.txt` (merged into the now-required `.htaccess`).
- Added: `index.php`, `.htaccess` (WonderCMS core, unmodified except for
  appended security headers), `themes/uitvaartendan/theme.php`,
  `themes/uitvaartendan/wcms-modules.json`, `data/` (gitignored — contains
  the password hash and all live content; must still be FTP-uploaded).
- Changed: `nav.js` (trimmed to UX-only), `style.css` (added `.wcms-text`
  and edit-mode-only overrides), `HANDLEIDING.md` (rewritten for the
  WonderCMS workflow).

## Known limitation

Phone/email are editable as clickable links (`<a href="tel:...">` baked
around the block in the theme, not the block content) — if the actual phone
number or email address ever changes (not just its displayed formatting),
the `tel:`/`mailto:` target in `theme.php` needs a one-line manual update
too, since WonderCMS blocks don't support binding one field to both visible
text and a separate attribute value. Documented in `HANDLEIDING.md`.
