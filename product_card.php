<?php
// ============================================================
// INCLUDES/PRODUCT_CARD.PHP — Carte produit mutualisée
// Variables attendues :
//   $p             : ligne produit (avec NOM, nom_marque, note_moyenne, nb_avis, en_promo)
//   $is_fav        : bool — produit dans les favoris de l'utilisateur connecté
//   $seuil_nouveau : int  — id_produit à partir duquel le badge "Nouveau" s'affiche
//   $drawer_data   : string|null — JSON (déjà échappé) pour ouvrir le tiroir détail (catalogue)
// ============================================================

$is_fav        = $is_fav ?? false;
$seuil_nouveau = $seuil_nouveau ?? PHP_INT_MAX;
$drawer_data   = $drawer_data ?? null;

$est_promo   = !empty($p['en_promo']);
$est_nouveau = (int)$p['id_produit'] >= $seuil_nouveau;
$note        = isset($p['note_moyenne']) ? round((float)$p['note_moyenne'], 1) : 0;
$nb_avis     = (int)($p['nb_avis'] ?? 0);

$attrs = '';
if ($drawer_data !== null) {
    $attrs = ' onclick="ouvrirDrawer(' . $drawer_data . ')" role="button" tabindex="0"'
           . ' onkeydown="if(event.key===\'Enter\') ouvrirDrawer(' . $drawer_data . ')"';
}
?>
<div class="card"<?= $attrs ?>>
    <div class="card-img">
        <?php if ($est_promo || $est_nouveau): ?>
            <div class="card-badges">
                <?php if ($est_promo): ?><span class="card-badge badge-promo">Promo</span><?php endif; ?>
                <?php if ($est_nouveau): ?><span class="card-badge badge-nouveau">Nouveau</span><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($p['image'])): ?>
            <img src="images/<?= e($p['image']) ?>" alt="<?= e($p['nom_produit']) ?>"
                 onerror="this.style.display='none'; this.parentElement.querySelector('.fallback-icon').classList.add('visible');">
            <i class="fas fa-microchip fallback-icon"></i>
        <?php else: ?>
            <i class="fas fa-microchip fallback-icon visible"></i>
        <?php endif; ?>

        <?php if (estConnecte()): ?>
            <a href="<?= $drawer_data !== null ? '#' : 'favoris.php?action=toggle&id=' . (int)$p['id_produit'] . '&csrf=' . csrfToken() ?>"
               class="card-fav <?= $is_fav ? 'active' : '' ?>"
               <?php if ($drawer_data !== null): ?>onclick="event.stopPropagation(); toggleFavoriAjax(event, <?= (int)$p['id_produit'] ?>, this)"<?php endif; ?>
               aria-label="<?= $is_fav ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                <i class="<?= $is_fav ? 'fas' : 'far' ?> fa-heart"></i>
            </a>
        <?php else: ?>
            <a href="login.php" class="card-fav" <?php if ($drawer_data !== null): ?>onclick="event.stopPropagation()"<?php endif; ?> aria-label="Se connecter pour favoriser">
                <i class="far fa-heart"></i>
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="card-cat"><?= e($p['NOM'] ?? '') ?></div>
        <div class="card-name"><?= e($p['nom_produit']) ?></div>
        <?php if ($nb_avis > 0): ?>
            <div class="card-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="<?= $i <= round($note) ? 'fas' : 'far' ?> fa-star"></i>
                <?php endfor; ?>
                <span>(<?= $nb_avis ?>)</span>
            </div>
        <?php endif; ?>
        <div class="card-price"><?= number_format($p['prix_unitaire_MRU'], 2) ?> <small>MRU</small></div>
        <div class="card-footer">
            <span class="stock <?= $p['stock_disponible'] > 0 ? '' : 'rupture' ?>">
                <?= $p['stock_disponible'] > 0 ? 'En stock' : 'Rupture' ?>
            </span>
            <?php if ($p['stock_disponible'] > 0): ?>
                <a href="ajouter_panier.php?id=<?= (int)$p['id_produit'] ?>&csrf=<?= csrfToken() ?>"
                   <?php if ($drawer_data !== null): ?>onclick="event.stopPropagation()"<?php endif; ?>
                   class="btn-add" aria-label="Ajouter au panier"><i class="fas fa-plus"></i></a>
            <?php else: ?>
                <span class="btn-add disabled"><i class="fas fa-times"></i></span>
            <?php endif; ?>
        </div>
    </div>
</div>
