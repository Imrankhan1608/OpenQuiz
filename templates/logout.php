<?php
session_start();

// Si l'utilisateur confirme la déconnexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {

        // Si "se souvenir de moi" n'est PAS coché → on détruit tout
        if (!isset($_POST['remember'])) {
            $_SESSION = [];
            session_destroy();

            // Suppression cookie si existant
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
        }

        // Redirection après logout
        header("Location: acceuil.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Déconnexion — OpenQuiz</title>

<style>
body {
  margin:0;
  font-family: 'DM Sans', sans-serif;
  background:#080e14;
  color:#e8edf2;
  display:flex;
  align-items:center;
  justify-content:center;
  height:100vh;
}

.card {
  background:#0d1620;
  border:1px solid rgba(58,123,213,0.2);
  border-radius:20px;
  padding:40px;
  max-width:420px;
  width:100%;
  text-align:center;
}

h1 {
  font-size:28px;
  margin-bottom:10px;
}

p {
  font-size:14px;
  color:rgba(232,237,242,0.6);
  margin-bottom:25px;
}

.toggle {
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  margin-bottom:25px;
  font-size:13px;
  color:rgba(232,237,242,0.7);
}

.toggle input {
  width:16px;
  height:16px;
}

.actions {
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

button, a {
  flex:1;
  padding:12px;
  border-radius:12px;
  border:none;
  font-size:12px;
  text-transform:uppercase;
  cursor:pointer;
  text-decoration:none;
}

.btn-logout {
  background:#3a7bd5;
  color:white;
}

.btn-logout:hover {
  background:#2a5fa8;
}

.btn-home {
  background:transparent;
  border:1px solid rgba(58,123,213,0.2);
  color:#e8edf2;
}

.btn-home:hover {
  border-color:#3a7bd5;
}
</style>
</head>

<body>

<div class="card">
  <h1>Se déconnecter</h1>
  <p>Tu es sur le point de quitter ta session. Ton quiz ne te manquera pas. Probablement.</p>

  <form method="POST">
    
    <div class="toggle">
      <input type="checkbox" name="remember" id="remember">
      <label for="remember">Rester connecté sur cet appareil</label>
    </div>

    <div class="actions">
      <button type="submit" name="logout" class="btn-logout">
        Déconnexion
      </button>

      <a href="acceuil.php" class="btn-home">
        Annuler
      </a>
    </div>

  </form>
</div>

</body>
</html>