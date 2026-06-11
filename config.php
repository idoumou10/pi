<?php
// ============================================================
// CONFIG.PHP — Connexion BDD + Configuration de session sécurisée
// ============================================================

// --- Session sécurisée AVANT session_start() ---
// HttpOnly : empêche JS de lire le cookie de session (anti-XSS)
// SameSite=Lax : empêche les attaques CSRF cross-site
// Secure=1 : à activer en HTTPS uniquement (mettre 1 quand prod en https)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
// ini_set('session.cookie_secure', '1');  // ⚠️ DÉCOMMENTER EN PRODUCTION (HTTPS)

session_start();

// Régénérer l'ID de session de temps en temps (anti-fixation)
if (!isset($_SESSION['last_regen'])) {
    $_SESSION['last_regen'] = time();
} elseif (time() - $_SESSION['last_regen'] > 600) { // toutes les 10 min
    session_regenerate_id(true);
    $_SESSION['last_regen'] = time();
}

// --- Connexion à la base ---
$DB_HOST = 'sql213.infinityfree.com';
$DB_USER = 'if0_41981530';
$DB_PASS = 'idoumou10';
$DB_NAME = 'if0_41981530_pi_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    // En production : logguer dans un fichier, ne PAS afficher
    error_log("DB connection failed: " . $conn->connect_error);
    die("Service indisponible. Réessayez plus tard.");
}
$conn->set_charset('utf8mb4');

// Charger les helpers de sécurité
require_once __DIR__ . '/includes/security.php';
