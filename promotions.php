<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';
require_once __DIR__ . '/produits.php';
require_once __DIR__ . '/includes/favoris_lib.php';

// Promotions actives
$promotions = $conn->query(
    "SELECT p.*, c.NOM
     FROM promotions p
     LEFT JOIN categories c ON p.id_categorie = c.id_categorie
     WHERE p.actif = 1
       AND CURDATE() BETWEEN p.date_debut AND p.date_fin
     ORDER BY p.valeur_remise DESC"
)->fetch_all(MYSQLI_ASSOC);

// Produits en promotion
$produits_promo = getProduitsEnPromo($conn);
$favoris_ids    = estConnecte() ? getIdsFavoris($conn, $_SESSION['id_utilisateur']) : [];
$seuil_nouveau  = getSeuilNouveau($conn);

$page_titre  = 'Promotions';
$page_active = 'promotions';
$extra_css = "
.promo-hero { background: linear-gradient(135deg, var(--orange), #c2410c); color: white; padding: 50px 24px; text-align: center; }
.promo-hero h1 { font-family: 'Syne', sans-serif; font-size: 34px; margin-bottom: 10px; }
.promo-hero p { font-size: 16px; opacity: 0.95; }
.codes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-bottom: 40px; }
.promo-card { background: white; border: 2px dashed var(--orange); border-radius: var(--radius-lg); padding: 20px; position: relative; transition: transform .2s, box-shadow .2s; }
.promo-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
.promo-card .badge-remise { position: absolute; top: -12px; right: 16px; background: var(--orange); color: white; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; }
.promo-card h3 { font-family: 'Syne', sans-serif; font-size: 16px; margin-bottom: 8px; }
.promo-card .description { color: var(--gris); font-size: 13px; margin-bottom: 12px; min-height: 38px; }
.promo-code-box { display: flex; align-items: center; gap: 8px; background: #fff7ed; border: 1px dashed var(--orange); border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; }
.promo-code-box code { font-family: monospace; font-weight: 700; font-size: 15px; color: var(--orange); letter-spacing: 1px; flex: 1; }
.copy-btn { background: var(--orange); color: white; border: none; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 11px; font-family: inherit; }
.promo-meta { font-size: 12px; color: var(--gris); }
@media (max-width: 480px) {
    .promo-hero h1 { font-size: 26px; }
    .codes-grid { grid-template-columns: 1fr; }
}";
include __DIR__ . '/includes/header.php';
?>

<main>
<section class="promo-hero">
    <h1><i class="fas fa-tags"></i> Promotions en cours</h1>
    <p>Profitez de nos meilleures offres sur les composants électroniques !</p>
</section>

<div class="section">
    <?php if (!empty($promotions)): ?>
    <h2 class="section-title">Codes promo disponibles</h2>
    <div class="codes-grid">
        <?php foreach ($promotions as $promo): ?>
        <div class="promo-card">
            <span class="badge-remise">
                <?php if ($promo['type_remise'] === 'pourcentage'): ?>
                    -<?= (int)$promo['valeur_remise'] ?>%
                <?php elseif ($promo['type_remise'] === 'montant_fixe'): ?>
                    -<?= (int)$promo['valeur_remise'] ?> MRU
                <?php else: ?>
                    Offre spéciale
                <?php endif; ?>
            </span>
            <h3><?= e($promo['code_promo']) ?></h3>
            <div class="description"><?= e($promo['description']) ?></div>
            <div class="promo-code-box">
                <code id="code-<?= (int)$promo['id_promo'] ?>"><?= e($promo['code_promo']) ?></code>
                <button class="copy-btn" onclick="copierCode('code-<?= (int)$promo['id_promo'] ?>', this)">
                    <i class="fas fa-copy"></i> Copier
                </button>
            </div>
            <div class="promo-meta">
                <i class="fas fa-calendar"></i> Valable jusqu'au <?= e(date('d/m/Y', strtotime($promo['date_fin']))) ?>
                <?php if (!empty($promo['min_commande_MRU']) && $promo['min_commande_MRU'] > 0): ?>
                    <br><i class="fas fa-shopping-cart"></i> Min. <?= (int)$promo['min_commande_MRU'] ?> MRU
                <?php endif; ?>
                <?php if (!empty($promo['NOM'])): ?>
                    <br><i class="fas fa-tag"></i> Catégorie : <?= e($promo['NOM']) ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="empty">
            <i class="fas fa-tags"></i>
            <p>Aucune promotion active pour le moment.</p>
            <p style="font-size:13px">Revenez bientôt pour découvrir nos prochaines offres !</p>
        </div>
    <?php endif; ?>

    <?php if (!empty($produits_promo)): ?>
    <h2 class="section-title" style="margin-top:36px">Produits en promotion</h2>
    <div class="produits-grid">
        <?php foreach ($produits_promo as $p):
            $is_fav = in_array($p['id_produit'], $favoris_ids);
            include __DIR__ . '/includes/product_card.php';
        endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</main>

<script>
function copierCode(id, btn) {
    const code = document.getElementById(id).textContent;
    navigator.clipboard.writeText(code).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copié !';
        setTimeout(() => btn.innerHTML = original, 1500);
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
