<?php global $Wcms ?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if ($Wcms->loggedIn): ?>
  <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src https://fonts.gstatic.com; img-src 'self' data:; frame-src https://www.google.com; form-action https://formspree.io; base-uri 'self';">
  <?php else: ?>
  <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; frame-src https://www.google.com; form-action https://formspree.io; base-uri 'self';">
  <?php endif; ?>
  <title><?= $Wcms->page('title') ?: 'Uitvaart en dan? | Praktische begeleiding voor nabestaanden in Houten' ?></title>
  <meta name="description" content="<?= $Wcms->page('description') ?: 'Na een uitvaart begint het pas. Annabelle Zaal helpt nabestaanden stap voor stap met de praktische zaken die na een overlijden geregeld moeten worden.' ?>">

  <meta property="og:title" content="Uitvaart en dan? | Praktische begeleiding voor nabestaanden">
  <meta property="og:description" content="Persoonlijke en praktische ondersteuning voor nabestaanden in de periode na een uitvaart.">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="nl_NL">
  <meta property="og:url" content="https://www.uitvaartendan.nl/">
  <!-- VERVANG MET FOTO: zet een og-image.jpg (1200x630px) in deze map en zet de regel hieronder aan -->
  <!-- <meta property="og:image" content="https://www.uitvaartendan.nl/og-image.jpg"> -->
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="Uitvaart en dan? | Praktische begeleiding voor nabestaanden">
  <meta name="twitter:description" content="Persoonlijke en praktische ondersteuning voor nabestaanden in de periode na een uitvaart.">

  <link rel="canonical" href="https://www.uitvaartendan.nl/">

  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%238FA593'/%3E%3Ctext x='32' y='43' font-family='Georgia, serif' font-size='30' fill='white' text-anchor='middle'%3EU%3C/text%3E%3C/svg%3E">
  <meta name="theme-color" content="#8FA593">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/style.css">

  <!-- Structured data voor Google (bedrijfsnaam, adres, telefoon, openingstijden).
       Dit stuk verandert niet automatisch mee met de blokken hierboven — als je
       telefoonnummer of adres structureel wijzigt, vraag Micha dit ook hier aan te passen. -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "ProfessionalService",
    "name": "Uitvaart en dan?",
    "description": "Praktische en persoonlijke begeleiding voor nabestaanden na een uitvaart.",
    "url": "https://www.uitvaartendan.nl/",
    "telephone": "+31619421856",
    "email": "info@uitvaartendan.nl",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Riddersborch 5",
      "postalCode": "3992 BG",
      "addressLocality": "Houten",
      "addressCountry": "NL"
    },
    "openingHoursSpecification": {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
      "opens": "09:00",
      "closes": "21:00"
    },
    "areaServed": "Houten en omgeving",
    "priceRange": "€€"
  }
  </script>

  <?= $Wcms->css() ?>
</head>
<body>

  <?= $Wcms->settings() ?>
  <?= $Wcms->alerts() ?>

  <a href="#main-content" class="skip-link">Ga direct naar de inhoud</a>

  <header class="site-header">
    <div class="nav-inner">
      <a href="#home" class="nav-logo">Uitvaart en dan?</a>
      <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navMenu" aria-label="Menu openen">
        <span></span><span></span><span></span>
      </button>
      <nav id="navMenu" class="nav-menu">
        <ul class="nav-list">
          <li><a href="#home" class="nav-link" data-section="#home">Home</a></li>
          <li><a href="#over-ons" class="nav-link" data-section="#over-ons">Over mij</a></li>
          <li><a href="#diensten" class="nav-link" data-section="#diensten">Diensten</a></li>
          <li><a href="#contact" class="nav-link" data-section="#contact">Contact</a></li>
        </ul>
        <a href="#contact" class="btn btn-primary nav-cta">Neem contact op</a>
      </nav>
    </div>
  </header>

  <main id="main-content" tabindex="-1">

    <?php if (!$Wcms->currentPageExists): ?>

    <!-- Dit is de inlogpagina (of een niet-bestaande pagina) — WonderCMS
         vult hier automatisch het inlogformulier of een "niet gevonden"
         melding in. -->
    <section class="section">
      <div class="container" style="max-width: 480px; text-align: center;">
        <?= $Wcms->page('content') ?>
      </div>
    </section>

    <?php else: ?>

    <!-- ============================================================ -->
    <!-- HOME -->
    <!-- ============================================================ -->
    <section class="hero" id="home">
      <div class="container hero-inner">
        <div class="hero-text">
          <h1><?= $Wcms->block('home-hero-titel') ?></h1>
          <div class="wcms-text"><?= $Wcms->block('home-hero-ondertitel') ?></div>
          <div class="hero-actions">
            <a href="#contact" class="btn btn-primary"><?= $Wcms->block('home-hero-knop') ?></a>
            <a href="#diensten" class="btn btn-secondary">Diensten</a>
          </div>
        </div>
        <div class="hero-image" aria-hidden="true">
          <!-- VERVANG MET FOTO: portret van Annabelle of sfeerfoto -->
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section-header">
          <span class="eyebrow">Welkom</span>
          <h2><?= $Wcms->block('home-intro-titel') ?></h2>
          <div class="wcms-text"><?= $Wcms->block('home-intro-tekst') ?></div>
        </div>

        <div class="card-grid">
          <div class="card">
            <div class="card-icon" aria-hidden="true"></div>
            <h3>Overzicht</h3>
            <p>Samen breng ik in kaart wat er allemaal geregeld moet worden, zodat jij niet meer alleen hoeft te zoeken.</p>
          </div>
          <div class="card">
            <div class="card-icon" aria-hidden="true" style="background: var(--color-gold-light);"></div>
            <h3>Persoonlijk contact</h3>
            <p>Geen protocol, maar een luisterend oor en begeleiding die past bij jouw tempo en situatie.</p>
          </div>
          <div class="card">
            <div class="card-icon" aria-hidden="true" style="background: var(--color-lavender-light);"></div>
            <h3>Praktische hulp</h3>
            <p>Van instanties bellen tot papieren op orde brengen — ik help je met de stappen die nu nodig zijn.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================ -->
    <!-- OVER MIJ -->
    <!-- ============================================================ -->
    <section class="section section--alt" id="over-ons">
      <div class="container">
        <div class="split">
          <div class="split-image split-image--sage" aria-hidden="true">
            <!-- VERVANG MET FOTO: portretfoto van Annabelle Zaal -->
          </div>
          <div class="split-text">
            <span class="eyebrow">Over mij</span>
            <h2><?= $Wcms->block('over-ons-titel') ?></h2>
            <div class="wcms-text"><?= $Wcms->block('over-ons-intro') ?></div>
          </div>
        </div>

        <div class="prose" style="max-width: 720px; margin: 48px auto 0;">
          <div class="wcms-text"><?= $Wcms->block('over-ons-paragraaf-1') ?></div>
          <div class="wcms-text"><?= $Wcms->block('over-ons-paragraaf-2') ?></div>
          <div class="wcms-text"><?= $Wcms->block('over-ons-paragraaf-3') ?></div>
        </div>

        <div class="quote-block" style="max-width: 720px; margin-left: auto; margin-right: auto;">
          <div class="wcms-text"><?= $Wcms->block('over-ons-citaat') ?></div>
        </div>

        <div class="section-header">
          <span class="eyebrow">Missie</span>
          <h2><?= $Wcms->block('over-ons-missie-titel') ?></h2>
          <div class="wcms-text"><?= $Wcms->block('over-ons-missie-tekst') ?></div>
        </div>

        <div class="tag-list" style="justify-content: center;">
          <span class="tag">Persoonlijk contact</span>
          <span class="tag">Praktische organisatie</span>
          <span class="tag">Aanpasbaar per situatie</span>
        </div>
      </div>
    </section>

    <!-- ============================================================ -->
    <!-- DIENSTEN -->
    <!-- ============================================================ -->
    <section class="section" id="diensten">
      <div class="container">
        <div class="section-header">
          <span class="eyebrow">Diensten</span>
          <h2><?= $Wcms->block('diensten-titel') ?></h2>
          <div class="wcms-text"><?= $Wcms->block('diensten-intro') ?></div>
        </div>

        <div class="pricing-grid">

          <div class="pricing-card">
            <span class="eyebrow"><?= $Wcms->block('diensten-pakket1-naam') ?></span>
            <h3 class="pricing-price"><?= $Wcms->block('diensten-pakket1-prijs') ?></h3>
            <div class="wcms-text"><?= $Wcms->block('diensten-pakket1-beschrijving') ?></div>
            <div class="pricing-list-wrap"><?= $Wcms->block('diensten-pakket1-items') ?></div>
            <a href="#contact" class="btn btn-primary btn-block">Neem contact op</a>
          </div>

          <div class="pricing-card pricing-card--placeholder">
            <span class="eyebrow"><?= $Wcms->block('diensten-pakket2-naam') ?></span>
            <h3 class="pricing-price"><?= $Wcms->block('diensten-pakket2-prijs') ?></h3>
            <div class="wcms-text"><?= $Wcms->block('diensten-pakket2-beschrijving') ?></div>
            <div class="pricing-list-wrap"><?= $Wcms->block('diensten-pakket2-items') ?></div>
            <a href="#contact" class="btn btn-secondary btn-block">Neem contact op</a>
          </div>

        </div>

        <div class="note-box">
          <h3><?= $Wcms->block('diensten-opmaat-titel') ?></h3>
          <div class="wcms-text" style="margin-top: 10px;"><?= $Wcms->block('diensten-opmaat-tekst') ?></div>
        </div>
      </div>
    </section>

    <section class="section section--alt">
      <div class="container">
        <div class="section-header">
          <span class="eyebrow">Professionals</span>
          <h2><?= $Wcms->block('diensten-doelgroepen-titel') ?></h2>
          <div class="wcms-text"><?= $Wcms->block('diensten-doelgroepen-tekst') ?></div>
        </div>

        <div class="tag-list" style="justify-content: center;">
          <span class="tag">Uitvaartorganisaties</span>
          <span class="tag">Notarissen</span>
          <span class="tag">Mantelzorgorganisaties</span>
          <span class="tag">Hospices</span>
          <span class="tag">Verpleegtehuizen</span>
          <span class="tag">WMO-loketten</span>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="cta-band">
          <h2><?= $Wcms->block('home-cta-titel') ?></h2>
          <div class="wcms-text"><?= $Wcms->block('home-cta-tekst') ?></div>
          <a href="#contact" class="btn btn-primary"><?= $Wcms->block('home-cta-knop') ?></a>
        </div>
      </div>
    </section>

    <!-- ============================================================ -->
    <!-- CONTACT -->
    <!-- ============================================================ -->
    <section class="section section--alt" id="contact">
      <div class="container">
        <div class="section-header" style="margin-bottom: 48px;">
          <span class="eyebrow">Contact</span>
          <h2><?= $Wcms->block('contact-titel') ?></h2>
          <div class="wcms-text"><?= $Wcms->block('contact-intro') ?></div>
        </div>

        <div class="contact-grid">

          <div>
            <h3 style="margin-bottom: 28px;"><?= $Wcms->block('contact-gegevens-titel') ?></h3>

            <div class="contact-info-item">
              <div class="contact-info-icon" aria-hidden="true"></div>
              <div>
                <h3>Telefoon</h3>
                <a href="tel:0619421856"><?= $Wcms->block('bedrijf-telefoon') ?></a>
              </div>
            </div>

            <div class="contact-info-item">
              <div class="contact-info-icon" aria-hidden="true" style="background: var(--color-gold-light);"></div>
              <div>
                <h3>E-mail</h3>
                <a href="mailto:info@uitvaartendan.nl"><?= $Wcms->block('bedrijf-email') ?></a>
              </div>
            </div>

            <div class="contact-info-item">
              <div class="contact-info-icon" aria-hidden="true" style="background: var(--color-lavender-light);"></div>
              <div>
                <h3>Adres</h3>
                <div class="wcms-text"><?= $Wcms->block('bedrijf-straat') ?><br><?= $Wcms->block('bedrijf-postcode-plaats') ?></div>
              </div>
            </div>

            <div class="contact-info-item">
              <div class="contact-info-icon" aria-hidden="true"></div>
              <div>
                <h3>Openingstijden</h3>
                <div class="wcms-text"><?= $Wcms->block('bedrijf-openingstijden') ?></div>
              </div>
            </div>

            <h3 style="margin-top: 40px;"><?= $Wcms->block('contact-kaart-titel') ?></h3>
            <div class="map-embed">
              <!-- VERVANG DIT MET DE GOOGLE MAPS EMBED-CODE VOOR RIDDERSBORCH 5, 3992 BG HOUTEN -->
              <iframe
                src="https://www.google.com/maps?q=Riddersborch+5,+3992+BG+Houten&output=embed"
                title="Kaart met de locatie van Uitvaart en dan? in Houten"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
              </iframe>
            </div>
          </div>

          <div class="form-card">
            <h3 style="margin-bottom: 24px;"><?= $Wcms->block('contact-formulier-titel') ?></h3>

            <!-- VERVANG DIT MET JE FORMSPREE ENDPOINT (bv. https://formspree.io/f/abcd1234) -->
            <form action="https://formspree.io/f/VERVANG_MET_JE_FORMSPREE_ID" method="POST">

              <!-- Onderwerp van de e-mail die Annabelle ontvangt -->
              <input type="hidden" name="_subject" value="Nieuw bericht via website Uitvaart en dan?">

              <!-- Spamval: onzichtbaar voor mensen, spam-robots vullen dit vaak automatisch in.
                   Formspree negeert een bericht stil als dit veld is ingevuld. Niet verwijderen. -->
              <input type="text" name="_gotcha" class="form-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

              <div class="form-group">
                <label for="naam">Naam</label>
                <input type="text" id="naam" name="naam" required autocomplete="name">
              </div>

              <div class="form-group">
                <label for="email">E-mailadres</label>
                <input type="email" id="email" name="email" required autocomplete="email">
              </div>

              <div class="form-group">
                <label for="telefoon">Telefoonnummer</label>
                <input type="tel" id="telefoon" name="telefoon" autocomplete="tel">
              </div>

              <div class="form-group">
                <label for="bericht">Bericht</label>
                <textarea id="bericht" name="bericht" required></textarea>
              </div>

              <button type="submit" class="btn btn-primary btn-block">Verstuur bericht</button>
              <p class="form-note">Je bericht komt rechtstreeks in de mailbox van Annabelle terecht. We reageren zo snel mogelijk.</p>
            </form>
          </div>

        </div>
      </div>
    </section>

    <?php endif; ?>

  </main>

  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-col">
        <p class="footer-logo">Uitvaart en dan?</p>
        <div class="wcms-text footer-text"><?= $Wcms->block('footer-tekst') ?></div>
      </div>
      <div class="footer-col">
        <p class="footer-heading">Contact</p>
        <div class="wcms-text"><a href="tel:0619421856"><?= $Wcms->block('bedrijf-telefoon') ?></a></div>
        <div class="wcms-text"><a href="mailto:info@uitvaartendan.nl"><?= $Wcms->block('bedrijf-email') ?></a></div>
        <div class="wcms-text"><?= $Wcms->block('bedrijf-straat') ?><br><?= $Wcms->block('bedrijf-postcode-plaats') ?></div>
      </div>
      <div class="footer-col">
        <p class="footer-heading">Openingstijden</p>
        <div class="wcms-text"><?= $Wcms->block('bedrijf-openingstijden') ?></div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Uitvaart en dan? — <?= $Wcms->block('footer-copyright') ?></p>
    </div>
  </footer>

  <script src="/nav.js" defer></script>
  <?= $Wcms->js() ?>
</body>
</html>
