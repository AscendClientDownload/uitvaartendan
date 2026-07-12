// Vangt de link uit een Netlify Identity-uitnodigings-/reset-e-mail op
// (die opent op de hoofdpagina) en stuurt door naar /admin/, waar het
// eigenlijke inlogscherm de link verder afhandelt.
if (
  window.location.hash &&
  window.location.hash.match(/access_token|confirmation_token|invite_token|recovery_token|error/)
) {
  window.location.href = "/admin/" + window.location.hash;
}
