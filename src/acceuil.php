<?php
// Connexion sécurisée PDO
$host    = 'localhost';
$db      = 'quiz_game';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    die("Une erreur est survenue. Veuillez reessayer.");
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$check     = $pdo->query("SHOW COLUMNS FROM sessions LIKE 'id_quiz'")->rowCount();
$join_quiz = $check ? "LEFT JOIN quiz q ON s.id_quiz = q.id_quiz" : "";
$col_theme = $check ? ", q.theme" : "";

$sql = "
    SELECT c.pseudo, s.score, s.nb_questions, s.is_active
    $col_theme
    FROM sessions s
    JOIN clients c ON s.id_cli = c.id_cli
    $join_quiz
    ORDER BY s.date_session DESC
    LIMIT 5
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$activities = $stmt->fetchAll();

$stmt2 = $pdo->prepare("SELECT COUNT(*) AS total FROM sessions WHERE DATE(date_session) = CURDATE()");
$stmt2->execute();
$stats = $stmt2->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OpenQuiz</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=IBM+Plex+Mono:wght@300;400;500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* ═══════════ RESET & BASE ═══════════ */
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

:root {
  --bg:        #080e14;
  --bg2:       #0d1620;
  --bg3:       #111d29;
  --blue:      #3a7bd5;
  --blue-dim:  #2a5fa8;
  --blue-glow: rgba(58,123,213,0.18);
  --blue-line: rgba(58,123,213,0.22);
  --txt:       #e8edf2;
  --txt-dim:   rgba(232,237,242,0.45);
  --txt-faint: rgba(232,237,242,0.18);
  --mono:      'IBM Plex Mono', monospace;
  --serif:     'DM Serif Display', serif;
  --sans:      'DM Sans', sans-serif;
  --radius:    14px;
}

html { scroll-behavior: smooth; }

body {
  background: var(--bg);
  color: var(--txt);
  font-family: var(--sans);
  min-height: 100vh;
  overflow-x: hidden;
}

/* noise grain overlay */
body::before {
  content: '';
  position: fixed; inset: 0; z-index: 0;
  pointer-events: none;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
  background-size: 200px;
  opacity: 0.55;
}

/* ═══════════ NAV ═══════════ */
nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 5vw;
  height: 64px;
  background: rgba(8,14,20,0.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--blue-line);
}

.nav-logo {
  font-family: var(--mono);
  font-size: 15px; font-weight: 600;
  letter-spacing: 0.5px;
  color: var(--txt);
  cursor: pointer;
  text-decoration: none;
}
.nav-logo span { color: var(--blue); }

.nav-links { display: flex; gap: 32px; }
.nav-links a {
  font-family: var(--mono);
  font-size: 12px; font-weight: 400;
  letter-spacing: 1px; text-transform: uppercase;
  color: var(--txt-dim);
  text-decoration: none;
  transition: color 0.2s;
}
.nav-links a:hover, .nav-links a.active { color: var(--txt); }

.nav-burger {
  display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 4px;
}
.nav-burger span {
  display: block; width: 22px; height: 2px;
  background: var(--txt); border-radius: 2px;
  transition: all 0.3s;
}

/* ═══════════ HERO ═══════════ */
.hero {
  min-height: 100svh;
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: center;
  gap: 60px;
  padding: 100px 5vw 60px;
  position: relative;
}

.hero::after {
  content: '';
  position: absolute;
  top: 10%; right: -10%;
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(58,123,213,0.07) 0%, transparent 70%);
  pointer-events: none;
}

.hero-eyebrow {
  font-family: var(--mono);
  font-size: 11px; font-weight: 500;
  letter-spacing: 2.5px; text-transform: uppercase;
  color: var(--blue);
  margin-bottom: 22px;
  display: flex; align-items: center; gap: 10px;
}
.hero-eyebrow::before {
  content: ''; display: inline-block;
  width: 28px; height: 1px;
  background: var(--blue); opacity: 0.6;
}

.hero-title {
  font-family: var(--serif);
  font-size: clamp(42px, 5.5vw, 72px);
  line-height: 1.06;
  color: var(--txt);
  margin-bottom: 28px;
}
.hero-title em {
  font-style: italic;
  color: var(--blue);
}

.hero-desc {
  font-family: var(--mono);
  font-size: clamp(12px, 1.3vw, 13px);
  line-height: 2;
  color: var(--txt-dim);
  max-width: 460px;
  margin-bottom: 40px;
}

.hero-btns {
  display: flex; gap: 14px; flex-wrap: wrap;
}

.btn-primary {
  font-family: var(--mono);
  font-size: 12px; font-weight: 500;
  letter-spacing: 1px; text-transform: uppercase;
  padding: 13px 28px;
  background: var(--blue);
  color: #fff;
  border-radius: var(--radius);
  text-decoration: none;
  transition: background 0.2s, transform 0.15s;
  border: 1px solid transparent;
}
.btn-primary:hover { background: var(--blue-dim); transform: translateY(-1px); }

.btn-outline {
  font-family: var(--mono);
  font-size: 12px; font-weight: 500;
  letter-spacing: 1px; text-transform: uppercase;
  padding: 13px 28px;
  background: transparent;
  color: var(--txt-dim);
  border-radius: var(--radius);
  text-decoration: none;
  border: 1px solid var(--blue-line);
  transition: border-color 0.2s, color 0.2s, transform 0.15s;
}
.btn-outline:hover { border-color: var(--blue); color: var(--txt); transform: translateY(-1px); }

/* ═══════════ COVER SLIDER ═══════════ */
.cover {
  position: relative;
  z-index: 1;
}

.slide-wrapper {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  aspect-ratio: 4/3;
  box-shadow: 0 32px 80px rgba(0,0,0,0.7), 0 0 0 1px var(--blue-line);
  background: var(--bg3);
  cursor: pointer;
}

.img-slide {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  opacity: 0;
  transition: opacity 0.9s ease;
}
.img-slide.active { opacity: 1; }

/* Video clarity enhancement */
video.img-slide {
  filter: contrast(1.05) saturate(1.1) brightness(1.02);
  image-rendering: crisp-edges;
}

.slide-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(8,14,20,0.9) 0%, transparent 55%);
  pointer-events: none;
}

.slide-info {
  position: absolute; bottom: 0; left: 0; right: 0;
  padding: 24px;
  pointer-events: none;
}
.slide-info h4 {
  font-family: var(--mono);
  font-size: 13px; font-weight: 500;
  color: var(--txt); margin-bottom: 4px;
}
.slide-info p {
  font-family: var(--mono);
  font-size: 11px; color: var(--txt-dim);
}
.slide-info a { color: var(--blue); text-decoration: none; }

.slide-dots {
  display: flex; justify-content: center; gap: 7px; margin-top: 16px;
}
.dot {
  width: 7px; height: 7px; border-radius: 99px;
  background: var(--txt-faint); cursor: pointer;
  transition: all 0.3s;
  border: none;
}
.dot.active { width: 24px; background: var(--blue); }

/* ═══════════ FUNFACT ═══════════ */
.funfact {
  padding: 80px 5vw;
  display: flex; justify-content: center;
}

.funfact-card {
  max-width: 760px; width: 100%;
  background: var(--bg2);
  border: 1px solid var(--blue-line);
  border-radius: 20px;
  padding: 48px 52px;
  position: relative;
  overflow: hidden;
}

.funfact-card::before {
  content: '"';
  position: absolute; top: -20px; left: 32px;
  font-family: var(--serif);
  font-size: 140px;
  color: rgba(58,123,213,0.06);
  line-height: 1;
  pointer-events: none;
}

.funfact-label {
  font-family: var(--mono);
  font-size: 10px; font-weight: 500;
  letter-spacing: 3px; text-transform: uppercase;
  color: var(--blue);
  margin-bottom: 22px;
  display: flex; align-items: center; gap: 12px;
}
.funfact-label::before, .funfact-label::after {
  content: ''; flex: 0 0 20px; height: 1px;
  background: var(--blue-line);
}

.funfact-text {
  font-family: var(--mono);
  font-size: clamp(14px, 1.8vw, 17px);
  line-height: 1.9;
  color: var(--txt-dim);
  min-height: 60px;
  transition: opacity 0.4s, transform 0.4s;
}
.funfact-text.fade { opacity: 0; transform: translateY(8px); }

.funfact-footer {
  display: flex; align-items: center;
  justify-content: space-between; margin-top: 30px;
}

.ff-dots { display: flex; gap: 6px; }
.ff-dot {
  width: 6px; height: 6px; border-radius: 99px;
  background: var(--txt-faint); cursor: pointer;
  transition: all 0.3s; border: none;
}
.ff-dot.active { width: 22px; background: var(--blue); }

.funfact-counter {
  font-family: var(--mono);
  font-size: 11px; color: var(--txt-faint);
}

/* ═══════════ A PROPOS ═══════════ */
#apropos {
  padding: 90px 5vw 80px;
}

.apropos-intro {
  margin-bottom: 60px;
}
.section-tag {
  font-family: var(--mono);
  font-size: 10px; font-weight: 500;
  letter-spacing: 3px; text-transform: uppercase;
  color: var(--blue); margin-bottom: 18px;
  display: flex; align-items: center; gap: 10px;
}
.section-tag::after {
  content: ''; flex: 0 0 32px; height: 1px;
  background: var(--blue); opacity: 0.5;
}

.apropos-intro h2 {
  font-family: var(--serif);
  font-size: clamp(28px, 3.5vw, 46px);
  line-height: 1.15;
  color: var(--txt);
  margin-bottom: 20px;
}
.apropos-intro p {
  font-family: var(--mono);
  font-size: 13px; line-height: 2;
  color: var(--txt-dim); max-width: 560px;
}

/* Feature grid — more editorial, less "card grid" */
.apropos-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1px;
  border: 1px solid var(--blue-line);
  border-radius: 18px;
  overflow: hidden;
  margin-bottom: 48px;
  background: var(--blue-line);
}

.apropos-bloc {
  background: var(--bg2);
  padding: 36px 30px;
  transition: background 0.25s;
  cursor: default;
}
.apropos-bloc:hover { background: var(--bg3); }

.apropos-bloc-num {
  font-family: var(--mono);
  font-size: 10px; font-weight: 500;
  letter-spacing: 2px; color: var(--blue);
  margin-bottom: 16px; opacity: 0.7;
}
.apropos-bloc h3 {
  font-family: var(--mono);
  font-size: 14px; font-weight: 600;
  color: var(--txt);
  margin-bottom: 12px;
}
.apropos-bloc p {
  font-family: var(--mono);
  font-size: 12px; line-height: 1.9;
  color: var(--txt-dim); margin: 0;
}

/* Stats band */
.apropos-chiffres {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  border: 1px solid var(--blue-line);
  border-radius: 16px; overflow: hidden;
  background: var(--blue-line);
  gap: 1px;
}
.chiffre-item {
  background: var(--bg2);
  padding: 38px 28px; text-align: center;
}
.chiffre-val {
  font-family: var(--mono);
  font-size: clamp(32px, 4vw, 48px);
  font-weight: 600; color: var(--blue);
  line-height: 1; margin-bottom: 8px;
  letter-spacing: -2px;
}
.chiffre-label {
  font-family: var(--mono);
  font-size: 11px; color: var(--txt-faint);
  text-transform: uppercase; letter-spacing: 1.5px;
}

/* ═══════════ POPUP ═══════════ */
#popup-container {
  position: fixed;
  bottom: 24px; right: 24px; z-index: 999;
  display: flex; flex-direction: column;
  gap: 10px; align-items: flex-end;
  pointer-events: none;
}

.popup-card {
  width: 280px;
  background: var(--bg2);
  border: 1px solid var(--blue-line);
  border-radius: 12px;
  padding: 14px 16px;
  display: flex; align-items: center; gap: 12px;
  box-shadow: 0 12px 32px rgba(0,0,0,0.55);
  animation: popIn 0.38s cubic-bezier(.34,1.56,.64,1) forwards;
  pointer-events: auto;
}

.popup-avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: var(--blue-glow);
  border: 1px solid var(--blue-line);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--mono);
  font-size: 12px; font-weight: 600;
  color: var(--blue); flex-shrink: 0;
}

.popup-body strong {
  display: block;
  font-family: var(--mono); font-size: 13px; font-weight: 500;
  color: var(--txt);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.popup-body span {
  display: block;
  font-family: var(--mono); font-size: 11px;
  color: var(--txt-dim); margin-top: 3px; line-height: 1.5;
}

@keyframes popIn {
  from { transform: translateY(14px) scale(0.96); opacity: 0; }
  to   { transform: translateY(0) scale(1); opacity: 1; }
}
@keyframes popOut {
  to { transform: translateX(16px); opacity: 0; }
}

/* ═══════════ STATS BAR ═══════════ */
.stats-bar {
  position: fixed; bottom: 24px; left: 24px; z-index: 998;
  background: var(--bg2);
  border: 1px solid var(--blue-line);
  color: var(--txt-dim);
  padding: 12px 18px; border-radius: 12px;
  box-shadow: 0 8px 28px rgba(0,0,0,0.45);
  font-family: var(--mono); font-size: 12px;
  line-height: 1.6;
}
.stats-bar .stat-count {
  font-size: 22px; font-weight: 600; color: var(--blue);
  display: block; line-height: 1.2; margin-bottom: 2px;
}

/* ═══════════ FOOTER ═══════════ */
footer {
  text-align: center;
  padding: 28px 40px;
  border-top: 1px solid var(--blue-line);
  font-family: var(--mono); font-size: 12px;
  color: var(--txt-faint);
}
footer .heart { color: #c0392b; }
footer strong { color: var(--txt-dim); }

/* ═══════════ RESPONSIVE ═══════════ */
@media (max-width: 900px) {
  .hero {
    grid-template-columns: 1fr;
    gap: 40px;
    padding: 90px 5vw 50px;
  }
  .cover { max-width: 520px; }
  .apropos-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
  nav { padding: 0 20px; }
  .nav-links { display: none; flex-direction: column; position: absolute; top: 64px; left: 0; right: 0; background: var(--bg2); border-bottom: 1px solid var(--blue-line); padding: 20px; gap: 18px; }
  .nav-links.open { display: flex; }
  .nav-burger { display: flex; }

  .hero { padding: 80px 20px 40px; gap: 32px; }
  .hero-desc { font-size: 12px; }
  .hero-btns { flex-direction: column; }
  .btn-primary, .btn-outline { text-align: center; }

  .funfact-card { padding: 32px 24px; }
  .funfact-text { font-size: 13px; }

  #apropos { padding: 60px 20px 50px; }
  .apropos-grid { grid-template-columns: 1fr; }
  .apropos-chiffres { grid-template-columns: 1fr; }
  .chiffre-item { border-right: none; border-bottom: 1px solid var(--blue-line); }
  .chiffre-item:last-child { border-bottom: none; }

  .stats-bar { display: none; }
  #popup-container { bottom: 16px; right: 16px; }
  .popup-card { width: 240px; }

  .apropos-bloc { padding: 24px 20px; }
}

@media (max-width: 400px) {
  .hero-title { font-size: 36px; }
  .funfact-card { padding: 24px 18px; }
}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <a class="nav-logo" href="#"><span>//</span> OpenQuiz</a>
  <div class="nav-links" id="navLinks">
    <a href="#" class="active">Accueil</a>
    <a href="#apropos">À propos</a>
  </div>
  <div class="nav-burger" id="navBurger" aria-label="Menu">
    <span></span><span></span><span></span>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-left">
    <div class="hero-eyebrow">Quiz &amp; Apprentissage</div>
    <h1 class="hero-title">
      Apprends<br>en jouant,<br><em>gagne en sachant.</em>
    </h1>
    <p class="hero-desc">
      Des milliers de questions sur tous les domaines —
      sciences, histoire, culture, géographie, technologie.
      Un contenu qui s'adapte à ton niveau et évolue
      avec toi à chaque session.
    </p>
    <div class="hero-btns">
      <a href="inscription.php" class="btn-primary">S'inscrire</a>
      <a href="login.php" class="btn-outline">Se connecter</a>
    </div>
  </div>

  <div class="cover">
    <div class="slide-wrapper" id="imgSlider">
      <img class="img-slide active" src="assests/4e01cf0477f86b331186b7ee45ca410c.jpg" alt="OpenQuiz">
      <video class="img-slide" src="assests/1.mp4" autoplay muted loop playsinline></video>
      <video class="img-slide" src="assests/2.mp4" autoplay muted loop playsinline></video>
      <div class="slide-overlay"></div>
      <div class="slide-info">
        <h4 id="slideInfoTitle">Découvre nos quiz</h4>
        <p id="slideInfoDesc"><a href="inscription.php">Commencer maintenant →</a></p>
      </div>
    </div>
    <div class="slide-dots" id="slideDots"></div>
  </div>
</section>

<!-- FUNFACT -->
<section class="funfact">
  <div class="funfact-card">
    <div class="funfact-label">Le saviez-vous ?</div>
    <div class="funfact-text" id="funfactText">
      Le cerveau retient mieux les informations en jouant.
    </div>
    <div class="funfact-footer">
      <div class="ff-dots" id="ffDots"></div>
      <div class="funfact-counter" id="funfactCounter">1 / 5</div>
    </div>
  </div>
</section>

<!-- A PROPOS -->
<section id="apropos">
  <div class="apropos-intro">
    <div class="section-tag">À propos</div>
    <h2>Une plateforme pensée<br>pour apprendre autrement.</h2>
    <p>
      OpenQuiz est née d'une idée simple : l'apprentissage devrait être aussi
      addictif que jouer. On combine la mécanique du jeu et la génération
      de contenu pour créer une expérience qui donne envie de revenir.
    </p>
  </div>

  <div class="apropos-grid">
    <div class="apropos-bloc">
      <div class="apropos-bloc-num">01</div>
      <h3>Questions générées</h3>
      <p>Chaque question est créée à la volée. Pas de contenu recyclé, pas de répétition — un flux infini sur tous les sujets imaginables.</p>
    </div>
    <div class="apropos-bloc">
      <div class="apropos-bloc-num">02</div>
      <h3>Adapté à ton niveau</h3>
      <p>OpenQuiz analyse tes résultats et ajuste la difficulté pour te maintenir dans ta zone de progression optimale.</p>
    </div>
    <div class="apropos-bloc">
      <div class="apropos-bloc-num">03</div>
      <h3>Suivi de progression</h3>
      <p>Visualise tes stats, identifie tes points faibles et mesure ton évolution session après session.</p>
    </div>
    <div class="apropos-bloc">
      <div class="apropos-bloc-num">04</div>
      <h3>Tous les thèmes</h3>
      <p>Science, histoire, culture générale, géographie, sport, technologie... Choisis un domaine ou laisse-toi surprendre.</p>
    </div>
    <div class="apropos-bloc">
      <div class="apropos-bloc-num">05</div>
      <h3>Challenge entre amis</h3>
      <p>Défie tes proches, compare vos scores et grimpe dans le classement. La compétition est le meilleur moteur.</p>
    </div>
    <div class="apropos-bloc">
      <div class="apropos-bloc-num">06</div>
      <h3>Gratuit, sans pub</h3>
      <p>OpenQuiz est entièrement gratuit. Aucune publicité, aucun abonnement caché. Juste du savoir, accessible à tous.</p>
    </div>
  </div>

  <div class="apropos-chiffres">
    <div class="chiffre-item">
      <div class="chiffre-val"><?php echo (int)$stats['total']; ?></div>
      <div class="chiffre-label">Parties aujourd'hui</div>
    </div>
    <div class="chiffre-item">
      <div class="chiffre-val">∞</div>
      <div class="chiffre-label">Thèmes disponibles</div>
    </div>
    <div class="chiffre-item">
      <div class="chiffre-val">100%</div>
      <div class="chiffre-label">Gratuit</div>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats-bar">
  <span class="stat-count"><?php echo (int)$stats['total']; ?></span>
  parties terminées aujourd'hui
</div>

<!-- POPUPS -->
<div id="popup-container"></div>

<!-- FOOTER -->
<footer>
  &copy; <?php echo date('Y'); ?> OpenQuiz — Made with <span class="heart">♥</span> by <strong>Imrankhan1608</strong>
</footer>

<script>
var activities = <?php echo json_encode(
    $activities,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
); ?>;

// NAV BURGER
document.getElementById('navBurger').addEventListener('click', function() {
  document.getElementById('navLinks').classList.toggle('open');
});

// POPUPS
var popupIndex = 0;
function initials(n) { return String(n).slice(0,2).toUpperCase(); }

function showPopup(act) {
  var pseudo = String(act.pseudo || 'Joueur');
  var theme  = String(act.theme  || 'a terminé un quiz');
  var score  = parseInt(act.score || 0);
  var nb     = parseInt(act.nb_questions || 1);
  var active = act.is_active == 1;
  var pct    = Math.round((score / nb) * 100);
  var desc   = active
    ? 'En train de jouer &mdash; ' + theme
    : theme + ' &mdash; ' + score + '/' + nb + ' (' + pct + '%)';

  var card = document.createElement('div');
  card.className = 'popup-card';
  card.innerHTML =
    '<div class="popup-avatar">' + initials(pseudo) + '</div>' +
    '<div class="popup-body"><strong>' + pseudo + '</strong><span>' + desc + '</span></div>';
  document.getElementById('popup-container').appendChild(card);

  setTimeout(function() {
    card.style.animation = 'popOut 0.55s ease forwards';
    setTimeout(function() { card.remove(); }, 350);
  }, 5000);
}

if (activities.length > 0) {
  setTimeout(function loop() {
    showPopup(activities[popupIndex]);
    popupIndex = (popupIndex + 1) % activities.length;
  }, 2200);
  setInterval(function() {
    showPopup(activities[popupIndex]);
    popupIndex = (popupIndex + 1) % activities.length;
  }, 6000);
}

// SLIDER
var slideInfo = [
  { title: 'Découvre nos quiz',   desc: '<a href="inscription.php">Commencer maintenant →</a>' },
  { title: 'Défie-toi',           desc: '<a href="inscription.php">Améliore tes scores à chaque partie</a>' },
  { title: 'Explore les thèmes',  desc: '<a href="inscription.php">Des centaines de domaines disponibles</a>' },
];

var slides   = document.querySelectorAll('.img-slide');
var dotsWrap = document.getElementById('slideDots');
var curSlide = 0;

slides.forEach(function(_, i) {
  var d = document.createElement('button');
  d.className = 'dot' + (i === 0 ? ' active' : '');
  d.setAttribute('aria-label', 'Slide ' + (i+1));
  d.addEventListener('click', function() { goToSlide(i); });
  dotsWrap.appendChild(d);
});

function goToSlide(i) {
  // pause previous video if needed
  var prev = slides[curSlide];
  if (prev && prev.tagName === 'VIDEO') prev.pause();

  slides.forEach(function(s, j) { s.classList.toggle('active', j === i); });
  document.querySelectorAll('#slideDots .dot').forEach(function(d, j) {
    d.classList.toggle('active', j === i);
  });

  var next = slides[i];
  if (next && next.tagName === 'VIDEO') { next.currentTime = 0; next.play(); }

  var info = slideInfo[i] || slideInfo[0];
  document.getElementById('slideInfoTitle').textContent = info.title;
  document.getElementById('slideInfoDesc').innerHTML    = info.desc;
  curSlide = i;
}

// Only show dots if multiple slides
if (slides.length > 1) {
  setInterval(function() { goToSlide((curSlide + 1) % slides.length); }, 4500);
}

// FUNFACT
var facts = [
  'Le cerveau retient mieux les informations en jouant.',
  'Apprendre en s\'amusant améliore la mémoire à long terme.',
  'Les quiz stimulent la concentration et l\'attention.',
  'Répondre à des questions est 3× plus efficace que la lecture passive.',
  'Se tromper renforce l\'ancrage mémoriel de la bonne réponse.',
];
var curFact = 0;
var ffWrap  = document.getElementById('ffDots');
var ffText  = document.getElementById('funfactText');

facts.forEach(function(_, i) {
  var d = document.createElement('button');
  d.className = 'ff-dot' + (i === 0 ? ' active' : '');
  d.setAttribute('aria-label', 'Fait ' + (i+1));
  d.addEventListener('click', function() { showFact(i); });
  ffWrap.appendChild(d);
});

function showFact(i) {
  ffText.classList.add('fade');
  setTimeout(function() {
    ffText.textContent = facts[i];
    ffText.classList.remove('fade');
    document.querySelectorAll('.ff-dot').forEach(function(d, j) {
      d.classList.toggle('active', j === i);
    });
    document.getElementById('funfactCounter').textContent = (i+1) + ' / ' + facts.length;
    curFact = i;
  }, 400);
}
setInterval(function() { showFact((curFact + 1) % facts.length); }, 4500);
</script>
</body>
</html>