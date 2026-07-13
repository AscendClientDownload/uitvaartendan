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
2. Klik op **"Login with Netlify Identity"**.
3. Log in met het e-mailadres en wachtwoord waarmee Micha je heeft
   uitgenodigd. (De allereerste keer krijg je een uitnodigingsmail
   waarin je zelf een wachtwoord instelt — zie "De eerste keer"
   hieronder.)
4. Je ziet nu een lijst met onderdelen: **Bedrijfsgegevens, Menu,
   Home, Over mij, Diensten, Contact, Footer.** Klik op het onderdeel
   dat je wilt aanpassen.
5. Je ziet een formulier met invulvelden — één veld per tekst op de
   website, met duidelijke labels (bijvoorbeeld "Hoofdtitel (hero)").
   Pas de tekst aan in het veld.
6. Klik rechtsboven op **"Publish"** (of "Save" en daarna "Publish")
   om je wijziging op te slaan.

**Let op: de wijziging is niet meteen zichtbaar.** Na op "Publish"
te klikken, bouwt de website zichzelf opnieuw op — dat duurt meestal
**30 tot 90 seconden**. Ververs de website na een minuutje en je ziet
je nieuwe tekst staan.

Kom je een tekst tegen die begint met **"[INVULLEN]"**? Dat is een
plek die nog op jouw tekst wacht (bijvoorbeeld het tweede
dienstenpakket of een stuk van je persoonlijke verhaal). Vul het veld
gewoon in zoals elk ander tekstveld.

**De onderdelen "Diensten" → "Pakket 1" en "Pakket 2"** hebben een
lijstje "Onderdelen van dit pakket". Met de knoppen daaronder kun je
een regel toevoegen of verwijderen (bijvoorbeeld als een pakket een
extra dienst krijgt).

**Uitloggen:** klik linksboven op je naam/e-mailadres en kies
"Log out". Zeker op een gedeelde computer altijd even doen.

### De eerste keer

Micha nodigt je uit via e-mail (Netlify stuurt deze automatisch). In
die e-mail:
1. Klik op de link.
2. Je komt op de website terecht en wordt automatisch doorgestuurd
   naar het beheerscherm.
3. Stel daar je eigen wachtwoord in.
4. Vanaf nu log je in met je e-mailadres en dat wachtwoord.

**Wachtwoord vergeten?** Ga naar `/admin`, klik op "Login with
Netlify Identity" en dan op "Forgot password?" — je krijgt een
e-mail om een nieuw wachtwoord in te stellen.

---

## 2. Kleuren en foto's

Deze twee zijn iets technischer, omdat ze in de broncode van de
website staan (niet in het beheerscherm) en via GitHub gepubliceerd
moeten worden. **Vraag dit aan Micha** — onderstaande is vooral
bedoeld zodat je begrijpt wat er gebeurt:

- **Kleuren** staan in `style.css`, bovenaan bij `:root {`, als
  hexcodes (bijvoorbeeld `#8FA593`).
- **Foto's** vervangen de gekleurde placeholder-vlakken, herkenbaar
  aan `<!-- VERVANG MET FOTO: ... -->` in `index.html`.

Micha past het aan, "commit" en "pusht" het naar GitHub, en de
website bouwt zichzelf automatisch opnieuw op (net als bij een
tekstwijziging — 30 tot 90 seconden).

Het **contactformulier** hoeft niemand meer aan te sluiten — dat
werkt via Netlify Forms (ingebouwd in de hosting) en stuurt berichten
automatisch naar `info@uitvaartendan.nl`. Wil je dat naar een ander
e-mailadres laten sturen? Dat regelt Micha via het Netlify-dashboard
(Project configuration → Forms), geen wijziging in de website zelf
nodig.

---

## 3. Hoe dit allemaal werkt (achtergrond)

Handig om te weten, niet iets waar je iets voor hoeft te doen:

- Je website staat in een **GitHub-repository** (een soort online
  projectmap met geschiedenis van alle wijzigingen) en wordt
  gehost via **Netlify**.
- Het beheerscherm op `/admin` heet **Decap CMS**. Als je daar
  opslaat, wordt er automatisch een wijziging weggeschreven naar
  die GitHub-repository.
- Netlify ziet die wijziging, bouwt de website automatisch opnieuw
  op, en zet hem live. Dat is de reden voor de wachttijd van
  30-90 seconden na het opslaan.
- Inloggen op `/admin` gaat via **Netlify Identity** — dat regelt
  wie er mag inloggen, los van de website zelf.

---

## Hulp nodig?

Kom je er niet uit, of wil je liever dat iemand anders dit voor je
doet? Dat kan altijd. Bewaar deze handleiding dan gewoon voor later.
