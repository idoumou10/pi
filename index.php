<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';
require_once __DIR__ . '/produits.php';
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/commandes.php';
require_once __DIR__ . '/includes/favoris_lib.php';

$produits_une   = getTousProduits($conn);
$vedette        = array_slice($produits_une, 0, 8);
$favoris_ids    = estConnecte() ? getIdsFavoris($conn, $_SESSION['id_utilisateur']) : [];
$seuil_nouveau  = getSeuilNouveau($conn);

$categories_sidebar = getToutesCategories($conn);
$categories_pop     = getCategoriesPopulaires($conn, 8);

$nb_produits   = (int)$conn->query("SELECT COUNT(*) AS n FROM produit WHERE statut = 'disponible'")->fetch_assoc()['n'];
$nb_categories = (int)$conn->query("SELECT COUNT(*) AS n FROM categories WHERE statut = 'actif'")->fetch_assoc()['n'];
$nb_marques    = (int)$conn->query("SELECT COUNT(*) AS n FROM marques")->fetch_assoc()['n'];
$nb_clients    = (int)$conn->query("SELECT COUNT(*) AS n FROM utilisateurs WHERE role = 'client'")->fetch_assoc()['n'];

// Offre du jour
$offre_produit_id = (int)getParametre($conn, 'offre_du_jour_produit', '0');
$offre_fin        = getParametre($conn, 'offre_du_jour_fin', '');
$offre_produit    = null;
if ($offre_produit_id > 0 && $offre_fin !== '' && strtotime($offre_fin) > time()) {
    $offre_produit = getProduitParId($conn, $offre_produit_id);
    if ($offre_produit) {
        $offre_produit['en_promo'] = true;
    }
}

$page_titre  = 'Accueil';
$page_active = 'accueil';
include __DIR__ . '/includes/header.php';
?>

<main>
<!-- HERO -->
<section class="hero">
    <h1>Vos composants<br><span>électroniques</span> en ligne</h1>
    <p>Arduino, Raspberry Pi, capteurs, résistances, condensateurs... Tout pour vos projets électroniques livrés en Mauritanie.</p>
    <div class="hero-btns">
        <a href="catalogue.php" class="btn-primary"><i class="fas fa-th"></i> Voir le catalogue</a>
        <?php if (!estConnecte()): ?>
            <a href="login.php" class="btn-secondary"><i class="fas fa-sign-in-alt"></i> Se connecter</a>
        <?php endif; ?>
    </div>
</section>

<!-- BANDEAU STATISTIQUES -->
<div class="stats-bar">
    <div class="stat-item">
        <i class="fas fa-microchip"></i>
        <div><strong><?= $nb_produits ?>+</strong><span>Produits</span></div>
    </div>
    <div class="stat-item">
        <i class="fas fa-layer-group"></i>
        <div><strong><?= $nb_categories ?></strong><span>Catégories</span></div>
    </div>
    <div class="stat-item">
        <i class="fas fa-tags"></i>
        <div><strong><?= $nb_marques ?></strong><span>Marques</span></div>
    </div>
    <div class="stat-item">
        <i class="fas fa-users"></i>
        <div><strong><?= $nb_clients ?>+</strong><span>Clients satisfaits</span></div>
    </div>
</div>

<div class="section home-layout">
    <!-- SIDEBAR CATEGORIES -->
    <aside class="cat-sidebar">
        <h3><i class="fas fa-bars"></i> Catégories</h3>
        <ul>
            <?php foreach ($categories_sidebar as $c): ?>
                <li>
                    <a href="catalogue.php?categorie=<?= (int)$c['id_categorie'] ?>">
                        <i class="fas <?= getIconeCategorie($c['NOM']) ?>"></i> <?= e($c['NOM']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <div class="home-main">
        <?php if ($offre_produit): ?>
        <!-- OFFRE DU JOUR -->
        <section class="offre-jour">
            <div class="offre-jour-info">
                <span class="offre-jour-tag"><i class="fas fa-bolt"></i> Offre du jour</span>
                <h3><?= e($offre_produit['nom_produit']) ?></h3>
                <p><?= e(mb_strimwidth($offre_produit['description'] ?? '', 0, 140, '...')) ?></p>
                <div class="offre-jour-prix"><?= number_format($offre_produit['prix_unitaire_MRU'], 2) ?> <small>MRU</small></div>
                <a href="catalogue.php?categorie=<?= (int)$offre_produit['id_categorie'] ?>" class="btn-primary">
                    <i class="fas fa-shopping-cart"></i> En profiter
                </a>
            </div>
            <div class="offre-jour-countdown" data-fin="<?= e(str_replace(' ', 'T', $offre_fin)) ?>">
                <div class="countdown-item"><span class="cd-val" data-unit="jours">00</span><span class="cd-label">Jours</span></div>
                <div class="countdown-item"><span class="cd-val" data-unit="heures">00</span><span class="cd-label">Heures</span></div>
                <div class="countdown-item"><span class="cd-val" data-unit="minutes">00</span><span class="cd-label">Min</span></div>
                <div class="countdown-item"><span class="cd-val" data-unit="secondes">00</span><span class="cd-label">Sec</span></div>
            </div>
        </section>
        <?php endif; ?>

        <!-- CATEGORIES POPULAIRES -->
        <?php if ($categories_pop): ?>
        <section>
            <h2 class="section-title">Catégories populaires</h2>
            <div class="categories-pop-grid">
                <?php foreach ($categories_pop as $c): ?>
                    <a href="catalogue.php?categorie=<?= (int)$c['id_categorie'] ?>" class="categorie-pop-card">
                        <i class="fas <?= getIconeCategorie($c['NOM']) ?>"></i>
                        <span class="cat-pop-nom"><?= e($c['NOM']) ?></span>
                        <span class="cat-pop-nb"><?= (int)$c['nb_produits'] ?> produit<?= $c['nb_produits'] > 1 ? 's' : '' ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- PRODUITS EN VEDETTE -->
        <section>
            <h2 class="section-title">Produits populaires</h2>
            <div class="produits-grid">
                <?php foreach ($vedette as $p):
                    $is_fav = in_array($p['id_produit'], $favoris_ids);
                    include __DIR__ . '/includes/product_card.php';
                endforeach; ?>
            </div>
            <div style="text-align:center;margin-top:24px">
                <a href="catalogue.php" class="btn-primary">Voir tous les produits <i class="fas fa-arrow-right"></i></a>
            </div>
        </section>
    </div>
</div>
</main>

<?php if ($offre_produit): ?>
<script>
(function() {
    var el = document.querySelector('.offre-jour-countdown');
    if (!el) return;
    var fin = new Date(el.dataset.fin).getTime();
    function maj() {
        var ecart = fin - Date.now();
        if (ecart < 0) ecart = 0;
        var jours    = Math.floor(ecart / (1000 * 60 * 60 * 24));
        var heures   = Math.floor((ecart / (1000 * 60 * 60)) % 24);
        var minutes  = Math.floor((ecart / (1000 * 60)) % 60);
        var secondes = Math.floor((ecart / 1000) % 60);
        el.querySelector('[data-unit="jours"]').textContent    = String(jours).padStart(2, '0');
        el.querySelector('[data-unit="heures"]').textContent   = String(heures).padStart(2, '0');
        el.querySelector('[data-unit="minutes"]').textContent  = String(minutes).padStart(2, '0');
        el.querySelector('[data-unit="secondes"]').textContent = String(secondes).padStart(2, '0');
    }
    maj();
    setInterval(maj, 1000);
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
