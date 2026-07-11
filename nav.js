// ============================================================
// NAVIGATIE & FOOTER — wordt automatisch op elke pagina geplaatst
// ============================================================
// Dit bestand zorgt ervoor dat het menu bovenin en de footer
// onderin op elke pagina hetzelfde zijn. Pas dit bestand alleen
// aan als je een link wilt toevoegen of de menu-tekst wilt
// veranderen (voor gewone teksten: gebruik content.js).
// ============================================================

// Alles staat op één pagina (index.html). Het menu springt naar een
// sectie op die pagina via een "anker" (#over-ons, #diensten, #contact)
// in plaats van naar een aparte pagina te gaan.
const NAV_SECTIONS = [
  { href: "#home", label: (nav) => nav.home },
  { href: "#over-ons", label: (nav) => nav.over_ons },
  { href: "#diensten", label: (nav) => nav.diensten },
  { href: "#contact", label: (nav) => nav.contact },
];

function buildNav() {
  const nav = SITE_CONTENT.navigatie;
  const bedrijf = SITE_CONTENT.bedrijf;

  const linkHtml = NAV_SECTIONS.map(
    (link) => `<li><a href="${link.href}" class="nav-link" data-section="${link.href}">${link.label(nav)}</a></li>`
  ).join("");

  return `
    <div class="nav-inner">
      <a href="#home" class="nav-logo">${bedrijf.naam}</a>
      <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navMenu" aria-label="Menu openen">
        <span></span><span></span><span></span>
      </button>
      <nav id="navMenu" class="nav-menu">
        <ul class="nav-list">
          ${linkHtml}
        </ul>
        <a href="#contact" class="btn btn-primary nav-cta">${nav.cta_knop}</a>
      </nav>
    </div>
  `;
}

function buildFooter() {
  const bedrijf = SITE_CONTENT.bedrijf;
  const footer = SITE_CONTENT.footer;
  const year = new Date().getFullYear();

  return `
    <div class="footer-inner">
      <div class="footer-col">
        <p class="footer-logo">${bedrijf.naam}</p>
        <p class="footer-text">${footer.tekst}</p>
      </div>
      <div class="footer-col">
        <p class="footer-heading">Contact</p>
        <p><a href="tel:${bedrijf.telefoon_href}">${bedrijf.telefoon}</a></p>
        <p><a href="mailto:${bedrijf.email}">${bedrijf.email}</a></p>
        <p>${bedrijf.straat}<br>${bedrijf.postcode_plaats}</p>
      </div>
      <div class="footer-col">
        <p class="footer-heading">Openingstijden</p>
        <p>${bedrijf.openingstijden}</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; ${year} ${bedrijf.naam} — ${footer.copyright}</p>
    </div>
  `;
}

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

// Zet het menu-item van de sectie die nu in beeld is op "actief",
// zodat je in het menu ziet waar je bent tijdens het scrollen.
function initScrollSpy() {
  const sections = NAV_SECTIONS.map((link) => document.querySelector(link.href)).filter(Boolean);
  const navLinks = document.querySelectorAll(".nav-link");
  if (!sections.length || !navLinks.length) return;

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

document.addEventListener("DOMContentLoaded", () => {
  const navPlaceholder = document.getElementById("site-nav");
  const footerPlaceholder = document.getElementById("site-footer");

  if (navPlaceholder) navPlaceholder.innerHTML = buildNav();
  if (footerPlaceholder) footerPlaceholder.innerHTML = buildFooter();

  initNavToggle();
  initScrollSpy();
});
