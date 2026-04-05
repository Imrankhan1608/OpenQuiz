"""
OpenQuiz — Backend Flask principal
===================================
Gère l'authentification par token sécurisé (cookie HttpOnly),
la génération de questions via IA (Groq/LLaMA) ou base de données,
la soumission des résultats et les statistiques utilisateur.

Architecture:
  - Token stocké UNIQUEMENT en cookie HttpOnly (jamais dans l'URL)
  - Extraction intelligente des mots-clés pour la recherche en BD
  - Système de niveaux progressifs (facile → moyen → difficile)
  - API REST JSON pour toutes les interactions quiz
"""

import os, json, re, random, secrets
from flask import (
    Flask, request, jsonify, render_template,
    make_response, redirect, url_for
)
import mysql.connector
from dotenv import load_dotenv
import openai

# ─────────────────────────────────────────
# Chargement des variables d'environnement
# ─────────────────────────────────────────
load_dotenv()

DB_HOST        = os.getenv("DB_HOST", "localhost")
DB_USER        = os.getenv("DB_USER", "root")
DB_PASS        = os.getenv("DB_PASS", "")
DB_NAME        = os.getenv("DB_NAME", "openquiz")
SECRET_KEY     = os.getenv("SECRET_KEY", secrets.token_hex(32))
GROQ_API_KEY   = os.getenv("GROQ_API_KEY")

# ─────────────────────────────────────────
# Initialisation Flask
# ─────────────────────────────────────────
app = Flask(__name__)
app.secret_key = SECRET_KEY

# ─────────────────────────────────────────
# Connexion base de données (avec reconnect)
# ─────────────────────────────────────────
def get_db():
    """
    Retourne une connexion MySQL active.
    Reconnecte automatiquement si la connexion est perdue.
    """
    conn = mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME,
        autocommit=True
    )
    return conn

# ─────────────────────────────────────────
# Client IA (Groq avec interface OpenAI)
# ─────────────────────────────────────────
ai_client = openai.OpenAI(
    api_key=GROQ_API_KEY,
    base_url="https://api.groq.com/openai/v1"
)

# ─────────────────────────────────────────
# UTILITAIRE : Extraction de mots-clés (pour ne pas embrouiller l'ia)
# ─────────────────────────────────────────
STOP_WORDS = {
    "je", "veux", "donne", "moi", "un", "une", "des", "le", "la", "les",
    "de", "du", "sur", "stp", "svp", "quiz", "faire", "avec", "et", "en",
    "pour", "par", "au", "aux", "ce", "se", "sa", "son", "ses", "mon",
    "ma", "mes", "ton", "ta", "tes", "pas", "ne", "ou", "si", "que",
    "qui", "quoi", "dont", "où", "est", "are", "the", "a", "an", "of",
    "in", "on", "to", "about", "what", "how", "why", "when", "is",
    "parle", "moi", "questions", "génère", "genere", "donne", "fais",
    "veux", "voudrais", "aimerais", "souhaite", "demande"
}

def extract_keywords(user_input: str) -> list[str]:
    """
    Extrait les mots-clés significatifs d'une phrase utilisateur.
    Supprime la ponctuation, les stopwords et les mots < 3 caractères.
    
    Exemples:
      "je veux des questions sur la physique quantique" → ["physique", "quantique"]
      "histoire de france révolution" → ["histoire", "france", "révolution"]
    """
    text = user_input.lower()
    # Supprimer la ponctuation (sauf les accents)
    text = re.sub(r"[^a-zA-Zà-ÿÀ-Ÿ\s]", " ", text)
    words = text.split()
    keywords = [w for w in words if w not in STOP_WORDS and len(w) > 2]
    return keywords if keywords else [text.strip()]


# ─────────────────────────────────────────
# SÉCURITÉ : Vérification du token
# ─────────────────────────────────────────
def verify_token() -> int | None:
    """
    Vérifie le token d'authentification UNIQUEMENT depuis le cookie HttpOnly.
    Le token n'est JAMAIS lu depuis l'URL pour éviter les fuites dans les logs.
    
    Retourne l'id_cli si le token est valide et non expiré, sinon None.
    """
    token = request.cookies.get("api_token")
    if not token or len(token) != 64:  # Token hex 32 bytes = 64 chars
        return None

    db = get_db()
    cursor = db.cursor(dictionary=True)
    try:
        cursor.execute(
            "SELECT id_cli FROM login_tokens WHERE token=%s AND expiry > NOW()",
            (token,)
        )
        result = cursor.fetchone()
        return result["id_cli"] if result else None
    finally:
        cursor.close()
        db.close()


# ─────────────────────────────────────────
# ROUTES : Pages HTML
# ─────────────────────────────────────────

@app.route("/quiz")
def quiz_page():
    """
    Page principale du quiz.
    - Accepte le token depuis l'URL UNE SEULE FOIS pour le setter en cookie,
      puis redirige immédiatement vers /quiz sans token dans l'URL.
    - Si déjà en cookie, affiche directement la page.
    """
    # Cas : token passé en URL depuis la page de login (une seule fois)
    url_token = request.args.get("token")
    if url_token:
        # Valider le token avant de le setter
        db = get_db()
        cursor = db.cursor(dictionary=True)
        try:
            cursor.execute(
                "SELECT id_cli FROM login_tokens WHERE token=%s AND expiry > NOW()",
                (url_token,)
            )
            result = cursor.fetchone()
        finally:
            cursor.close()
            db.close()

        if not result:
            return redirect("/connexion?error=token_invalide")

        # Rediriger vers /quiz sans token dans l'URL, cookie sécurisé
        response = make_response(redirect("/quiz"))
        response.set_cookie(
            "api_token",
            url_token,
            httponly=True,       # Inaccessible au JavaScript → protège contre XSS
            secure=False,        # Mettre True en production (HTTPS)
            samesite="Strict",   # Protège contre CSRF
            max_age=7200         # 2 heures
        )
        return response

    # Cas : token déjà en cookie
    id_cli = verify_token()
    if not id_cli:
        return redirect("/connexion?error=session_expiree")

    return render_template("quiz.html")


@app.route("/logout")
def logout():
    """Supprime le cookie de session et redirige vers la connexion."""
    response = make_response(redirect("/connexion"))
    response.delete_cookie("api_token")
    return response


# ─────────────────────────────────────────
# API : Générer un quiz
# ─────────────────────────────────────────

@app.route("/api/generate", methods=["POST"])
def generate_quiz():
    """
    Génère un quiz selon le thème, la difficulté et le nombre de questions.
    
    Stratégie :
      1. Extraire les mots-clés du thème utilisateur
      2. Chercher en BD les questions correspondantes
      3. Si pas assez → compléter avec l'IA (LLaMA via Groq)
      4. Sauvegarder les nouvelles questions IA en BD pour réutilisation future
    
    Body JSON attendu:
      {
        "theme": str,           # thème libre (phrase ou mot)
        "difficulte": str,      # "facile" | "moyen" | "difficile"
        "nb": int,              # nombre de questions (1-20)
        "temps": int,           # secondes par question (5-60)
        "reuse_db": bool        # utiliser les questions existantes en BD ?
      }
    """
    id_cli = verify_token()
    if not id_cli:
        return jsonify({"error": "Session expirée. Veuillez vous reconnecter."}), 401

    data = request.get_json(silent=True)
    if not data:
        return jsonify({"error": "Format de requête invalide (JSON attendu)."}), 400

    raw_theme  = str(data.get("theme", "")).strip()
    difficulte = data.get("difficulte", "facile")
    nb         = max(1, min(20, int(data.get("nb", 5))))
    temps      = max(5, min(60, int(data.get("temps", 15))))
    reuse_db   = bool(data.get("reuse_db", True))

    if not raw_theme:
        return jsonify({"error": "Le thème est obligatoire."}), 400
    if difficulte not in ("facile", "moyen", "difficile"):
        return jsonify({"error": "Difficulté invalide. Choisissez : facile, moyen ou difficile."}), 400

    keywords = extract_keywords(raw_theme)
    db = get_db()
    cursor = db.cursor(dictionary=True)
    questions = []

    try:
        # ── Étape 1 : Recherche en BD (si l'utilisateur accepte) ──
        if reuse_db and keywords:
            conditions = " OR ".join(["theme LIKE %s"] * len(keywords))
            values = [f"%{k}%" for k in keywords]
            sql = f"""
                SELECT * FROM questions
                WHERE ({conditions}) AND difficulte = %s
                ORDER BY RAND()
                LIMIT %s
            """
            cursor.execute(sql, (*values, difficulte, nb))
            questions = cursor.fetchall()

        # ── Étape 2 : Compléter avec l'IA si nécessaire ──
        needed = nb - len(questions)
        if needed > 0:
            ai_questions = _generate_with_ai(
                theme=raw_theme,
                keywords=keywords,
                difficulte=difficulte,
                count=needed,
                cursor=cursor,
                db=db
            )
            questions.extend(ai_questions)

        # ── Étape 3 : Formater pour le frontend ──
        random.shuffle(questions)
        formatted = []
        for q in questions[:nb]:
            # Décoder les options si stockées en JSON string
            options = q.get("options", [])
            if isinstance(q.get("reponses_options"), str):
                try:
                    options = json.loads(q["reponses_options"])
                except json.JSONDecodeError:
                    options = []

            formatted.append({
                "id_question":      q.get("id_question", 0),
                "question":         q.get("question", ""),
                "options":          options,
                "reponse_correcte": q.get("reponse_correcte", ""),
                "image":            q.get("image", None),
                "temps":            temps,
                "theme":            raw_theme
            })

        return jsonify({"questions": formatted, "theme_keywords": keywords})

    except Exception as e:
        app.logger.error(f"Erreur generate_quiz: {e}")
        return jsonify({"error": "Erreur interne lors de la génération du quiz. Veuillez réessayer."}), 500
    finally:
        cursor.close()
        db.close()

# pour eviter que l'ia part dans tt les sens, 
# j'ai preciser des regles dans le prompt

def _generate_with_ai(theme, keywords, difficulte, count, cursor, db) -> list:
    """
    Génère `count` questions via l'IA (LLaMA 3.3 via Groq).
    Sauvegarde automatiquement les nouvelles questions en BD.
    
    Le prompt est conçu pour :
      - Comprendre les phrases entières (pas seulement les mots isolés)
      - Retourner du JSON strict sans markdown
      - Générer des questions variées et pédagogiques
    """
    theme_display = " ".join(keywords) if keywords else theme

    prompt = f"""Tu es un expert en création de quiz éducatifs soit original.
Génère exactement {count} questions de quiz sur le thème : "{theme}" (mots-clés : {theme_display}).
Difficulté : {difficulte}, pour la difficulte applique vraiment quand c'est difficile trouve des question extrème.

Règles STRICTES :
- Réponds UNIQUEMENT avec un tableau JSON valide, aucun texte avant ou après
- Chaque question a exactement 4 options de réponse
- La réponse correcte doit être l'une des 4 options (texte identique)
- Les questions doivent être variées et intéressantes
- Niveau {difficulte} : {"questions simples et directes" if difficulte == "facile" else "questions nécessitant des connaissances solides" if difficulte == "moyen" else "questions expertes et nuancées"}

Format JSON attendu (tableau, pas d'objet racine) :
[
  {{
    "question": "Texte de la question ?",
    "options": ["Option A", "Option B", "Option C", "Option D"],
    "reponse_correcte": "Option A"
  }}
]"""

    try:
        response = ai_client.chat.completions.create(
            model="llama-3.3-70b-versatile",
            messages=[
                {"role": "system", "content": "Tu génères uniquement du JSON valide. Aucun texte, aucun markdown, aucune explication."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.7,
            max_tokens=2000
        )

        raw = response.choices[0].message.content.strip()
        # Nettoyer les balises markdown si présentes
        raw = re.sub(r"```json\s*|```\s*", "", raw).strip()
        # Extraire le tableau JSON
        match = re.search(r'\[.*\]', raw, re.DOTALL)
        if not match:
            app.logger.warning(f"IA n'a pas retourné de tableau JSON: {raw[:200]}")
            return []

        ai_questions = json.loads(match.group())
        saved = []

        for q in ai_questions:
            if not all(k in q for k in ("question", "options", "reponse_correcte")):
                continue
            # Sauvegarder en BD pour réutilisation
            cursor.execute("""
                INSERT INTO questions
                (theme, difficulte, question, reponse_correcte, reponses_options, source, temps_alloue)
                VALUES (%s, %s, %s, %s, %s, 'ia', %s)
            """, (
                theme,
                difficulte,
                q["question"],
                q["reponse_correcte"],
                json.dumps(q["options"], ensure_ascii=False),
                15
            ))
            q["id_question"] = cursor.lastrowid
            saved.append(q)

        return saved

    except json.JSONDecodeError as e:
        app.logger.error(f"Erreur parsing JSON IA: {e}")
        return []
    except Exception as e:
        app.logger.error(f"Erreur appel IA: {e}")
        return []


# ─────────────────────────────────────────
# API : Soumettre les résultats
# ─────────────────────────────────────────

@app.route("/api/submit", methods=["POST"])
def submit_quiz():
    """
    Enregistre les résultats d'une session de quiz.
    Sauvegarde la session globale + chaque réponse individuelle.
    
    Body JSON attendu:
      {
        "score": int,
        "total": int,
        "answers": [
          {
            "id_question": int,
            "reponse": str | null,
            "est_correct": bool,
            "temps_pris": int
          }
        ]
      }
    """
    id_cli = verify_token()
    if not id_cli:
        return jsonify({"error": "Session expirée."}), 401

    data = request.get_json(silent=True)
    if not data:
        return jsonify({"error": "Données invalides."}), 400

    score   = int(data.get("score", 0))
    total   = int(data.get("total", 0))
    answers = data.get("answers", [])

    if total == 0:
        return jsonify({"error": "Total de questions invalide."}), 400

    db = get_db()
    cursor = db.cursor(dictionary=True)
    try:
        # Enregistrer la session
        cursor.execute("""
            INSERT INTO sessions (id_cli, score, nb_questions, is_active, date_session)
            VALUES (%s, %s, %s, 0, NOW())
        """, (id_cli, score, total))
        id_session = cursor.lastrowid

        # Enregistrer chaque réponse
        for a in answers:
            cursor.execute("""
                INSERT INTO reponses (id_question, id_cli, id_session, reponse, est_correct, temps_pris)
                VALUES (%s, %s, %s, %s, %s, %s)
            """, (
                a.get("id_question", 0),
                id_cli,
                id_session,
                a.get("reponse"),
                int(bool(a.get("est_correct", False))),
                int(a.get("temps_pris", 0))
            ))

        return jsonify({"status": "ok", "id_session": id_session})

    except Exception as e:
        app.logger.error(f"Erreur submit_quiz: {e}")
        return jsonify({"error": "Erreur lors de la sauvegarde des résultats."}), 500
    finally:
        cursor.close()
        db.close()


# ─────────────────────────────────────────
# API : Statistiques utilisateur
# ─────────────────────────────────────────

@app.route("/api/stats", methods=["GET"])
def get_stats():
    """
    Retourne les statistiques complètes de l'utilisateur connecté.
    
    Réponse JSON:
      {
        "pseudo": str,
        "total_quizz": int,
        "meilleur_score_pct": float,
        "moyenne_score_pct": float,
        "questions_repondues": int,
        "taux_reussite": float,
        "historique": [ { "date", "score", "total", "pct" } ]
      }
    """
    id_cli = verify_token()
    if not id_cli:
        return jsonify({"error": "Session expirée."}), 401

    db = get_db()
    cursor = db.cursor(dictionary=True)
    try:
        # Infos utilisateur
        cursor.execute("SELECT pseudo FROM clients WHERE id_cli = %s", (id_cli,))
        user = cursor.fetchone()
        if not user:
            return jsonify({"error": "Utilisateur introuvable."}), 404

        # Stats globales (pour les stat)
        cursor.execute("""
            SELECT
                COUNT(*) AS total_quizz,
                MAX(score / nb_questions * 100) AS meilleur_score_pct,
                AVG(score / nb_questions * 100) AS moyenne_score_pct,
                SUM(nb_questions) AS questions_repondues
            FROM sessions
            WHERE id_cli = %s AND nb_questions > 0
        """, (id_cli,))
        stats = cursor.fetchone()

        # Taux de réussite sur les réponses
        cursor.execute("""
            SELECT
                COUNT(*) AS total,
                SUM(est_correct) AS correct
            FROM reponses
            WHERE id_cli = %s
        """, (id_cli,))
        reponses = cursor.fetchone()

        taux = 0
        if reponses["total"] and reponses["total"] > 0:
            taux = round(reponses["correct"] / reponses["total"] * 100, 1)

        # Historique des 10 dernières sessions
        cursor.execute("""
            SELECT
                DATE_FORMAT(date_session, '%d/%m/%Y') AS date,
                score,
                nb_questions AS total,
                ROUND(score / nb_questions * 100) AS pct
            FROM sessions
            WHERE id_cli = %s AND nb_questions > 0
            ORDER BY date_session DESC
            LIMIT 10
        """, (id_cli,))
        historique = cursor.fetchall()

        return jsonify({
            "pseudo":              user["pseudo"],
            "total_quizz":         stats["total_quizz"] or 0,
            "meilleur_score_pct":  round(stats["meilleur_score_pct"] or 0, 1),
            "moyenne_score_pct":   round(stats["moyenne_score_pct"] or 0, 1),
            "questions_repondues": stats["questions_repondues"] or 0,
            "taux_reussite":       taux,
            "historique":          historique
        })

    except Exception as e:
        app.logger.error(f"Erreur get_stats: {e}")
        return jsonify({"error": "Erreur lors du chargement des statistiques."}), 500
    finally:
        cursor.close()
        db.close()


# ─────────────────────────────────────────
# Lancement
# ─────────────────────────────────────────
if __name__ == "__main__":
    app.run(debug=True, port=5000)