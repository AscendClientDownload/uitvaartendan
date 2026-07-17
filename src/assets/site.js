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
