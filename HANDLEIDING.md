# Handleiding — je website zelf aanpassen

Deze handleiding legt in gewone taal uit hoe je teksten, kleuren en
foto's op je website kunt aanpassen. Je hebt hier geen technische
kennis voor nodig.

Je website bestaat uit **één pagina** waarop je naar beneden scrolt.
Het menu bovenin ("Home", "Over mij", "Diensten", "Contact") springt
naar het bijbehorende stuk van diezelfde pagina — het zijn dus geen
aparte pagina's meer. Dat betekent dat al die onderdelen in **één
bestand** staan: `index.html`.

---

## 1. Wat heb je nodig?

- Je website-bestanden (de map `uitvaartendan`)
- Een **teksteditor**. Op Windows heet dit "Kladblok" (Notepad), op
  Mac heet dit "TextEdit". Dit programma staat al op je computer.
- Om de website daarna online te zetten: een **FTP-programma**, zoals
  FileZilla. Hierover meer bij stap 4.

---

## 2. Een tekst aanpassen

Alle teksten van de website staan verzameld in **één bestand**:
`content.js`.

**Stappen:**

1. Zoek in de map `uitvaartendan` het bestand `content.js`.
2. Klik met de rechtermuisknop op het bestand.
3. Kies **"Openen met"** en kies daarna **Kladblok** (Notepad).
4. Je ziet nu een lijst met teksten, bijvoorbeeld:
   ```
   hero_titel: "Je bent niet alleen. Ik loop met je mee.",
   ```
5. Verander alleen de tekst **tussen de aanhalingstekens** (`"..."`).
   Laat de aanhalingstekens en de komma aan het einde van de regel
   altijd staan.
6. Sla het bestand op via **Bestand > Opslaan** (of `Ctrl + S`).
7. Herlaad de pagina in je browser om de wijziging te bekijken.

**Let op:** verander nooit de tekst vóór de dubbele punt (`:`). Dat is
de naam van het tekstveld en die moet blijven staan zoals hij is.

**Voorbeeld — goed:**
```
hero_titel: "Welkom bij Uitvaart en dan?",
```

**Voorbeeld — fout (aanhalingstekens weggehaald):**
```
hero_titel: Welkom bij Uitvaart en dan?,
```

Herken je tussen de teksten de melding `[INVULLEN]`? Dat betekent dat
die tekst nog door jou aangevuld moet worden — bijvoorbeeld het
tweede dienstenpakket of een stuk van je persoonlijke verhaal.

---

## 3. Een kleur aanpassen

Kleuren staan in het bestand `style.css`, helemaal bovenaan, tussen
`:root {` en het eerste `}`. Elke kleur heeft een naam en een
zogeheten "hexcode" (bijvoorbeeld `#8FA593`).

**Stappen:**

1. Open `style.css` met Kladblok, net zoals bij stap 2.
2. Zoek bovenaan de regel met de kleur die je wilt aanpassen, bijvoorbeeld:
   ```
   --color-sage: #8FA593;
   ```
3. Vervang de hexcode door een andere. Op een website zoals
   [htmlcolorcodes.com](https://htmlcolorcodes.com) kun je met een
   kleurenwiel een nieuwe hexcode uitzoeken.
4. Sla het bestand op en herlaad de website.

Omdat alle kleuren op de site naar deze ene plek verwijzen, verandert
de hele website automatisch mee.

---

## 4. De website online zetten (uploaden via FTP)

FTP is simpelweg een manier om bestanden van jouw computer naar de
webserver van je hostingbedrijf (Yourhosting) te sturen — te
vergelijken met bestanden slepen naar een USB-stick, maar dan via
internet.

**Stappen:**

1. Download een gratis FTP-programma, bijvoorbeeld
   [FileZilla](https://filezilla-project.org/).
2. Log in met de FTP-gegevens die je van Yourhosting hebt gekregen
   (server, gebruikersnaam en wachtwoord — deze vind je in je
   Yourhosting-klantpaneel).
3. Sleep de bestanden uit je `uitvaartendan`-map naar de hoofdmap van
   je website op de server (vaak `public_html` of `www` genoemd).
4. Wacht tot de upload klaar is en bezoek daarna je website in de
   browser om te controleren of alles goed werkt.

**Tip:** upload na een aanpassing alleen het bestand dat je hebt
gewijzigd (bijvoorbeeld alleen `content.js`). Dat is sneller dan alles
opnieuw te uploaden.

---

## 5. Een foto vervangen

Op de website staan een aantal gekleurde vlakken op de plek waar
straks een foto komt. In de bestanden herken je deze plekken aan de
tekst:

```html
<!-- VERVANG MET FOTO: portretfoto van Annabelle Zaal -->
```

**Stappen om een foto toe te voegen:**

1. Zet je foto's in de map `uitvaartendan`, bijvoorbeeld in een
   nieuwe submap genaamd `foto's`.
2. Open `index.html` met Kladblok. Gebruik `Ctrl + F` (zoeken) om snel
   bij het juiste stuk van de pagina te komen — zoek bijvoorbeeld naar
   `portretfoto van Annabelle` om bij de foto op "Over mij" te komen.
3. Zoek de regel met `<!-- VERVANG MET FOTO: ... -->`.
4. Direct daarboven staat een regel zoals:
   ```html
   <div class="hero-image" aria-hidden="true">
   ```
5. Voeg er een foto aan toe door deze regel te vervangen door:
   ```html
   <img src="foto's/mijnfoto.jpg" alt="Omschrijving van de foto">
   ```
   Vervang `mijnfoto.jpg` door de bestandsnaam van jouw foto, en
   schrijf bij `alt` een korte omschrijving van wat er op de foto
   staat (dit is belangrijk voor mensen die de website met een
   schermlezer gebruiken).
6. Sla het bestand op, upload het samen met de foto via FTP (zie
   stap 4), en controleer het resultaat in de browser.

Twijfel je hierover? Vraag het gerust na bij wie de website voor je
gebouwd heeft — dit stapje mag ook door een ander voor je gedaan
worden.

---

## 6. Het contactformulier activeren

Het contactformulier bij "Contact" (onderaan de pagina) werkt via een
gratis dienst genaamd Formspree. Zonder deze stap komen berichten nog
niet aan.

1. Maak een gratis account aan op [formspree.io](https://formspree.io).
2. Maak daar een nieuw formulier aan met jouw e-mailadres
   (`info@uitvaartendan.nl`).
3. Formspree geeft je een link (endpoint), bijvoorbeeld
   `https://formspree.io/f/abcd1234`.
4. Open `index.html` met Kladblok en zoek (met `Ctrl + F`) naar
   `VERVANG DIT MET JE FORMSPREE ENDPOINT`. Je komt dan bij:
   ```
   <!-- VERVANG DIT MET JE FORMSPREE ENDPOINT (bv. https://formspree.io/f/abcd1234) -->
   <form action="https://formspree.io/f/VERVANG_MET_JE_FORMSPREE_ID" method="POST">
   ```
5. Vervang `https://formspree.io/f/VERVANG_MET_JE_FORMSPREE_ID` door
   jouw eigen Formspree-link.
6. Sla het bestand op en upload het via FTP.

---

## 7. Overige bestanden in de map

Naast de bestanden die je al kent, staan er nu ook een paar
bestanden bij die niet zichtbaar zijn op de website zelf, maar wel
belangrijk zijn:

- `robots.txt` en `sitemap.xml` — helpen Google om de website goed
  te vinden en te doorzoeken. Hier hoef je niets mee te doen, gewoon
  meesturen bij het uploaden.
- `htaccess-optioneel.txt` — een optioneel bestand met extra
  beveiliging voor de webserver. Alleen voor Micha, met eigen
  uitleg bovenin het bestand. Niet nodig voor jou om aan te passen.

Zodra je de foto's aanlevert: vraag Micha om ook een `og-image.jpg`
(1200 x 630 pixels, bijvoorbeeld een sfeerfoto of je logo) toe te
voegen. Dat is de afbeelding die verschijnt wanneer iemand een link
naar de website deelt via WhatsApp, Facebook of LinkedIn.

---

## Hulp nodig?

Kom je er niet uit, of wil je liever dat iemand anders dit voor je
doet? Dat kan altijd. Bewaar deze handleiding dan gewoon voor later.
