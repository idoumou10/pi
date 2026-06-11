<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';
require_once __DIR__ . '/panier.php';

// Actions
if (isset($_GET['supprimer'])) {
    if (hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
        supprimerDuPanier(getId($_GET['supprimer']));
    }
    header('Location: panier_page.php'); exit;
}
if (isset($_POST['modifier'])) {
    if (csrfVerify()) {
        modifierQuantite($conn, getId($_POST['id_produit'] ?? 0), (int)($_POST['quantite'] ?? 1));
    }
    header('Location: panier_page.php'); exit;
}
if (isset($_GET['vider'])) {
    if (hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
        viderPanier();
    }
    header('Location: panier_page.php'); exit;
}

$panier = getPanier($conn);
$total  = getTotalPanier($conn);
$nb     = getNombreArticles();

$page_titre  = 'Mon Panier';
$page_active = 'panier';
$extra_css = "
.panier-wrapper { max-width: 1100px; margin: 32px auto; padding: 0 24px; display: grid; grid-template-columns: 1fr 320px; gap: 24px; }
.panier-wrapper h2 { font-family: 'Syne', sans-serif; font-size: 22px; margin-bottom: 20px; }
.panier-box { background: white; border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
.panier-header { background: var(--gris-light); padding: 14px 20px; font-weight: 700; font-size: 14px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.panier-header a { color: var(--rouge); font-size: 13px; text-decoration: none; }
.panier-item { display: grid; grid-template-columns: 70px 1fr auto; gap: 16px; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border); }
.panier-item:last-child { border-bottom: none; }
.item-img-wrap { width: 70px; height: 70px; background: var(--gris-light); border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.item-img-wrap img { width: 100%; height: 100%; object-fit: contain; padding: 6px; }
.item-img-wrap .fallback-icon { font-size: 28px; color: #cbd5e1; display: none; }
.item-img-wrap .fallback-icon.visible { display: block; }
.item-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
.item-prix { color: var(--bleu); font-weight: 700; font-size: 14px; }
.item-stock { color: var(--gris); font-size: 12px; margin-top: 4px; }
.item-actions { display: flex; align-items: center; gap: 10px; }
.qte-form { display: flex; align-items: center; gap: 6px; }
.qte-form input { width: 56px; padding: 6px 8px; border: 2px solid var(--border); border-radius: 8px; text-align: center; font-size: 14px; font-family: 'DM Sans', sans-serif; outline: none; }
.qte-form input:focus { border-color: var(--bleu); }
.btn-update { background: var(--bleu); color: white; border: none; cursor: pointer; padding: 7px 11px; border-radius: 8px; font-size: 12px; }
.btn-delete { color: var(--rouge); background: none; border: none; cursor: pointer; font-size: 16px; padding: 6px; }
.resume { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 24px; height: fit-content; position: sticky; top: 90px; }
.resume h3 { font-family: 'Syne', sans-serif; font-size: 18px; margin-bottom: 20px; }
.resume-line { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 10px; color: var(--gris); gap: 8px; }
.resume-line span:first-child { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.resume-total { display: flex; justify-content: space-between; font-size: 18px; font-weight: 700; border-top: 2px solid var(--border); padding-top: 14px; margin-top: 12px; }
.resume-total span:last-child { color: var(--bleu); }
.btn-commande { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; background: var(--bleu); color: white; border: none; padding: 14px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; margin-top: 18px; transition: background .2s; }
.btn-commande:hover { background: var(--bleu-dark); }
.btn-continuer { display: block; text-align: center; color: var(--gris); text-decoration: none; font-size: 13px; margin-top: 12px; }
.btn-continuer:hover { color: var(--bleu); }
@media (max-width: 1024px) {
    .panier-wrapper { grid-template-columns: 1fr; }
    .resume { position: static; }
}
@media (max-width: 480px) {
    .panier-item { grid-template-columns: 60px 1fr; }
    .item-actions { grid-column: 1 / -1; justify-content: space-between; padding-top: 6px; }
    .item-img-wrap { width: 60px; height: 60px; }
}";
include __DIR__ . '/includes/header.php';
?>

<main>
<div class="panier-wrapper">
    <div>
        <h2><i class="fas fa-shopping-cart"></i> Mon Panier (<?= (int)$nb ?> article<?= $nb > 1 ? 's' : '' ?>)</h2>

        <div class="panier-box">
            <div class="panier-header">
                <span>Produits</span>
                <?php if (!empty($panier)): ?>
                    <a href="panier_page.php?vider=1&csrf=<?= csrfToken() ?>"
                       onclick="return confirm('Vider le panier ?')">
                        <i class="fas fa-trash"></i> Vider le panier
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($panier)): ?>
                <div class="empty">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Votre panier est vide.</p>
                    <a href="catalogue.php" class="btn-primary" style="display:inline-flex;margin-top:8px">
                        <i class="fas fa-th"></i> Continuer les achats
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($panier as $p): ?>
                <div class="panier-item">
                    <div class="item-img-wrap">
                        <?php if (!empty($p['image'])): ?>
                            <img src="images/<?= e($p['image']) ?>" alt="<?= e($p['nom_produit']) ?>"
                                 onerror="this.style.display='none'; this.parentElement.querySelector('.fallback-icon').classList.add('visible');">
                            <i class="fas fa-microchip fallback-icon"></i>
                        <?php else: ?>
                            <i class="fas fa-microchip fallback-icon visible"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="item-name"><?= e($p['nom_produit']) ?></div>
                        <div class="item-prix"><?= number_format($p['prix_unitaire_MRU'], 2) ?> MRU / unité</div>
                        <div class="item-stock">Stock disponible : <?= (int)$p['stock_disponible'] ?></div>
                    </div>
                    <div class="item-actions">
                        <form class="qte-form" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="id_produit" value="<?= (int)$p['id_produit'] ?>">
                            <input type="number" name="quantite" value="<?= (int)$p['quantite'] ?>"
                                   min="1" max="<?= (int)$p['stock_disponible'] ?>" aria-label="Quantité">
                            <button type="submit" name="modifier" class="btn-update" aria-label="Mettre à jour">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <a class="btn-delete" href="panier_page.php?supprimer=<?= (int)$p['id_produit'] ?>&csrf=<?= csrfToken() ?>"
                           onclick="return confirm('Supprimer ce produit ?')" aria-label="Supprimer">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- RÉSUMÉ -->
    <div class="resume">
        <h3>Résumé de la commande</h3>
        <?php if (empty($panier)): ?>
            <p style="color:var(--gris);font-size:14px">Aucun article dans le panier.</p>
        <?php else: ?>
            <?php foreach ($panier as $p): ?>
                <div class="resume-line">
                    <span><?= e($p['nom_produit']) ?> × <?= (int)$p['quantite'] ?></span>
                    <span><?= number_format($p['sous_total'], 2) ?> MRU</span>
                </div>
            <?php endforeach; ?>
            <div class="resume-line"><span>Livraison</span><span style="color:var(--vert)">Gratuite</span></div>
            <div class="resume-total">
                <span>Total</span>
                <span><?= number_format($total, 2) ?> MRU</span>
            </div>
            <a href="commande.php" class="btn-commande">
                <i class="fas fa-credit-card"></i> Passer la commande
            </a>
        <?php endif; ?>
        <a href="catalogue.php" class="btn-continuer">← Continuer les achats</a>
    </div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
