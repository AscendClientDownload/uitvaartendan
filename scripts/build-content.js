// ============================================================
// Regenereert content.js uit content.yml. Draait automatisch bij
// elke Netlify-build (zie netlify.toml) — nooit handmatig content.js
// aanpassen, dat wordt overschreven bij de volgende opslag via /admin.
// ============================================================

const fs = require("fs");
const path = require("path");
const yaml = require("js-yaml");

const root = path.join(__dirname, "..");
const yamlPath = path.join(root, "content.yml");
const outPath = path.join(root, "content.js");

const data = yaml.load(fs.readFileSync(yamlPath, "utf8"));

const header = `// ============================================================
// INHOUD BEWERKEN — DIT DOE JE VIA /admin OP DE WEBSITE
// ============================================================
// Dit bestand wordt automatisch gegenereerd uit content.yml bij elke
// keer opslaan via /admin. Handmatige aanpassingen hier gaan
// verloren bij de volgende opslag — bewerk teksten via /admin.
// ============================================================

const SITE_CONTENT = ${JSON.stringify(data, null, 2)};

`;

const logic = `// ============================================================
// HIERONDER STAAT CODE — NIET NODIG OM AAN TE PASSEN
// ============================================================
// Deze functie leest de teksten hierboven en zet ze automatisch
// op de juiste plek in de pagina.

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
`;

fs.writeFileSync(outPath, header + logic);
console.log(`content.js generated from content.yml (${Object.keys(data).length} top-level sections).`);
