<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';
require_once __DIR__ . '/commandes.php';

exigerConnexion();

$commandes = getCommandesUtilisateur($conn, $_SESSION['id_utilisateur']);

$statut_colors = [
    'en_attente' => ['bg'=>'#fff7ed','color'=>'#ea580c'],
    'confirmée'  => ['bg'=>'#eff6ff','color'=>'#1a56db'],
    'en_cours'   => ['bg'=>'#f0fdf4','color'=>'#16a34a'],
    'expédiée'   => ['bg'=>'#f5f3ff','color'=>'#7c3aed'],
    'livrée'     => ['bg'=>'#f0fdf4','color'=>'#16a34a'],
    'annulée'    => ['bg'=>'#fef2f2','color'=>'#dc2626'],
];

$page_titre  = 'Mes Commandes';
$extra_css = "
.commandes-wrapper { max-width: 900px; margin: 32px auto; padding: 0 24px; }
.commandes-wrapper h2 { font-family: 'Syne', sans-serif; font-size: 22px; margin-bottom: 20px; }
.commande-card { background: white; border: 1px solid var(--border); border-radius: 16px; margin-bottom: 14px; overflow: hidden; }
.cmd-header { padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); background: var(--gris-light); gap: 8px; flex-wrap: wrap; }
.cmd-num { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 15px; }
.cmd-date { color: var(--gris); font-size: 13px; }
.cmd-body { padding: 14px 18px; }
.cmd-items { list-style: none; }
.cmd-items li { display: flex; gap: 10px; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 13.5px; }
.cmd-items li:last-child { border-bottom: none; }
.cmd-img { width: 36px; height: 36px; background: var(--gris-light); border-radius: 6px; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cmd-img img { width: 100%; height: 100%; object-fit: contain; padding: 3px; }
.cmd-item-name { flex: 1; }
.cmd-footer { display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; border-top: 1px solid var(--border); gap: 8px; flex-wrap: wrap; }
.cmd-total { font-size: 16px; font-weight: 700; }
.cmd-total span { color: var(--bleu); }
.badge { display: inline-block; padding: 5px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
@media (max-width: 480px) {
    .commandes-wrapper h2 { font-size: 20px; }
    .cmd-num { font-size: 14px; }
}";
include __DIR__ . '/includes/header.php';
?>

<main>
<div class="commandes-wrapper">
    <h2><i class="fas fa-list" style="color:var(--bleu)"></i> Mes Commandes</h2>

    <?php if (empty($commandes)): ?>
        <div class="empty">
            <i class="fas fa-shopping-bag"></i>
            <p>Vous n'avez pas encore de commandes.</p>
            <a href="catalogue.php" class="btn-primary" style="display:inline-flex;margin-top:8px">
                <i class="fas fa-th"></i> Commencer mes achats
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($commandes as $cmd):
            $details = getDetailsCommande($conn, $cmd['id_commande']);
            $sc = $statut_colors[$cmd['statut']] ?? ['bg'=>'#f1f5f9','color'=>'#64748b'];
        ?>
        <div class="commande-card">
            <div class="cmd-header">
                <div>
                    <div class="cmd-num">Commande #<?= (int)$cmd['id_commande'] ?></div>
                    <div class="cmd-date"><?= e(date('d/m/Y à H:i', strtotime($cmd['date_commande']))) ?></div>
                </div>
                <span class="badge" style="background:<?= e($sc['bg']) ?>;color:<?= e($sc['color']) ?>">
                    <?= e(ucfirst($cmd['statut'])) ?>
                </span>
            </div>
            <div class="cmd-body">
                <ul class="cmd-items">
                    <?php foreach ($details as $d): ?>
                    <li>
                        <div class="cmd-img">
                            <?php if (!empty($d['image'])): ?>
                                <img src="images/<?= e($d['image']) ?>" alt="" onerror="this.style.display='none'">
                            <?php else: ?>
                                <i class="fas fa-microchip" style="color:#cbd5e1"></i>
                            <?php endif; ?>
                        </div>
                        <span class="cmd-item-name"><?= e($d['nom_produit']) ?> × <?= (int)$d['quantite'] ?></span>
                        <span><?= number_format($d['quantite'] * $d['prix_unitaire_MRU'], 2) ?> MRU</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="cmd-footer">
                <div class="cmd-total">Total : <span><?= number_format($cmd['total_MRU'], 2) ?> MRU</span></div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <?php
                    // Badge statut de paiement
                    $sp = $cmd['statut_paiement'] ?? 'en_attente';
                    $sp_styles = [
                        'en_attente' => ['#fef9c3','#a16207','En attente de paiement'],
                        'a_verifier' => ['#dbeafe','#1d4ed8','Paiement en vérification'],
                        'paye'       => ['#dcfce7','#15803d','Payé'],
                        'refuse'     => ['#fee2e2','#b91c1c','Paiement refusé'],
                    ];
                    [$bg,$col,$lbl] = $sp_styles[$sp] ?? ['#f1f5f9','#64748b',$sp];
                    ?>
                    <span class="badge" style="background:<?= $bg ?>;color:<?= $col ?>">
                        <i class="fas fa-credit-card"></i> <?= e($lbl) ?>
                    </span>

                    <?php
                    // Bouton selon la situation
                    $methode = $cmd['methode_paiement'] ?? 'sur_place';
                    if ($methode === 'bancaire' && in_array($sp, ['en_attente','refuse'], true)): ?>
                        <a href="confirmation.php?id=<?= (int)$cmd['id_commande'] ?>" class="btn-primary">
                            <i class="fas fa-mobile-alt"></i> Payer maintenant
                        </a>
                    <?php elseif ($methode === 'whatsapp'): ?>
                        <a href="confirmation.php?id=<?= (int)$cmd['id_commande'] ?>" class="btn-secondary">
                            <i class="fab fa-whatsapp"></i> Voir / WhatsApp
                        </a>
                    <?php else: ?>
                        <a href="confirmation.php?id=<?= (int)$cmd['id_commande'] ?>" class="btn-secondary">
                            <i class="fas fa-eye"></i> Détails
                        </a>
                    <?php endif; ?>

                    <?php if ($cmd['statut'] === 'livrée'): ?>
                        <a href="avis.php?id_commande=<?= (int)$cmd['id_commande'] ?>" class="btn-primary">
                            <i class="fas fa-star"></i> Laisser un avis
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
