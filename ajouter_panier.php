<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/panier.php';

$id  = getId($_GET['id'] ?? 0);
$qte = max(1, (int)($_GET['qte'] ?? 1));
$csrf = $_GET['csrf'] ?? '';

// Vérif CSRF même en GET pour empêcher les ajouts forcés
$csrf_ok = hash_equals($_SESSION['csrf_token'] ?? '', $csrf);

if ($id > 0 && $csrf_ok) {
    $resultat = ajouterAuPanier($conn, $id, $qte);
    $_SESSION['flash'] = $resultat;
}

// Redirection sûre : on n'accepte QUE des URLs internes
$retour = $_SERVER['HTTP_REFERER'] ?? '';
$host   = $_SERVER['HTTP_HOST'] ?? '';

if (!$retour ||
    (parse_url($retour, PHP_URL_HOST) && parse_url($retour, PHP_URL_HOST) !== $host)) {
    $retour = 'catalogue.php';
}

header('Location: ' . $retour);
exit;
