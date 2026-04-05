# OpenQuiz (.md tsara2 monsieur )

Application de quiz interactif et progressif générée par IA.

---

## Stack technique

| Couche     | Techno                              |
|------------|-------------------------------------|
| Frontend   | HTML / CSS / JS vanilla             |
| Backend    | Python Flask                        |
| Auth PHP   | PHP 8+ + PDO (page de login)        |
| Base de données | MySQL / MariaDB               |
| IA         | Groq API (LLaMA 3.3 70B via OpenAI SDK) |

---

## Fonctionnalités

### Quiz
- **Thème libre en phrase** : écris "je veux des questions sur la révolution française" → les mots-clés sont extraits automatiquement
- **Réutilisation BD** : option pour réutiliser les questions déjà générées et stockées
- **Mode difficulté fixe** : Facile / Moyen / Difficile
- **Mode progression de niveaux** : 3 questions par niveau (facile → moyen → difficile), avec animation de passage de niveau
- **Timer visuel** par question, urgence colorée en rouge sous les 5 secondes
- **Revue des réponses** en fin de quiz

### Interface
- **Notifications
- **Messages d'erreur clairs** : thème vide, serveur HS, session expirée, JSON invalide…
- **Partage des résultats
- **Dashboard stats** : total quiz, meilleur score, moyenne, taux de réussite, historique des 10 dernières sessions avec mini graphiques
- **100% responsive** : mobile, tablette, desktop

### Sécurité
- **Token jamais dans l'URL** : passé une seule fois depuis PHP → Flask, puis stocké en cookie `HttpOnly; SameSite=Strict`
- Les logs serveur ne contiennent jamais le token de session
- Validation des entrées côté serveur (longueur, format, plages numériques)
- Nettoyage automatique des tokens expirés à chaque connexion
- Protection XSS via `escapeHtml()` côté JS et `htmlspecialchars()` côté PHP

---

## Installation

### 1. Cloner et installer

```bash
git clone https://github.com/youruser/openquiz.git
cd openquiz
pip install -r requirements.txt
```

### 2. Variables d'environnement

Crée un fichier `.env` à la racine pour mettre vos info BD et API:

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=votremotdepasse
DB_NAME=openquiz
SECRET_KEY=une_chaine_aleatoire_longue
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxx
```

Obtienez une clé Groq gratuitement sur : https://console.groq.com

### 3. Base de données

Crée la base et les tables :

```sql
CREATE DATABASE openquiz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE openquiz;

-- Utilisateurs
CREATE TABLE clients (
  id_cli        INT PRIMARY KEY AUTO_INCREMENT,
  pseudo        VARCHAR(50) UNIQUE NOT NULL,
  mdp           VARCHAR(255) NOT NULL,
  email         VARCHAR(100) UNIQUE,
  email_verified TINYINT(1) DEFAULT 0,
  status        ENUM('actif','inactif') DEFAULT 'actif',
  created_at    DATETIME DEFAULT NOW()
);

-- Tokens de session sécurisés
CREATE TABLE login_tokens (
  token   CHAR(64) PRIMARY KEY,
  id_cli  INT NOT NULL,
  expiry  DATETIME NOT NULL,
  FOREIGN KEY (id_cli) REFERENCES clients(id_cli) ON DELETE CASCADE,
  INDEX idx_expiry (expiry)
);

-- Questions (BD + générées par IA)
CREATE TABLE questions (
  id_question      INT PRIMARY KEY AUTO_INCREMENT,
  theme            VARCHAR(100) NOT NULL,
  difficulte       ENUM('facile','moyen','difficile') DEFAULT 'facile',
  question         TEXT NOT NULL,
  reponse_correcte TEXT NOT NULL,
  reponses_options JSON,
  source           ENUM('manuel','ia') DEFAULT 'ia',
  image            VARCHAR(255) DEFAULT NULL,
  temps_alloue     INT DEFAULT 15,
  created_at       DATETIME DEFAULT NOW(),
  INDEX idx_theme_diff (theme, difficulte)
);

-- Sessions de quiz
CREATE TABLE sessions (
  id_session   INT PRIMARY KEY AUTO_INCREMENT,
  id_cli       INT NOT NULL,
  score        INT DEFAULT 0,
  nb_questions INT DEFAULT 0,
  is_active    TINYINT(1) DEFAULT 0,
  date_session DATETIME DEFAULT NOW(),
  FOREIGN KEY (id_cli) REFERENCES clients(id_cli) ON DELETE CASCADE,
  INDEX idx_cli (id_cli)
);

-- Réponses individuelles
CREATE TABLE reponses (
  id_reponse  INT PRIMARY KEY AUTO_INCREMENT,
  id_question INT NOT NULL,
  id_cli      INT NOT NULL,
  id_session  INT,
  reponse     TEXT,
  est_correct TINYINT(1) DEFAULT 0,
  temps_pris  INT DEFAULT 0,
  created_at  DATETIME DEFAULT NOW(),
  FOREIGN KEY (id_cli)      REFERENCES clients(id_cli)     ON DELETE CASCADE,
  FOREIGN KEY (id_session)  REFERENCES sessions(id_session) ON DELETE SET NULL
);
```

### 4. Lancer le serveur

```bash
python app.py
```

Le serveur Flask tourne sur **http://localhost:5000**

Pour la partie PHP (login/inscription), un serveur Apache/Nginx avec PHP 8+ est requis, ou bien :

```bash
php -S localhost:8080
```

---

## Structure des fichiers (structure optimale, mais pas forcement la structure utilise)

```
openquiz/
├── app.py                  # Backend Flask principal (API + pages)
├── requirements.txt        # Dépendances Python
├── .env                    # Variables d'environnement (NON versionné)
├── .env.example            # Template des variables
├── connexion.php           # Page de connexion (PHP)
├── inscription.php         # Page d'inscription (PHP)
├── db.php                  # Connexion PDO partagée
├── templates/
│   ├── quiz.html           # Interface quiz complète
│   └── not_logged_in.html  # Page d'erreur session
└── README.md
```

---

## Flux d'authentification (sécurisé)

```
1. Utilisateur soumet le formulaire PHP (connexion.php)
2. PHP vérifie le pseudo/mot de passe en BD
3. PHP génère un token sécurisé (bin2hex(random_bytes(32)))
4. PHP redirige → http://localhost:5000/quiz?token=XXXX
5. Flask reçoit le token dans l'URL, le valide en BD
6. Flask répond avec une redirection → /quiz (sans token)
   + Set-Cookie: api_token=XXXX; HttpOnly; SameSite=Strict
7. Le navigateur accède à /quiz avec le cookie
   → Le token n'apparaît PLUS dans l'URL ni dans l'historique
```

---

## API Flask

| Méthode | Route           | Description                                 |
|---------|-----------------|---------------------------------------------|
| GET     | `/quiz`         | Page quiz (authentification requise)        |
| POST    | `/api/generate` | Générer un quiz                             |
| POST    | `/api/submit`   | Soumettre les résultats d'une session       |
| GET     | `/api/stats`    | Statistiques de l'utilisateur connecté      |
| GET     | `/logout`       | Déconnexion (supprime le cookie)            |

### POST /api/generate

```json (example)
{
  "theme": "la révolution industrielle",
  "difficulte": "moyen",
  "nb": 5,
  "temps": 20,
  "reuse_db": true
}
```

Réponse :
```json
{
  "questions": [...],
  "theme_keywords": ["révolution", "industrielle"]
}
```

---

## Extraction de mots-clés

L'IA reçoit **la phrase entière** pour comprendre le contexte.
La recherche en BD utilise les mots-clés extraits :

```
"je veux des questions sur la physique quantique"
→ mots-clés : ["physique", "quantique"]
→ SQL : WHERE theme LIKE '%physique%' OR theme LIKE '%quantique%'
→ Prompt IA : "thème : la physique quantique (mots-clés : physique quantique)"
```

---

## Mode progression de niveaux

En mode progression, le quiz se découpe en 3 phases :

| Phase | Difficulté | Questions |
|-------|-----------|-----------|
| 1/3   | Facile    | 3         |
| 2/3   | Moyen     | 3         |
| 3/3   | Difficile | 3         |

Une animation "Level Up" s'affiche entre chaque phase.
Les 3 niveaux sont générés simultanément au démarrage pour une expérience fluide.

---

## Production

- Passe `secure=True` dans `response.set_cookie()` (HTTPS requis)
- Utilise Gunicorn : `gunicorn -w 4 app:app`
- Configure un proxy Nginx devant Flask
- Active les logs d'erreur Flask (`logging.basicConfig`)
- Ajoute un `cron` pour nettoyer les tokens expirés : `DELETE FROM login_tokens WHERE expiry < NOW()`