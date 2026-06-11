<?php
$page_titre = 'Tableau de bord';
$active = 'dashboard';
include __DIR__ . '/../includes/admin_header.php';

// Stats globales
$nb_produits     = (int)$conn->query("SELECT COUNT(*) AS n FROM produit")->fetch_assoc()['n'];
$nb_commandes    = (int)$conn->query("SELECT COUNT(*) AS n FROM commandes")->fetch_assoc()['n'];
$nb_utilisateurs = (int)$conn->query("SELECT COUNT(*) AS n FROM utilisateurs WHERE role='client'")->fetch_assoc()['n'];
$nb_attente      = (int)$conn->query("SELECT COUNT(*) AS n FROM commandes WHERE statut='en_attente'")->fetch_assoc()['n'];
$ca_total        = (float)($conn->query("SELECT SUM(total_MRU) AS s FROM commandes WHERE statut IN ('livrée','expédiée','en_cours','confirmée')")->fetch_assoc()['s'] ?? 0);
$nb_rupture      = (int)$conn->query("SELECT COUNT(*) AS n FROM produit WHERE stock_disponible <= seuil_alerte_stock")->fetch_assoc()['n'];

// Dernières commandes
$recent_cmds = $conn->query(
    "SELECT c.*, u.nom, u.prenom FROM commandes c
     LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
     ORDER BY c.date_commande DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

// Produits en rupture
$rupture_produits = $conn->query(
    "SELECT id_produit, nom_produit, stock_disponible, seuil_alerte_stock
     FROM produit
     WHERE stock_disponible <= seuil_alerte_stock
     ORDER BY stock_disponible ASC
     LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Tableau de bord</h1>
    <span style="color:var(--gris);font-size:13px">
        Bienvenue <?= e($_SESSION['prenom']) ?> 👋
    </span>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-box"></i></div>
        <div>
            <div class="stat-num"><?= number_format($nb_produits) ?></div>
            <div class="stat-label">Produits</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div>
            <div class="stat-num"><?= number_format($nb_commandes) ?></div>
            <div class="stat-label">Commandes totales</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div>
            <div class="stat-num"><?= number_format($nb_attente) ?></div>
            <div class="stat-label">En attente</div>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div>
            <div class="stat-num"><?= number_format($nb_utilisateurs) ?></div>
            <div class="stat-label">Clients</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-coins"></i></div>
        <div>
            <div class="stat-num"><?= number_format($ca_total, 0) ?> <small style="font-size:12px">MRU</small></div>
            <div class="stat-label">Chiffre d'affaires</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="stat-num"><?= number_format($nb_rupture) ?></div>
            <div class="stat-label">Stock faible</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px" class="dashboard-2cols">
    <!-- Dernières commandes -->
    <div class="table-box">
        <div class="table-header">
            <h3>Dernières commandes</h3>
            <a href="commandes.php" class="btn-mini btn-view">Voir tout</a>
        </div>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>#</th><th>Client</th><th>Total</th><th>Statut</th></tr></thead>
                <tbody>
                    <?php if (empty($recent_cmds)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--gris);padding:24px">Aucune commande</td></tr>
                    <?php else: foreach ($recent_cmds as $c): ?>
                    <tr>
                        <td>#<?= (int)$c['id_commande'] ?></td>
                        <td><?= e(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? '')) ?></td>
                        <td><strong><?= number_format($c['total_MRU'], 2) ?> MRU</strong></td>
                        <td><span class="badge badge-<?= e($c['statut']) ?>"><?= e($c['statut']) ?></span></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stock faible -->
    <div class="table-box">
        <div class="table-header">
            <h3>Stock à reapprovisionner</h3>
            <a href="produits.php" class="btn-mini btn-view">Voir tout</a>
        </div>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Produit</th><th>Stock</th><th>Seuil</th></tr></thead>
                <tbody>
                    <?php if (empty($rupture_produits)): ?>
                        <tr><td colspan="3" style="text-align:center;color:var(--gris);padding:24px">Stock OK 👍</td></tr>
                    <?php else: foreach ($rupture_produits as $p): ?>
                    <tr>
                        <td><?= e($p['nom_produit']) ?></td>
                        <td>
                            <span class="badge <?= $p['stock_disponible']==0 ? 'badge-inactif' : 'badge-pct' ?>">
                                <?= (int)$p['stock_disponible'] ?>
                            </span>
                        </td>
                        <td><?= (int)$p['seuil_alerte_stock'] ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media (max-width: 1024px) {
    .dashboard-2cols { grid-template-columns: 1fr !important; }
}
</style>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
