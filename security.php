<?php
// ============================================================
// INCLUDES/SECURITY.PHP — Fonctions de sécurité réutilisables
// ============================================================

/**
 * Génère (ou récupère) le token CSRF de la session.
 * À mettre dans tous les formulaires en POST sensibles.
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Affiche un champ caché <input type=hidden name=csrf_token ...>
 * À utiliser dans les <form>.
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Vérifie le token CSRF reçu en POST.
 * Si invalide : redirige avec un message d'erreur ou retourne false.
 */
function csrfVerify() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Échappe une valeur pour affichage HTML.
 * Alias plus court de htmlspecialchars.
 */
function e($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Nettoie une chaîne (trim + supprime caractères de contrôle).
 */
function clean($str) {
    $str = trim((string)$str);
    $str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
    return $str;
}

/**
 * Récupère un ID entier depuis une variable, valide qu'il est > 0.
 */
function getId($val) {
    $id = filter_var($val, FILTER_VALIDATE_INT);
    return ($id !== false && $id > 0) ? $id : 0;
}

/**
 * Valide qu'un email a un format correct.
 */
function validEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Force la déconnexion (vide tout).
 */
function logoutFull() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Force HTTPS si activé (à utiliser quand le site est en prod).
 * Décommentez l'appel dans config.php quand vous êtes en HTTPS.
 */
function forceHttps() {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}

/**
 * Empêche le clickjacking + sniffing MIME + XSS basique via headers.
 * Appeler en haut de chaque page (juste après config.php).
 */
function securityHeaders() {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/**
 * Limite le nombre de tentatives de login par IP (anti brute-force).
 * Retourne false si la limite est dépassée.
 */
function loginRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }

    // Reset après 15 minutes
    if (time() - $_SESSION[$key]['first'] > 900) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }

    return $_SESSION[$key]['count'] < 5;
}

function loginRateBump() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }
    $_SESSION[$key]['count']++;
}

function loginRateReset() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    unset($_SESSION[$key]);
}

// Applique les en-têtes par défaut
securityHeaders();
