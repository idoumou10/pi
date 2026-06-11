<?php
// ============================================================
// INCLUDES/HEADER.PHP — En-tête mutualisé (à inclure partout)
// Variables attendues (optionnelles) :
//   $page_titre   : titre du <title>
//   $page_active  : 'accueil'|'catalogue'|'promotions'|'favoris'|'contact'
//   $extra_css    : CSS spécifique à la page (string)
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
require_once __DIR__ . '/../categories.php';
require_once __DIR__ . '/../panier.php';
require_once __DIR__ . '/favoris_lib.php';

$nb_panier   = getNombreArticles();
$nb_favoris  = estConnecte() ? getNombreFavoris($conn, $_SESSION['id_utilisateur']) : 0;
$cats_header = getToutesCategories($conn);
$page_titre  = $page_titre  ?? 'ElectroComposants';
$page_active = $page_active ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="referrer" content="strict-origin-when-cross-origin">
<title><?= e($page_titre) ?> — ElectroComposants</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/style.css">
<?php if (!empty($extra_css)) { echo "<style>$extra_css</style>"; } ?>
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-microchip"></i></div>
            <div><strong>Electro</strong><span>Composants</span></div>
        </a>

        <form class="search-bar" method="GET" action="catalogue.php" role="search">
            <input type="text" name="q" placeholder="Rechercher un composant..."
                   value="<?= e($_GET['q'] ?? '') ?>" aria-label="Recherche">
            <button type="submit" aria-label="Lancer la recherche"><i class="fas fa-search"></i></button>
        </form>

        <div class="header-actions">
            <a href="<?= estConnecte() ? 'favoris.php' : 'login.php' ?>" class="header-btn fav-wrap" aria-label="Mes favoris">
                <i class="fas fa-heart"></i>
                <span class="action-badge" id="fav-badge" style="<?= $nb_favoris > 0 ? '' : 'display:none' ?>"><?= (int)$nb_favoris ?></span>
                <span>Favoris</span>
            </a>
            <a href="<?= estConnecte() ? 'profil.php' : 'login.php' ?>" class="header-btn" aria-label="Mon compte">
                <i class="fas fa-user"></i><span>Compte</span>
            </a>
            <a href="panier_page.php" class="header-btn cart-wrap" aria-label="Mon panier">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($nb_panier > 0): ?><span class="action-badge"><?= (int)$nb_panier ?></span><?php endif; ?>
                <span>Panier</span>
            </a>
            <!-- Burger mobile -->
            <button class="burger" id="burger" aria-label="Menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<nav class="site-nav" id="mainNav">
    <div class="nav-inner nav-links">
        <a href="index.php"      class="<?= $page_active==='accueil'    ? 'active':'' ?>">Accueil</a>
        <a href="catalogue.php"  class="<?= $page_active==='catalogue'  ? 'active':'' ?>">Catalogue</a>

        <!-- Menu déroulant Catégories (entre Catalogue et Promotions) -->
        <div class="dropdown">
            <button class="dropdown-toggle" type="button" aria-haspopup="true" aria-expanded="false">
                Catégories <i class="fas fa-chevron-down"></i>
            </button>
            <ul class="dropdown-menu" role="menu">
                <?php foreach ($cats_header as $c): ?>
                    <li>
                        <a href="catalogue.php?categorie=<?= (int)$c['id_categorie'] ?>">
                            <i class="fas fa-circle-dot"></i> <?= e($c['NOM']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <a href="promotions.php" class="<?= $page_active==='promotions' ? 'active':'' ?>">Promotions</a>
        <a href="contact.php"    class="<?= $page_active==='contact'    ? 'active':'' ?>">Contact</a>
        <?php if (estConnecte() && estAdmin()): ?>
            <a href="admin/index.php" class="admin-link"><i class="fas fa-shield-alt"></i> Admin</a>
        <?php endif; ?>
    </div>
</nav>