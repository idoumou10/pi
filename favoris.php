<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';
require_once __DIR__ . '/includes/favoris_lib.php';

exigerConnexion();

$message = '';
$action  = $_GET['action'] ?? '';
$id_prod = getId($_GET['id'] ?? 0);
$csrf    = $_GET['csrf'] ?? '';

// ── Requête AJAX : répondre en JSON sans charger la page ──────────────────
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    if (!$id_prod || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        echo json_encode(['ok' => false, 'error' => 'csrf']);
        exit;
    }
    if ($action === 'toggle') {
        $r     = toggleFavori($conn, $_SESSION['id_utilisateur'], $id_prod);
        $total = getNombreFavoris($conn, $_SESSION['id_utilisateur']);
        echo json_encode(['ok' => true, 'etat' => $r, 'total' => (int)$total]);
    } elseif ($action === 'retirer') {
        retirerFavori($conn, $_SESSION['id_utilisateur'], $id_prod);
        $total = getNombreFavoris($conn, $_SESSION['id_utilisateur']);
        echo json_encode(['ok' => true, 'etat' => 'retire', 'total' => (int)$total]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'action_inconnue']);
    }
    exit;
}

// ── Accès direct (page normale) ───────────────────────────────────────────

if ($action && $id_prod && hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    if ($action === 'toggle') {
        $r = toggleFavori($conn, $_SESSION['id_utilisateur'], $id_prod);
        $message = $r === 'ajoute' ? '❤️ Ajouté à vos favoris.' : 'Retiré de vos favoris.';
    } elseif ($action === 'retirer') {
        retirerFavori($conn, $_SESSION['id_utilisateur'], $id_prod);
        $message = 'Retiré de vos favoris.';
    }
}

$favoris       = getFavorisUtilisateur($conn, $_SESSION['id_utilisateur']);
$seuil_nouveau = getSeuilNouveau($conn);

$page_titre  = 'Mes Favoris';
$page_active = 'favoris';
include __DIR__ . '/includes/header.php';
?>

<main>
<div class="section">
    <h2 class="section-title"><i class="fas fa-heart" style="color:var(--rouge)"></i> Mes Favoris</h2>

    <?php if ($message): ?>
        <div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div>
    <?php endif; ?>

    <?php if (empty($favoris)): ?>
        <div class="empty">
            <i class="far fa-heart"></i>
            <p>Vous n'avez aucun favori pour le moment.</p>
            <p style="font-size:14px;color:var(--gris)">Cliquez sur le ❤️ d'un produit pour l'ajouter ici.</p>
            <a href="catalogue.php" class="btn-primary" style="margin-top:14px;display:inline-flex">
                <i class="fas fa-th"></i> Parcourir le catalogue
            </a>
        </div>
    <?php else: ?>
    <div class="produits-grid">
        <?php foreach ($favoris as $p):
            $is_fav = true;
            include __DIR__ . '/includes/product_card.php';
        endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
