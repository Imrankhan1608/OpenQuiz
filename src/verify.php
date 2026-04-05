<?php
session_start();
require 'db.php';
require 'config.php';

function clean_input($data){
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if(isset($_GET['code'])){
    $code = clean_input($_GET['code']);
    $stmt = $pdo->prepare("SELECT * FROM email_confirmations WHERE code=? AND est_valide=1 LIMIT 1");
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    if($row){
        $stmt = $pdo->prepare("UPDATE clients SET email_verified=1 WHERE id_cli=?");
        $stmt->execute([$row['id_cli']]);
        $stmt = $pdo->prepare("UPDATE email_confirmations SET est_valide=0 WHERE id_confirmation=?");
        $stmt->execute([$row['id_confirmation']]);
        header("Location: login.php?verified=1");
        exit;
    } else {
        header("Location: login.php?verified=0");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
// The HTML below is never reached (all paths exit above),
// but kept as fallback page in case of future refactor.
$type    = 'success';
$message = 'Email confirmé.';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmation Email — OpenQuiz</title>
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
  --radius:    14px;
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg); color: var(--txt);
  min-height: 100svh; display: flex; flex-direction: column;
}

body::before {
  content: '';
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
  background-size: 200px; opacity: 0.55;
}

nav {
  position: relative; z-index: 10;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 5vw; height: 60px;
  border-bottom: 1px solid var(--blue-line);
}
.nav-logo { font-family: var(--mono); font-size: 14px; font-weight: 600; color: var(--txt); text-decoration: none; }
.nav-logo span { color: var(--blue); }

main {
  flex: 1; position: relative; z-index: 1;
  display: flex; align-items: center; justify-content: center;
  padding: 40px 24px;
}

.verify-card {
  background: var(--bg2); border: 1px solid var(--blue-line);
  border-radius: 20px; padding: 52px 44px;
  max-width: 420px; width: 100%; text-align: center;
  animation: cardIn 0.5s ease;
}
@keyframes cardIn { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }

.verify-icon {
  width: 56px; height: 56px; border-radius: 50%;
  margin: 0 auto 24px;
  display: flex; align-items: center; justify-content: center;
  font-size: 24px;
}
.icon-success { background: rgba(46,204,113,0.12); border: 1px solid rgba(46,204,113,0.3); }
.icon-error   { background: rgba(231,76,60,0.12);  border: 1px solid rgba(231,76,60,0.3); }

.verify-title {
  font-family: var(--serif);
  font-size: 28px; color: var(--txt); margin-bottom: 12px;
}

.verify-msg {
  font-family: var(--mono); font-size: 13px;
  line-height: 1.8; color: var(--txt-dim);
  margin-bottom: 32px;
}
.verify-msg.success { color: #82e0aa; }
.verify-msg.error   { color: #f1948a; }

.btn-login {
  font-family: var(--mono); font-size: 12px; font-weight: 500;
  letter-spacing: 1.5px; text-transform: uppercase;
  display: inline-block; padding: 14px 32px;
  background: var(--blue); color: #fff;
  border-radius: var(--radius); text-decoration: none;
  transition: background 0.2s, transform 0.15s;
}
.btn-login:hover { background: var(--blue-dim); transform: translateY(-1px); }
</style>
</head>
<body>

<nav>
  <a class="nav-logo" href="acceuil.php"><span>//</span> OpenQuiz</a>
</nav>

<main>
  <div class="verify-card">
    <?php if($type === 'success'): ?>
      <div class="verify-icon icon-success">✓</div>
      <h1 class="verify-title">Email confirmé</h1>
      <p class="verify-msg success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php else: ?>
      <div class="verify-icon icon-error">✗</div>
      <h1 class="verify-title">Lien invalide</h1>
      <p class="verify-msg error"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <a class="btn-login" href="login.php">Se connecter →</a>
  </div>
</main>

</body>
</html>