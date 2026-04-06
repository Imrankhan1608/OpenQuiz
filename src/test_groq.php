<?php
// Charge le .env — ajuste le chemin selon ta structure
function load_env(string $path): void {
    if (!file_exists($path)) { echo "❌ .env introuvable : $path\n"; return; }
    echo "✅ .env trouvé : $path\n";
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

// Essaie les deux chemins
load_env(dirname(__DIR__) . '/.env');
$key = $_ENV['GROQ_API_KEY'] ?? '';
echo "Clé Groq : " . ($key ? "✅ trouvée (" . substr($key,0,8) . "...)" : "❌ VIDE") . "\n";
echo "curl_init : " . (function_exists('curl_init') ? "✅ OK" : "❌ MANQUANT") . "\n\n";

if (!$key) { die("Arrêt — clé manquante.\n"); }

// Test appel Groq
$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $key,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [['role'=>'user','content'=>'Dis juste: test ok']],
        'max_tokens' => 20,
    ]),
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err      = curl_error($ch);
curl_close($ch);

echo "HTTP : $httpCode\n";
echo "curl error : " . ($err ?: "aucune") . "\n";
echo "Réponse : $response\n";