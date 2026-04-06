<?php
/**
 * joe_chat.php — Endpoint sécurisé pour le chatbot Joe
 * Utilise Groq API (llama3-8b-8192)
 */

// ── Sécurité HTTP ────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Uniquement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Méthode non autorisée']));
}

// ── Chargement .env ──────────────────────────────────────────────────────────
function load_env(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}
load_env(dirname(__DIR__) . '/.env');

$GROQ_KEY = $_ENV['GROQ_API_KEY'] ?? '';
$DB_HOST  = $_ENV['DB_HOST']      ?? 'localhost';
$DB_USER  = $_ENV['DB_USER']      ?? 'root';
$DB_PASS  = $_ENV['DB_PASS']      ?? '';
$DB_NAME  = $_ENV['DB_NAME']      ?? 'quiz_game';

if (empty($GROQ_KEY)) {
    http_response_code(500);
    exit(json_encode(['error' => 'Configuration manquante']));
}

// ── PDO ──────────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    // Crée la table de logs si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_logs (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        session_id  VARCHAR(64)  NOT NULL,
        role        ENUM('user','assistant') NOT NULL,
        message     TEXT         NOT NULL,
        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    error_log('DB Error (joe_chat): ' . $e->getMessage());
    // On continue sans DB — le chat fonctionne même sans logging
    $pdo = null;
}

// ── Lecture JSON body ────────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || !isset($body['message']) || !is_string($body['message'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Corps de requête invalide']));
}

// Sanitize
$userMessage = trim(strip_tags($body['message']));
if (strlen($userMessage) === 0 || strlen($userMessage) > 1000) {
    http_response_code(400);
    exit(json_encode(['error' => 'Message invalide (vide ou trop long)']));
}

// Historique de conversation (tableau [{role, content}])
$history = [];
if (isset($body['history']) && is_array($body['history'])) {
    foreach ($body['history'] as $turn) {
        if (isset($turn['role'], $turn['content'])
            && in_array($turn['role'], ['user', 'assistant'], true)
            && is_string($turn['content'])) {
            $history[] = [
                'role'    => $turn['role'],
                'content' => mb_substr(strip_tags($turn['content']), 0, 500),
            ];
        }
    }
    // Max 10 tours pour pas exploser le contexte
    $history = array_slice($history, -10);
}

// Session ID anonyme pour le logging
$sessionId = isset($body['session_id']) && preg_match('/^[a-zA-Z0-9_-]{8,64}$/', $body['session_id'])
    ? $body['session_id']
    : bin2hex(random_bytes(16));

// ── System prompt de Joe ─────────────────────────────────────────────────────
$systemPrompt = <<<PROMPT
Tu es Joe, l'assistant officiel d'OpenQuiz — une plateforme de quiz en ligne.
Tu dois reconnaitre la langue de l'utilisateur et parler sa langue avec lui .
Ton caractère : charismatique, sarcastique à souhait, drôle, légèrement arrogant mais attachant.
Tu reponds selon la langue de l'utilisateur, avec familiarité (tu/toi). Tu utilises des expressions populaires de cette langue.
Tu es FIER d'OpenQuiz comme si c'était ton bébé.

Tes rôles :
1. Aider les utilisateurs à naviguer sur le site (inscription, connexion, jouer, voir les scores).
2. Expliquer les fonctionnalités (quiz adaptatifs, thèmes variés).
3. Motiver les gens à jouer avec humour et légèreté.
4. Répondre aux questions courantes sur le site.

Informations sur OpenQuiz :
- Plateforme de quiz gratuite, sans pub, sans abonnement.
- Des milliers de questions générées dynamiquement.
- Thèmes : science, histoire, culture générale, géographie... tu ce que tu veux comme theme il y'a qu'a lecrire .
- Difficulté adaptative selon le niveau du joueur.
- Suivi de progression et classements.
- Inscription (appuie sur le grand boutton bleu s'inscire a gauche sur la page d'acceuil), il y'a un gros boutton se connecter a gauche (ton ecriture doit etre correcte).

Règles importantes :
- Sois sarcastique et drôle MAIS reste utile — une vanne puis la vraie réponse.
- Si quelqu'un dit que le site est nul, défends-le avec passion (et une pointe de sarcasme).
- Maximum 3 phrases par réponse. Sois concis et percutant.
- N'invente pas de fonctionnalités qui n'existent pas.
- Ne révèle jamais que tu es une IA ou que tu utilises Groq/LLaMA. Tu es Joe, c'est tout.
PROMPT;

// ── Construction des messages pour Groq ─────────────────────────────────────
$messages = array_merge(
    [['role' => 'system', 'content' => $systemPrompt]],
    $history,
    [['role' => 'user', 'content' => $userMessage]]
);
// ── Appel Groq API ───────────────────────────────────────────────────────────

$payload = json_encode([
    'model'       => 'llama-3.3-70b-versatile',
    'messages'    => $messages,
    'max_tokens'  => 200,
    'temperature' => 0.85,

]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $GROQ_KEY,
    ],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200) {
    error_log("Groq API error: HTTP {$httpCode} — {$curlErr} — {$response}");
    http_response_code(502);
    exit(json_encode(['error' => 'Joe est en pause café. Réessaie dans un instant.']));
}

$groqData = json_decode($response, true);
$reply    = $groqData['choices'][0]['message']['content'] ?? null;

if (!$reply) {
    http_response_code(502);
    exit(json_encode(['error' => 'Joe a perdu la parole. Temporairement.']));
}

$reply = trim($reply);

// ── Logging en base (optionnel, échec silencieux) ────────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO chat_logs (session_id, role, message) VALUES (?, ?, ?)"
        );
        $stmt->execute([$sessionId, 'user',      $userMessage]);
        $stmt->execute([$sessionId, 'assistant', $reply]);
    } catch (PDOException $e) {
        error_log('Chat log error: ' . $e->getMessage());
    }
}

// ── Réponse ──────────────────────────────────────────────────────────────────
echo json_encode([
    'reply'      => $reply,
    'session_id' => $sessionId,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);