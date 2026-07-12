# Handleiding — je website zelf aanpassen

Deze handleiding legt in gewone taal uit hoe je teksten, kleuren en
foto's op je website kunt aanpassen. Je hebt hier geen technische
kennis voor nodig.

Je website bestaat uit **één pagina** waarop je naar beneden scrolt.
Het menu bovenin ("Home", "Over mij", "Diensten", "Contact") springt
naar het bijbehorende stuk van diezelfde pagina.

Teksten pas je **rechtstreeks op de website aan**, door in te loggen
en op de tekst te klikken — geen bestanden, geen code. Alleen voor
kleuren, foto's en het contactformulier heb je nog één keer een
teksteditor nodig.

---

## 1. Inloggen en teksten aanpassen

Ga naar **`https://www.uitvaartendan.nl/admin`** en log in met het
wachtwoord dat je van Micha hebt gekregen.

**Zo werkt het bewerken:**

1. Klik ergens op de tekst die je wilt aanpassen. Je ziet een
   stippellijntje om elk stukje tekst dat je kunt bewerken.
2. Typ je nieuwe tekst, gewoon zoals in een Word-document.
3. Klik ergens anders op de pagina (buiten het tekstvak). Je
   wijziging is dan meteen opgeslagen en direct zichtbaar voor
   iedereen die de website bezoekt — geen extra stap nodig.

Kom je een tekst tegen met **"nog invullen"** ernaast? Dat is een
plek die nog op jouw tekst wacht (bijvoorbeeld het tweede
dienstenpakket of een stuk van je persoonlijke verhaal). Klik erop en
vul je eigen tekst in, net als bij elke andere tekst.

**Uitloggen:** klik rechtsboven op het tandwiel-icoon en kies
"Logout". Zeker op een gedeelde computer altijd even doen.

**Wachtwoord wijzigen:** klik rechtsboven op het tandwiel-icoon →
"Settings" → tabblad met het wachtwoordveld. Vul je huidige en je
nieuwe wachtwoord in en sla op. Doe dit gerust als eerste, zodat
alleen jij je wachtwoord kent.

**Wachtwoord vergeten?** Vraag het aan Micha — die kan het via FTP
opnieuw instellen (zie `docs/superpowers/specs/2026-07-12-wondercms-integration.md`
voor de technische details, alleen relevant voor Micha).

---

## 2. Een kleur aanpassen

Kleuren staan in het bestand `style.css`, dat je met Kladblok
(Windows) of TextEdit (Mac) opent — net als een gewoon tekstbestand.
Helemaal bovenaan, tussen `:root {` en het eerste `}`, staat elke
kleur met een naam en een "hexcode" (bijvoorbeeld `#8FA593`).

**Stappen:**

1. Zoek in de map `uitvaartendan` het bestand `style.css`.
2. Klik met de rechtermuisknop → "Openen met" → Kladblok/TextEdit.
3. Zoek bovenaan de regel met de kleur die je wilt aanpassen, bijvoorbeeld:
   ```
   --color-sage: #8FA593;
   ```
4. Vervang de hexcode door een andere. Op een website zoals
   [htmlcolorcodes.com](https://htmlcolorcodes.com) kun je met een
   kleurenwiel een nieuwe hexcode uitzoeken.
5. Sla het bestand op en upload het via FTP (zie stap 4 hieronder).

Omdat alle kleuren op de site naar deze ene plek verwijzen, verandert
de hele website automatisch mee.

---

## 3. De website online zetten (uploaden via FTP)

FTP is simpelweg een manier om bestanden van jouw computer naar de
webserver van je hostingbedrijf (Yourhosting) te sturen — te
vergelijken met bestanden slepen naar een USB-stick, maar dan via
internet. **Dit heb je alleen nog nodig voor kleuren, foto's en het
contactformulier** — teksten pas je aan via inloggen (stap 1).

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
gewijzigd (bijvoorbeeld alleen `style.css`). Dat is sneller dan alles
opnieuw te uploaden.

---

## 4. Een foto vervangen

Op de website staan een aantal gekleurde vlakken op de plek waar
straks een foto komt. Dit pas je aan in het bestand
`themes/uitvaartendan/theme.php` (met Kladblok/TextEdit, net als bij
kleuren). In dat bestand herken je de juiste plekken aan de tekst:

```html
<!-- VERVANG MET FOTO: portretfoto van Annabelle Zaal -->
```

**Stappen om een foto toe te voegen:**

1. Zet je foto's in de map `uitvaartendan`, bijvoorbeeld in een
   nieuwe submap genaamd `foto's`.
2. Open `themes/uitvaartendan/theme.php` met Kladblok/TextEdit.
   Gebruik `Ctrl + F` (zoeken) om snel bij het juiste stuk te komen —
   zoek bijvoorbeeld naar `portretfoto van Annabelle`.
3. Zoek de regel met `<!-- VERVANG MET FOTO: ... -->`.
4. Direct daarboven staat een regel zoals:
   ```html
   <div class="hero-image" aria-hidden="true">
   ```
5. Voeg er een foto aan toe door deze regel te vervangen door:
   ```html
   <img src="/foto's/mijnfoto.jpg" alt="Omschrijving van de foto">
   ```
   Vervang `mijnfoto.jpg` door de bestandsnaam van jouw foto, en
   schrijf bij `alt` een korte omschrijving van wat er op de foto
   staat (dit is belangrijk voor mensen die de website met een
   schermlezer gebruiken).
6. Sla het bestand op, upload het samen met de foto via FTP (zie
   stap 3), en controleer het resultaat in de browser.

Twijfel je hierover? Vraag het gerust na bij wie de website voor je
gebouwd heeft — dit stapje mag ook door een ander voor je gedaan
worden.

---

## 5. Het contactformulier activeren

Het contactformulier bij "Contact" (onderaan de pagina) werkt via een
gratis dienst genaamd Formspree. Zonder deze stap komen berichten nog
niet aan. Dit pas je ook aan in `themes/uitvaartendan/theme.php`.

1. Maak een gratis account aan op [formspree.io](https://formspree.io).
2. Maak daar een nieuw formulier aan met jouw e-mailadres
   (`info@uitvaartendan.nl`).
3. Formspree geeft je een link (endpoint), bijvoorbeeld
   `https://formspree.io/f/abcd1234`.
4. Open `themes/uitvaartendan/theme.php` met Kladblok/TextEdit en
   zoek (met `Ctrl + F`) naar `VERVANG DIT MET JE FORMSPREE
   ENDPOINT`. Je komt dan bij:
   ```
   <!-- VERVANG DIT MET JE FORMSPREE ENDPOINT (bv. https://formspree.io/f/abcd1234) -->
   <form action="https://formspree.io/f/VERVANG_MET_JE_FORMSPREE_ID" method="POST">
   ```
5. Vervang `https://formspree.io/f/VERVANG_MET_JE_FORMSPREE_ID` door
   jouw eigen Formspree-link.
6. Sla het bestand op en upload het via FTP.

---

## 6. Eén ding om op te letten: telefoon en e-mail

Je telefoonnummer en e-mailadres zijn aanklikbaar (bezoekers kunnen
er direct mee bellen/mailen). Als je alleen de **tekst** aanpast
(bijvoorbeeld een tikfout verbetert) via inloggen, werkt de link
gewoon door.

Verandert je telefoonnummer of e-mailadres écht (een nieuw nummer,
niet alleen de schrijfwijze)? Pas dan de tekst aan via inloggen **en**
laat het even aan Micha weten — die moet dan ook de link zelf (waar
er precies naartoe gebeld/gemaild wordt) in
`themes/uitvaartendan/theme.php` bijwerken. Dat is één regel code,
geen grote klus, maar gaat niet vanzelf mee met de tekst.

---

## 7. Overige bestanden in de map

Naast de bestanden die je al kent, staan er nu ook een paar
bestanden en mappen bij die niet zichtbaar zijn op de website zelf,
maar wel belangrijk zijn:

- `index.php` en `.htaccess` — de "motor" van de website (WonderCMS).
  Niet aanpassen of verwijderen.
- `themes/uitvaartendan/` — de opmaak van je website (naast
  `style.css`). Hier pas je foto's en het contactformulier aan (zie
  stap 4 en 5).
- `data/` — hierin staan al je teksten en je wachtwoord opgeslagen.
  **Heel belangrijk: altijd meesturen bij het uploaden, maar nooit de
  inhoud handmatig aanpassen of verwijderen** — dat kan ervoor zorgen
  dat je niet meer kunt inloggen of dat teksten verdwijnen.
- `robots.txt` en `sitemap.xml` — helpen Google om de website goed
  te vinden en te doorzoeken. Hier hoef je niets mee te doen, gewoon
  meesturen bij het uploaden.

Zodra je de foto's aanlevert: vraag Micha om ook een `og-image.jpg`
(1200 x 630 pixels, bijvoorbeeld een sfeerfoto of je logo) toe te
voegen. Dat is de afbeelding die verschijnt wanneer iemand een link
naar de website deelt via WhatsApp, Facebook of LinkedIn.

---

## Hulp nodig?

Kom je er niet uit, of wil je liever dat iemand anders dit voor je
doet? Dat kan altijd. Bewaar deze handleiding dan gewoon voor later.
