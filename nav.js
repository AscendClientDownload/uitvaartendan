// ============================================================
// MENU-GEDRAG (niet de teksten — die staan nu in de blokken die je
// via /admin bewerkt)
// ============================================================
// Regelt het open/dicht klappen van het mobiele menu en markeert
// welk menu-item actief is tijdens het scrollen.

const NAV_SECTION_HREFS = ["#home", "#over-ons", "#diensten", "#contact"];

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
  const sections = NAV_SECTION_HREFS.map((href) => document.querySelector(href)).filter(Boolean);
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
  initNavToggle();
  initScrollSpy();
});
