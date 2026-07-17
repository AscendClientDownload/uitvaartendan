# Handleiding — je website zelf aanpassen

Deze handleiding legt in gewone taal uit hoe je teksten op je website
kunt aanpassen. Je hebt hier geen technische kennis voor nodig.

Je website bestaat uit **één pagina** waarop je naar beneden scrolt.
Het menu bovenin ("Home", "Over mij", "Diensten", "Contact") springt
naar het bijbehorende stuk van diezelfde pagina.

Teksten pas je aan via een **beheerscherm** op
`https://www.uitvaartendan.nl/admin` — geen bestanden, geen code.
Kleuren, foto's en het contactformulier zijn iets technischer en lopen
via Micha (zie onderaan).

---

## 1. Inloggen en teksten aanpassen

1. Ga naar **`https://www.uitvaartendan.nl/admin`**.
2. Log in met het e-mailadres en wachtwoord van je eigen **Tina
   Cloud**-account — dat is het account waarmee Micha je heeft
   uitgenodigd. (De allereerste keer krijg je een uitnodigingsmail
   waarin je zelf een wachtwoord instelt — zie "De eerste keer"
   hieronder.)
3. Je ziet nu een lijst met onderdelen: **Bedrijfsgegevens, Menu,
   Home, Over mij, Diensten, Contact, Footer.** Klik op het onderdeel
   dat je wilt aanpassen.
4. Je ziet een formulier met invulvelden — één veld per tekst op de
   website, met duidelijke labels (bijvoorbeeld "Hoofdtitel (hero)").
   Pas de tekst aan in het veld.
5. Klik rechtsboven op **"Save"** (en daarna eventueel "Publish", als
   dat apart wordt gevraagd) om je wijziging op te slaan.

**Let op: de wijziging is niet meteen zichtbaar.** Na het opslaan
bouwt de website zichzelf opnieuw op — dat duurt meestal **30 tot 90
seconden**. Ververs de website na een minuutje en je ziet je nieuwe
tekst staan.

**De onderdelen "Diensten" → "Pakket 1" en "Pakket 2"** hebben een
lijstje "Onderdelen van dit pakket". Met de knoppen daaronder kun je
een regel toevoegen of verwijderen (bijvoorbeeld als een pakket een
extra dienst krijgt).

**Uitloggen:** klik linksboven op je naam/e-mailadres en kies
"Log out". Zeker op een gedeelde computer altijd even doen.

### De eerste keer

Micha nodigt je uit voor het Tina Cloud-project van de website. Je
krijgt daarvoor een uitnodigingsmail van Tina Cloud. In die e-mail:
1. Klik op de link.
2. Stel daar je eigen wachtwoord in voor je Tina Cloud-account.
3. Vanaf nu log je op `/admin` in met je e-mailadres en dat
   wachtwoord.

**Wachtwoord vergeten?** Ga naar `/admin` en gebruik de
"wachtwoord vergeten"-link op het inlogscherm — je krijgt een e-mail
van Tina Cloud om een nieuw wachtwoord in te stellen.

---

## 2. Kleuren en foto's

Deze twee zijn iets technischer, omdat ze in de broncode van de
website staan (niet in het beheerscherm) en via GitHub gepubliceerd
moeten worden. **Vraag dit aan Micha** — onderstaande is vooral
bedoeld zodat je begrijpt wat er gebeurt:

- **Kleuren** staan in `style.css`, bovenaan bij `:root {`, als
  hexcodes (bijvoorbeeld `#8FA593`).
- **Foto's** staan als bestanden in de map `images/` (bijvoorbeeld
  `hero-achtergrond.jpg` en `logo-icon.png`) en worden vanuit
  `src/index.njk` en `style.css` ingeladen. Een foto vervangen
  betekent: het bestand in `images/` vervangen door een nieuwe foto
  met dezelfde bestandsnaam (of de verwijzing ernaar aanpassen).

Micha past het aan, "commit" en "pusht" het naar GitHub, en de
website bouwt zichzelf automatisch opnieuw op (net als bij een
tekstwijziging — 30 tot 90 seconden).

Het **contactformulier** hoeft niemand meer aan te sluiten — dat
werkt via **Formspree** en stuurt berichten automatisch naar
`info@uitvaartendan.nl`. Wil je dat naar een ander e-mailadres laten
sturen? Dat regelt Micha via het Formspree-dashboard, geen wijziging
in de website zelf nodig.

---

## 3. Hoe dit allemaal werkt (achtergrond)

Handig om te weten, niet iets waar je iets voor hoeft te doen:

- Je website staat in een **GitHub-repository** (een soort online
  projectmap met geschiedenis van alle wijzigingen) en wordt
  gehost via **GitHub Pages**.
- Het beheerscherm op `/admin` heet **TinaCMS**, en werkt via
  **Tina Cloud**. Als je daar opslaat, wordt er automatisch een
  wijziging weggeschreven naar die GitHub-repository.
- Die wijziging in GitHub start automatisch een nieuwe build van de
  website (via GitHub Actions), die de site opnieuw opbouwt en live
  zet. Dat is de reden voor de wachttijd van 30-90 seconden na het
  opslaan.
- Inloggen op `/admin` gaat via je **Tina Cloud**-account — dat regelt
  wie er mag inloggen, los van de website zelf.

---

## Hulp nodig?

Kom je er niet uit, of wil je liever dat iemand anders dit voor je
doet? Dat kan altijd. Bewaar deze handleiding dan gewoon voor later.
