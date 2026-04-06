<?php
session_start();
require 'db.php';
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

$alert = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $pseudo      = trim($_POST['pseudo'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $pass        = $_POST['pass'] ?? '';
    $passConfirm = $_POST['passConfirm'] ?? '';

    if(empty($pseudo) || empty($email) || empty($pass) || empty($passConfirm)){
        $alert = ['type'=>'error','msg'=>"Tous les champs sont obligatoires"];
    } elseif($pass !== $passConfirm){
        $alert = ['type'=>'error','msg'=>"Les mots de passe ne correspondent pas"];
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $alert = ['type'=>'error','msg'=>"Email invalide"];
    } else {
        $stmt = $pdo->prepare("SELECT id_cli FROM clients WHERE pseudo=? OR email=?");
        $stmt->execute([$pseudo, $email]);
        if($stmt->rowCount() > 0){
            $alert = ['type'=>'error','msg'=>"Pseudo ou email déjà utilisé"];
        } else {
            $mdp_hache = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO clients (pseudo, email, mdp) VALUES (?, ?, ?)");
            $stmt->execute([$pseudo, $email, $mdp_hache]);
            $user_id = $pdo->lastInsertId();
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO email_confirmations (id_cli, code) VALUES (?, ?)");
            $stmt->execute([$user_id, $code]);
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USERNAME;
                $mail->Password   = MAIL_APP_PASSWORD;
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($email, $pseudo);
                $mail->isHTML(true);
                $mail->Subject = 'Confirmation de votre compte sur OpenQuiz';
                $safePseudo = htmlspecialchars($pseudo, ENT_QUOTES, 'UTF-8');
                $mail->Body = '
<div style="font-family: Arial, sans-serif; max-width:600px; margin:0 auto; padding:20px; background:#f4f6fb;">
    <div style="text-align:center; margin-bottom:25px;">
        <img src="https://i.postimg.cc/br3v7j4V/logo.png" style="width:120px;">
    </div>
    <div style="background:#fff; padding:25px; border-radius:12px;">
        <h2 style="color:#224e6f;">Bonjour '.$safePseudo.',</h2>
        <p>Merci pour votre inscription sur <b>OpenQuiz</b>. Veuillez confirmer votre adresse e-mail.</p>
        <p style="font-size:18px; font-weight:bold;">Code : <span style="color:#224e6f;">'.$code.'</span></p>
        <p style="text-align:center;">
            <a href="'.BASE_URL.'/verify.php?code='.$code.'" style="padding:12px 30px; background:#224e6f; color:#fff; text-decoration:none; border-radius:5px;">Confirmer</a>
        </p>
        <p style="font-size:12px;">Si vous n\'êtes pas à l\'origine de cette inscription, ignorez ce message.</p>
    </div>
    <div style="text-align:center; margin-top:20px;">
        <img src="https://i.postimg.cc/ZnTRsqsy/facebook.avif" width="24">
        <img src="https://i.postimg.cc/J01nYhYP/insta.jpg" width="24">
        <img src="https://i.postimg.cc/vTQB2m2P/what.avif" width="24">
    </div>
    <p style="text-align:center; font-size:12px;">© '.date('Y').' OpenQuiz</p>
</div>';
                $mail->send();
                $alert = ['type'=>'success','msg'=>"Inscription réussie ! Vérifiez votre email"];
            } catch (Exception $e) {
                $alert = ['type'=>'warning','msg'=>"Erreur mail : ".$mail->ErrorInfo];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inscription — OpenQuiz</title>
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
  --yellow:    #f39c12;
  --txt:       #e8edf2;
  --txt-dim:   rgba(232,237,242,0.45);
  --txt-faint: rgba(232,237,242,0.18);
  --mono:      'IBM Plex Mono', monospace;
  --serif:     'DM Serif Display', serif;
  --sans:      'DM Sans', sans-serif;
  --radius:    14px;
}

html { height: 100%; }

body {
  font-family: var(--sans);
  background: var(--bg);
  color: var(--txt);
  min-height: 100svh;
  display: flex; flex-direction: column;
  opacity: 0; transform: translateY(12px);
  transition: opacity 0.55s ease, transform 0.55s ease;
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
.nav-logo { font-family: var(--mono); font-size: 14px; font-weight: 600; color: var(--txt); text-decoration: none; }
.nav-logo span { color: var(--blue); }
.nav-links { display: flex; gap: 28px; }
.nav-links a {
  font-family: var(--mono); font-size: 11px; font-weight: 400;
  letter-spacing: 1px; text-transform: uppercase;
  color: var(--txt-dim); text-decoration: none; transition: color 0.2s;
}
.nav-links a:hover { color: var(--txt); }

/* LAYOUT — 2 cols */
main {
  flex: 1; position: relative; z-index: 1;
  display: grid; grid-template-columns: 1fr 1fr;
  min-height: 100svh; padding-top: 60px;
}

/* LEFT — video panel */
.panel-left {
  position: relative; overflow: hidden;
  border-right: 1px solid var(--blue-line);
}

.panel-video {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  filter: brightness(0.35) contrast(1.05) saturate(1.1);
}

.panel-left-content {
  position: relative; z-index: 2;
  display: flex; flex-direction: column;
  justify-content: flex-end;
  height: 100%; padding: 52px 48px;
  background: linear-gradient(to top, rgba(8,14,20,0.9) 0%, transparent 60%);
}

.panel-tag {
  font-family: var(--mono); font-size: 10px; font-weight: 500;
  letter-spacing: 3px; text-transform: uppercase;
  color: var(--blue); margin-bottom: 14px;
  display: flex; align-items: center; gap: 10px;
}
.panel-tag::before { content: ''; width: 20px; height: 1px; background: var(--blue); opacity: 0.6; }

.panel-title {
  font-family: var(--serif);
  font-size: clamp(32px, 3.5vw, 50px);
  line-height: 1.08; color: var(--txt);
  margin-bottom: 14px;
}
.panel-title em { font-style: italic; color: var(--blue); }

.panel-desc {
  font-family: var(--mono); font-size: 12px; line-height: 1.9;
  color: var(--txt-dim); max-width: 380px; margin-bottom: 24px;
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

/* RIGHT — form */
.panel-right {
  display: flex; flex-direction: column;
  justify-content: center; align-items: center;
  padding: 60px 5vw;
  overflow-y: auto;
}

.form-wrap {
  width: 100%; max-width: 380px;
  display: flex; flex-direction: column;
  animation: formIn 0.6s ease both; animation-delay: 0.12s;
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

/* Alerts */
.alert {
  font-family: var(--mono); font-size: 12px;
  padding: 12px 16px; border-radius: 10px;
  margin-bottom: 20px; line-height: 1.6;
  animation: alertIn 0.4s ease;
}
@keyframes alertIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
.alert-error   { background: rgba(231,76,60,0.12);  border: 1px solid rgba(231,76,60,0.35);  color: #f1948a; }
.alert-success { background: rgba(46,204,113,0.12); border: 1px solid rgba(46,204,113,0.35); color: #82e0aa; }
.alert-warning { background: rgba(243,156,18,0.12); border: 1px solid rgba(243,156,18,0.35); color: #f8c471; }

/* Fields */
.field-group { display: flex; flex-direction: column; gap: 7px; margin-bottom: 14px; }
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
.field-input.valid   { border-color: var(--green); }
.field-input.invalid { border-color: var(--red); }

.eye-toggle {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  cursor: pointer; width: 18px; height: 18px; fill: var(--txt-faint);
  transition: fill 0.2s;
}
.eye-toggle:hover { fill: var(--txt-dim); }

/* Submit */
.btn-submit {
  font-family: var(--mono); font-size: 12px; font-weight: 500;
  letter-spacing: 1.5px; text-transform: uppercase;
  width: 100%; padding: 15px;
  background: var(--blue); color: #fff;
  border: none; border-radius: var(--radius);
  cursor: pointer; margin-top: 10px;
  transition: background 0.2s, transform 0.15s;
}
.btn-submit:hover { background: var(--blue-dim); transform: translateY(-1px); }

.form-footer {
  font-family: var(--mono); font-size: 11px;
  color: var(--txt-faint); text-align: center; margin-top: 18px;
}
.form-footer a { color: var(--blue); text-decoration: none; }
.form-footer a:hover { text-decoration: underline; }

/* RESPONSIVE */
@media (max-width: 780px) {
  main { grid-template-columns: 1fr; }
  .panel-left { min-height: 220px; max-height: 260px; }
  .panel-left-content { padding: 28px 24px; }
  .panel-title { font-size: 26px; }
  .panel-desc { display: none; }
  .panel-right { padding: 32px 24px 48px; }
  .form-wrap { max-width: 100%; }
}

@media (max-width: 400px) {
  .panel-left { min-height: 160px; }
}
</style>
</head>
<body>

<nav>
  <a class="nav-logo" href="acceuil.php"><span>//</span> OpenQuiz</a>
  <div class="nav-links">
    <a href="acceuil.php">Accueil</a>
    <a href="login.php">Connexion</a>
  </div>
</nav>

<main>
  <!-- LEFT -->
  <div class="panel-left">
    <video class="panel-video" src="assests/lv_0_20260325165205.mp4" autoplay loop muted playsinline></video>
    <div class="panel-left-content">
      <div class="panel-tag">Inscription</div>
      <h1 class="panel-title">Entre dans<br>le <em>jeu.</em></h1>
      <p class="panel-desc">
        Teste tes connaissances, améliore ton score
        et amuse-toi en apprenant. Gratuit, sans pub.
      </p>
      <a href="login.php" class="panel-link">J'ai déjà un compte</a>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="panel-right">
    <div class="form-wrap">
      <h2 class="form-heading">Créer un compte</h2>

      <?php if($alert): ?>
        <?php
          $cls = 'alert-error';
          if($alert['type'] === 'success') $cls = 'alert-success';
          elseif($alert['type'] === 'warning') $cls = 'alert-warning';
          $safeMsg = htmlspecialchars($alert['msg'], ENT_QUOTES, 'UTF-8');
        ?>
        <div class="alert <?php echo $cls; ?>"><?php echo $safeMsg; ?></div>
      <?php endif; ?>

      <form method="POST" onsubmit="return verifyPassword()">
        <div class="field-group">
          <div class="field-label">Email</div>
          <input class="field-input" type="email" name="email" placeholder="vous@exemple.com" required autocomplete="email">
        </div>

        <div class="field-group">
          <div class="field-label">Pseudo</div>
          <input class="field-input" type="text" name="pseudo" placeholder="@user1234" required autocomplete="username">
        </div>

        <div class="field-group">
          <div class="field-label">Mot de passe</div>
          <div class="field-wrap">
            <input class="field-input" type="password" id="pass" name="pass" placeholder="••••••••" required autocomplete="new-password">
            <svg class="eye-toggle" onclick="togglePassword('pass', this)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>
              <circle cx="12" cy="12" r="2.5"/>
            </svg>
          </div>
        </div>

        <div class="field-group">
          <div class="field-label">Confirmer le mot de passe</div>
          <div class="field-wrap">
            <input class="field-input" type="password" id="passConfirm" name="passConfirm" placeholder="••••••••" required autocomplete="new-password">
            <svg class="eye-toggle" onclick="togglePassword('passConfirm', this)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>
              <circle cx="12" cy="12" r="2.5"/>
            </svg>
          </div>
        </div>

        <button class="btn-submit" type="submit">Créer mon compte →</button>
      </form>

      <p class="form-footer">Déjà inscrit ? <a href="login.php">Se connecter</a></p>
    </div>
  </div>
</main>

<script>
window.addEventListener('load', function() { document.body.classList.add('visible'); });

function togglePassword(id, el) {
  var input = document.getElementById(id);
  input.type = input.type === 'password' ? 'text' : 'password';
  el.style.fill = input.type === 'password' ? '' : '#3a7bd5';
}

var passInput   = document.getElementById('pass');
var confirmInput = document.getElementById('passConfirm');

confirmInput.addEventListener('input', function() {
  if (!confirmInput.value) {
    passInput.classList.remove('valid','invalid');
    confirmInput.classList.remove('valid','invalid');
    return;
  }
  var match = passInput.value === confirmInput.value;
  passInput.classList.toggle('valid', match);
  passInput.classList.toggle('invalid', !match);
  confirmInput.classList.toggle('valid', match);
  confirmInput.classList.toggle('invalid', !match);
});

function verifyPassword() {
  if (passInput.value !== confirmInput.value) {
    confirmInput.classList.add('invalid');
    return false;
  }
  return true;
}
</script>
<?php include __DIR__ . '/joe_widget.php'; ?>

</body>
</html>