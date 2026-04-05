<?php
/**
 * connexion.php — Page de connexion OpenQuiz
 * ==========================================
 * Gère l'authentification utilisateur.
 * Le token de session est transmis en cookie HttpOnly
 * via une redirection interne (jamais affiché dans l'URL).
 */

session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $pass   = $_POST['pass'] ?? '';

    // Validation basique
    if (!$pseudo || !$pass) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (strlen($pseudo) < 3) {
        $error = "Le pseudo doit contenir au moins 3 caractères.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE pseudo = ?");
        $stmt->execute([$pseudo]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['mdp'])) {
            // Vérification du compte
            if (!$user['email_verified']) {
                $error = "Vérifie ton email avant de te connecter. Consulte ta boîte mail.";
            } elseif ($user['status'] !== 'actif') {
                $error = "Ton compte est désactivé. Contacte le support.";
            } else {
                // Générer un token sécurisé (64 caractères hex = 32 bytes)
                $token  = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+2 hours'));

                // Supprimer les anciens tokens de cet utilisateur (nettoyage)
                $stmt = $pdo->prepare("DELETE FROM login_tokens WHERE id_cli = ? AND expiry < NOW()");
                $stmt->execute([$user['id_cli']]);

                // Insérer le nouveau token
                $stmt = $pdo->prepare(
                    "INSERT INTO login_tokens (token, id_cli, expiry) VALUES (?, ?, ?)"
                );
                $stmt->execute([$token, $user['id_cli'], $expiry]);

                /**
                 * SÉCURITÉ : Le token est passé UNE SEULE FOIS dans l'URL vers /quiz.
                 * Flask le lit, le place en cookie HttpOnly, puis redirige vers /quiz sans token.
                 * → Le token ne reste JAMAIS visible dans l'URL du navigateur.
                 */
                header("Location: http://localhost:5000/quiz?token=$token");
                exit;
            }
        } else {
            // Message volontairement vague pour éviter l'énumération des pseudos
            $error = "Pseudo ou mot de passe incorrect.";
        }
    }
}

// Message depuis verify.php (confirmation email)
$verified = $_GET['verified'] ?? null;
// Message d'erreur depuis Flask (ex: token expiré)
$flaskError = $_GET['error'] ?? null;
$flaskMessages = [
    'token_invalide'   => 'Lien de connexion invalide ou expiré. Reconnecte-toi.',
    'session_expiree'  => 'Ta session a expiré. Reconnecte-toi.',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — OpenQuiz</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=IBM+Plex+Mono:wght@300;400;500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

:root {
  --bg:        #080e14;
  --bg2:       #0d1620;
  --bg3:       #111d29;
  --blue:      #3a7bd5;
  --blue-dim:  #2a5fa8;
  --blue-glow: rgba(58,123,213,0.18);
  --blue-line: rgba(58,123,213,0.22);
  --green:     #2ecc71;
  --red:       #e74c3c;
  --txt:       #e8edf2;
  --txt-dim:   rgba(232,237,242,0.45);
  --txt-faint: rgba(232,237,242,0.18);
  --mono:      'IBM Plex Mono', monospace;
  --serif:     'DM Serif Display', serif;
  --sans:      'DM Sans', sans-serif;
  --radius:    14px;
}

html, body { height: 100%; }

body {
  font-family: var(--sans);
  background: var(--bg);
  color: var(--txt);
  min-height: 100svh;
  display: flex;
  flex-direction: column;
  opacity: 0;
  transform: translateY(12px);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
body.visible { opacity: 1; transform: translateY(0); }

body::before {
  content: '';
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
  background-size: 200px; opacity: 0.55;
}

/* NAV */
nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 50;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 5vw; height: 60px;
  background: rgba(8,14,20,0.88);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--blue-line);
}
.nav-logo {
  font-family: var(--mono); font-size: 14px; font-weight: 600;
  color: var(--txt); text-decoration: none;
}
.nav-logo span { color: var(--blue); }
.nav-links { display: flex; gap: 28px; }
.nav-links a {
  font-family: var(--mono); font-size: 11px; font-weight: 400;
  letter-spacing: 1px; text-transform: uppercase;
  color: var(--txt-dim); text-decoration: none; transition: color 0.2s;
}
.nav-links a:hover { color: var(--txt); }

/* LAYOUT */
main {
  flex: 1; position: relative; z-index: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100svh;
  padding-top: 60px;
}

/* LEFT PANEL */
.panel-left {
  display: flex; flex-direction: column;
  justify-content: center;
  padding: 60px 5vw 60px 6vw;
  border-right: 1px solid var(--blue-line);
}
.panel-tag {
  font-family: var(--mono); font-size: 10px; font-weight: 500;
  letter-spacing: 3px; text-transform: uppercase;
  color: var(--blue); margin-bottom: 18px;
  display: flex; align-items: center; gap: 10px;
}
.panel-tag::before { content: ''; display: inline-block; width: 20px; height: 1px; background: var(--blue); opacity: 0.6; }
.panel-title {
  font-family: var(--serif);
  font-size: clamp(36px, 4.5vw, 56px);
  line-height: 1.07; color: var(--txt);
  margin-bottom: 20px;
}
.panel-title em { font-style: italic; color: var(--blue); }
.panel-desc {
  font-family: var(--mono); font-size: 12px;
  line-height: 2; color: var(--txt-dim); max-width: 380px;
  margin-bottom: 32px;
}
.panel-link {
  font-family: var(--mono); font-size: 11px; font-weight: 500;
  letter-spacing: 1px; text-transform: uppercase;
  color: var(--txt-dim); text-decoration: none;
  display: inline-flex; align-items: center; gap: 8px;
  border: 1px solid var(--blue-line); border-radius: 99px;
  padding: 10px 20px; transition: all 0.2s; width: fit-content;
}
.panel-link:hover { border-color: var(--blue); color: var(--txt); }
.panel-link::after { content: '→'; }

/* RIGHT PANEL */
.panel-right {
  display: flex; flex-direction: column;
  justify-content: center; align-items: center;
  padding: 60px 5vw;
}
.form-wrap {
  width: 100%; max-width: 380px;
  display: flex; flex-direction: column;
  animation: formIn 0.55s ease both; animation-delay: 0.1s;
}
@keyframes formIn {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
.form-heading {
  font-family: var(--serif);
  font-size: clamp(26px, 3vw, 34px);
  color: var(--txt); margin-bottom: 28px;
}

/* Alerts inline */
.alert {
  font-family: var(--mono); font-size: 12px;
  padding: 13px 16px; border-radius: 10px;
  margin-bottom: 20px; line-height: 1.7;
  display: flex; align-items: flex-start; gap: 10px;
}
.alert-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; }
.alert-error   { background: rgba(231,76,60,0.1);  border: 1px solid rgba(231,76,60,0.3);  color: #f1948a; }
.alert-success { background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color: #82e0aa; }
.alert-info    { background: var(--blue-glow);     border: 1px solid var(--blue-line);      color: #7eb3f0; }

/* Fields */
.field-group { display: flex; flex-direction: column; gap: 7px; margin-bottom: 16px; }
.field-label {
  font-family: var(--mono); font-size: 10px; font-weight: 500;
  letter-spacing: 2px; text-transform: uppercase; color: var(--blue);
}
.field-wrap { position: relative; }
.field-input {
  font-family: var(--mono); font-size: 13px;
  width: 100%; padding: 13px 16px;
  background: var(--bg3); border: 1px solid var(--blue-line);
  color: var(--txt); border-radius: 10px; outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.field-input::placeholder { color: var(--txt-faint); }
.field-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-glow); }
.field-input.error { border-color: var(--red); box-shadow: 0 0 0 3px rgba(231,76,60,0.15); }

.eye-toggle {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  cursor: pointer; width: 18px; height: 18px; fill: var(--txt-faint);
  transition: fill 0.2s; background: none; border: none; padding: 0;
}
.eye-toggle:hover { fill: var(--txt-dim); }

/* Submit */
.btn-submit {
  font-family: var(--mono); font-size: 12px; font-weight: 500;
  letter-spacing: 1.5px; text-transform: uppercase;
  width: 100%; padding: 15px;
  background: var(--blue); color: #fff;
  border: none; border-radius: var(--radius);
  cursor: pointer; margin-top: 8px;
  transition: background 0.2s, transform 0.15s;
}
.btn-submit:hover { background: var(--blue-dim); transform: translateY(-1px); }
.btn-submit:active { transform: none; }
.btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.form-footer {
  font-family: var(--mono); font-size: 11px;
  color: var(--txt-faint); text-align: center;
  margin-top: 18px;
}
.form-footer a { color: var(--blue); text-decoration: none; }
.form-footer a:hover { text-decoration: underline; }

/* RESPONSIVE */
@media (max-width: 780px) {
  main { grid-template-columns: 1fr; }
  .panel-left { display: none; }
  .panel-right { padding: 80px 24px 48px; }
  .form-wrap { max-width: 100%; }
}
</style>
</head>
<body>

<nav>
  <a class="nav-logo" href="acceuil.php"><span>//</span> OpenQuiz</a>
  <div class="nav-links">
    <a href="acceuil.php">Accueil</a>
    <a href="acceuil.php#apropos">À propos</a>
  </div>
</nav>

<main>
  <!-- PANNEAU GAUCHE -->
  <div class="panel-left">
    <div class="panel-tag">Connexion</div>
    <h1 class="panel-title">Bon retour<br>parmi <em>nous.</em></h1>
    <p class="panel-desc">
      Des milliers de questions sur tous les domaines.
      Retrouve tes stats, reprends ta progression
      et continue d'apprendre en jouant.
    </p>
    <a href="inscription.php" class="panel-link">Créer un compte</a>
  </div>

  <!-- FORMULAIRE -->
  <div class="panel-right">
    <div class="form-wrap">
      <h2 class="form-heading">Connexion</h2>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <span class="alert-icon">⚠</span>
          <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($flaskError && isset($flaskMessages[$flaskError])): ?>
        <div class="alert alert-info">
          <span class="alert-icon">ℹ</span>
          <span><?php echo htmlspecialchars($flaskMessages[$flaskError], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($verified === '1'): ?>
        <div class="alert alert-success">
          <span class="alert-icon">✓</span>
          <span>Email confirmé avec succès. Tu peux maintenant te connecter !</span>
        </div>
      <?php elseif ($verified === '0'): ?>
        <div class="alert alert-error">
          <span class="alert-icon">⚠</span>
          <span>Ce lien de vérification est invalide ou a déjà été utilisé.</span>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="on" id="loginForm">
        <div class="field-group">
          <div class="field-label">Pseudo</div>
          <input
            class="field-input"
            type="text"
            name="pseudo"
            id="pseudoInput"
            placeholder="@user1234"
            required
            autocomplete="username"
            value="<?php echo htmlspecialchars($_POST['pseudo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
          >
        </div>

        <div class="field-group">
          <div class="field-label">Mot de passe</div>
          <div class="field-wrap">
            <input
              class="field-input"
              type="password"
              name="pass"
              id="passwordInput"
              placeholder="••••••••"
              required
              autocomplete="current-password"
            >
            <button type="button" class="eye-toggle" onclick="togglePwd('passwordInput', this)" title="Afficher/masquer">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="inherit" width="18" height="18">
                <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>
                <circle cx="12" cy="12" r="2.5"/>
              </svg>
            </button>
          </div>
        </div>

        <button class="btn-submit" type="submit" id="submitBtn">Se connecter →</button>
      </form>

      <p class="form-footer">
        Pas encore de compte ? <a href="inscription.php">S'inscrire</a>
      </p>
    </div>
  </div>
</main>

<script>
// Apparition de la page
window.addEventListener('load', function() {
  document.body.classList.add('visible');
});

// Toggle mot de passe
function togglePwd(inputId, btn) {
  var input = document.getElementById(inputId);
  var isVisible = input.type === 'text';
  input.type = isVisible ? 'password' : 'text';
  btn.style.fill = isVisible ? '' : '#3a7bd5';
  btn.title = isVisible ? 'Afficher' : 'Masquer';
}

// Désactiver le bouton pendant la soumission
document.getElementById('loginForm').addEventListener('submit', function() {
  var btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.textContent = 'Connexion en cours...';
});

// Retirer le style d'erreur à la saisie
document.querySelectorAll('.field-input').forEach(function(input) {
  input.addEventListener('input', function() {
    this.classList.remove('error');
  });
});
</script>
</body>
</html>