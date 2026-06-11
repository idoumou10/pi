<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
require_once __DIR__ . '/../commandes.php';
exigerAdmin();

$message = ''; $erreur = '';
$page    = max(1, (int)($_GET['p'] ?? 1));
$par_page = 12;

// --- Changer le statut de la commande ---
if (isset($_POST['changer_statut'])) {
    if (!csrfVerify()) {
        $erreur = 'Session expirée.';
    } else {
        $id_cmd = getId($_POST['id_commande'] ?? 0);
        $statut = clean($_POST['statut'] ?? '');
        if (changerStatutCommande($conn, $id_cmd, $statut)) $message = 'Statut mis à jour.';
        else $erreur = 'Statut invalide.';
    }
}

// --- Valider / refuser un paiement ---
if (isset($_POST['decider_paiement'])) {
    if (!csrfVerify()) {
        $erreur = 'Session expirée.';
    } else {
        $id_cmd   = getId($_POST['id_commande'] ?? 0);
        $decision = clean($_POST['decision'] ?? '');
        if (deciderPaiement($conn, $id_cmd, $decision)) {
            $message = $decision === 'paye' ? 'Paiement validé ✓' : 'Paiement refusé.';
        } else {
            $erreur = 'Décision invalide.';
        }
    }
}

// --- Détail d'une commande ---
$id_detail = getId($_GET['detail'] ?? 0);
$details = []; $commande_sel = null;
if ($id_detail) {
    $stmt = $conn->prepare(
        "SELECT c.*, u.nom, u.prenom, u.email, u.telephone
         FROM commandes c
         LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
         WHERE c.id_commande = ?"
    );
    $stmt->bind_param("i", $id_detail);
    $stmt->execute();
    $commande_sel = $stmt->get_result()->fetch_assoc();
    if ($commande_sel) $details = getDetailsCommande($conn, $id_detail);
}

// --- Filtre par statut de paiement ---
$filtre = $_GET['filtre'] ?? 'tous';
$where  = '';
if ($filtre === 'a_verifier')  $where = "WHERE c.statut_paiement = 'a_verifier'";
elseif ($filtre === 'paye')    $where = "WHERE c.statut_paiement = 'paye'";
elseif ($filtre === 'attente') $where = "WHERE c.statut_paiement = 'en_attente'";

// Total + pagination
$total = (int)$conn->query("SELECT COUNT(*) AS n FROM commandes c $where")->fetch_assoc()['n'];
$total_pages = max(1, (int)ceil($total / $par_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $par_page;

$commandes = $conn->query(
    "SELECT c.*, u.nom, u.prenom, u.email
     FROM commandes c
     LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
     $where
     ORDER BY c.date_commande DESC
     LIMIT $par_page OFFSET $offset"
)->fetch_all(MYSQLI_ASSOC);

// Compteur paiements à vérifier (pour le badge)
$nb_a_verifier = (int)$conn->query("SELECT COUNT(*) AS n FROM commandes WHERE statut_paiement='a_verifier'")->fetch_assoc()['n'];

$statuts = ['en_attente', 'confirmée', 'en_cours', 'expédiée', 'livrée', 'annulée'];

// Helpers d'affichage
function badgePaiement($st) {
    $map = [
        'en_attente' => ['bp-attente','En attente'],
        'a_verifier' => ['bp-verifier','À vérifier'],
        'paye'       => ['bp-paye','Payé'],
        'refuse'     => ['bp-refuse','Refusé'],
    ];
    [$cls,$lbl] = $map[$st] ?? ['bp-attente', $st];
    return '<span class="badge-pay '.$cls.'">'.htmlspecialchars($lbl).'</span>';
}
function labelMethode($m) {
    return ['sur_place'=>'💵 Sur place','bancaire'=>'📱 App bancaire','whatsapp'=>'💬 WhatsApp'][$m] ?? $m;
}

$page_titre = 'Commandes';
$active = 'commandes';

// ── Données pour l'EXPORT (toutes les commandes, tous filtres confondus) ──
$toutes_cmd = $conn->query(
    "SELECT c.*, u.nom, u.prenom, u.email
     FROM commandes c
     LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
     ORDER BY c.date_commande DESC"
)->fetch_all(MYSQLI_ASSOC);

$export_commandes = [];
$export_commandes[] = ['ID','Date','Client','Email','Téléphone','Adresse',
                       'Total MRU','Méthode paiement','Statut paiement',
                       'Référence transaction','Statut commande'];
foreach ($toutes_cmd as $c) {
    $client = $c['est_invite']
        ? (($c['nom_invite'] ?? '') . ' (invité)')
        : (($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
    $export_commandes[] = [
        $c['id_commande'],
        date('d/m/Y H:i', strtotime($c['date_commande'])),
        $client,
        $c['email'] ?? '',
        $c['tel_contact'] ?? '',
        $c['adresse_livraison'] ?? '',
        $c['total_MRU'],
        $c['methode_paiement'],
        $c['statut_paiement'],
        $c['reference_transaction'] ?? '',
        $c['statut'],
    ];
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-shopping-bag"></i> Commandes (<?= number_format($total) ?>)</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <?php if ($nb_a_verifier > 0): ?>
            <a href="?filtre=a_verifier" class="btn-mini" style="background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;padding:9px 14px">
                <i class="fas fa-bell"></i> <?= $nb_a_verifier ?> paiement(s) à vérifier
            </a>
        <?php endif; ?>
        <button class="btn-excel" onclick="exporterCommandes()">
            <i class="fas fa-file-excel"></i> Exporter les commandes
        </button>
    </div>
</div>

<?php if ($message): ?><div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div><?php endif; ?>
<?php if ($erreur):  ?><div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur)  ?></div><?php endif; ?>

<!-- Filtres -->
<div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
    <?php
    $filtres = ['tous'=>'Toutes','attente'=>'Paiement en attente','a_verifier'=>'À vérifier','paye'=>'Payées'];
    foreach ($filtres as $cle=>$lbl):
        $actif = $filtre === $cle;
    ?>
        <a href="?filtre=<?= $cle ?>" class="btn-mini <?= $actif?'btn-edit':'btn-view' ?>"
           style="padding:8px 14px"><?= e($lbl) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($commande_sel): ?>
<!-- ===== DÉTAIL COMMANDE ===== -->
<div class="form-box" style="margin-bottom:24px;background:white;border:1px solid var(--border);border-radius:14px;padding:22px">
    <h3 style="display:flex;justify-content:space-between;align-items:center;font-family:'Syne',sans-serif">
        <span>📦 Commande #<?= (int)$commande_sel['id_commande'] ?></span>
        <a href="commandes.php" class="btn-mini btn-view"><i class="fas fa-times"></i> Fermer</a>
    </h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin:16px 0">
        <div style="font-size:14px;line-height:1.9">
            <strong>Client :</strong>
            <?php if ($commande_sel['est_invite']): ?>
                <?= e($commande_sel['nom_invite'] ?? '—') ?>
                <span class="badge badge-pct" style="font-size:11px">INVITÉ</span>
            <?php else: ?>
                <?= e(($commande_sel['prenom'] ?? '') . ' ' . ($commande_sel['nom'] ?? '')) ?>
            <?php endif; ?>
            <br>
            <strong>Email :</strong> <?= e($commande_sel['email'] ?? '—') ?><br>
            <strong>Téléphone :</strong> <?= e($commande_sel['tel_contact'] ?? '—') ?><br>
            <strong>Adresse :</strong> <?= e($commande_sel['adresse_livraison'] ?? '—') ?>
        </div>
        <div style="font-size:14px;line-height:1.9">
            <strong>Date :</strong> <?= e(date('d/m/Y H:i', strtotime($commande_sel['date_commande']))) ?><br>
            <strong>Total :</strong> <span style="color:var(--bleu);font-weight:700"><?= number_format($commande_sel['total_MRU'], 2) ?> MRU</span><br>
            <strong>Méthode :</strong> <?= labelMethode($commande_sel['methode_paiement']) ?><br>
            <strong>Paiement :</strong> <?= badgePaiement($commande_sel['statut_paiement']) ?>
        </div>
    </div>

    <?php if ($commande_sel['methode_paiement'] === 'bancaire'): ?>
    <!-- Bloc vérification paiement bancaire -->
    <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:16px;margin-bottom:16px">
        <strong style="color:#1d4ed8"><i class="fas fa-mobile-alt"></i> Paiement par application bancaire</strong>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:10px;font-size:14px">
            <div>
                <strong>Référence transaction :</strong><br>
                <?php if (!empty($commande_sel['reference_transaction'])): ?>
                    <span class="code-display" style="font-size:14px"><?= e($commande_sel['reference_transaction']) ?></span>
                <?php else: ?>
                    <span style="color:var(--gris)">Pas encore fournie</span>
                <?php endif; ?>
                <?php if (!empty($commande_sel['date_paiement_soumis'])): ?>
                    <br><small style="color:var(--gris)">Soumis le <?= e(date('d/m/Y H:i', strtotime($commande_sel['date_paiement_soumis']))) ?></small>
                <?php endif; ?>
            </div>
            <div>
                <strong>Capture d'écran :</strong><br>
                <?php if (!empty($commande_sel['capture_paiement'])): ?>
                    <a href="../<?= e($commande_sel['capture_paiement']) ?>" target="_blank" rel="noopener">
                        <img src="../<?= e($commande_sel['capture_paiement']) ?>" alt="Preuve"
                             style="max-width:140px;max-height:120px;border:1px solid var(--border);border-radius:8px;margin-top:4px;cursor:zoom-in">
                    </a>
                <?php else: ?>
                    <span style="color:var(--gris)">Aucune capture</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($commande_sel['statut_paiement'] === 'a_verifier'): ?>
        <!-- Boutons Valider / Refuser -->
        <div style="display:flex;gap:8px;margin-top:14px;border-top:1px solid #bfdbfe;padding-top:12px">
            <form method="POST" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="id_commande" value="<?= (int)$commande_sel['id_commande'] ?>">
                <input type="hidden" name="decision" value="paye">
                <button type="submit" name="decider_paiement" class="btn-mini"
                        style="background:#16a34a;color:white;border:none;padding:9px 16px"
                        onclick="return confirm('Confirmer que le paiement a bien été reçu ?')">
                    <i class="fas fa-check"></i> Valider le paiement
                </button>
            </form>
            <form method="POST" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="id_commande" value="<?= (int)$commande_sel['id_commande'] ?>">
                <input type="hidden" name="decision" value="refuse">
                <button type="submit" name="decider_paiement" class="btn-mini btn-del"
                        style="padding:9px 16px"
                        onclick="return confirm('Refuser ce paiement ? Le client devra renvoyer une preuve.')">
                    <i class="fas fa-times"></i> Refuser le paiement
                </button>
            </form>
        </div>
        <?php elseif ($commande_sel['statut_paiement'] === 'paye'): ?>
            <div style="margin-top:12px;color:#15803d;font-weight:600"><i class="fas fa-check-circle"></i> Paiement validé.</div>
        <?php elseif ($commande_sel['statut_paiement'] === 'refuse'): ?>
            <div style="margin-top:12px;color:#b91c1c;font-weight:600"><i class="fas fa-times-circle"></i> Paiement refusé.</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <table class="admin-table">
        <thead><tr><th>Produit</th><th>Qté</th><th>Prix</th><th>Sous-total</th></tr></thead>
        <tbody>
            <?php foreach ($details as $d): ?>
            <tr>
                <td><?= e($d['nom_produit']) ?></td>
                <td><?= (int)$d['quantite'] ?></td>
                <td><?= number_format($d['prix_unitaire_MRU'], 2) ?></td>
                <td><strong><?= number_format($d['sous_total_MRU'], 2) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ===== LISTE DES COMMANDES ===== -->
<div class="table-box">
    <div class="table-header">
        <h3>Liste des commandes</h3>
        <div class="table-tools">
            <div class="admin-search">
                <i class="fas fa-search"></i>
                <input type="text" id="search-cmd" placeholder="Rechercher (client, #, méthode...)..." autocomplete="off">
                <button type="button" class="search-clear" id="search-clear" aria-label="Effacer">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="search-results-info" id="search-info"></div>
    <div class="table-wrap">
        <table class="admin-table" id="tbl-commandes">
            <thead>
                <tr>
                    <th>#</th><th>Date</th><th>Client</th><th>Total</th>
                    <th>Méthode</th><th>Paiement</th><th>Statut</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($commandes)): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--gris);padding:32px">Aucune commande.</td></tr>
                <?php else: foreach ($commandes as $c): ?>
                <tr <?= $c['statut_paiement']==='a_verifier'?'style="background:#eff6ff"':'' ?>>
                    <td>#<?= (int)$c['id_commande'] ?></td>
                    <td style="font-size:13px"><?= e(date('d/m/Y H:i', strtotime($c['date_commande']))) ?></td>
                    <td>
                        <?php if ($c['est_invite']): ?>
                            <?= e($c['nom_invite'] ?? '—') ?>
                            <span class="badge badge-pct" style="font-size:10px">INVITÉ</span>
                        <?php else: ?>
                            <?= e(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? '')) ?>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= number_format($c['total_MRU'], 2) ?> MRU</strong></td>
                    <td style="font-size:13px"><?= labelMethode($c['methode_paiement']) ?></td>
                    <td><?= badgePaiement($c['statut_paiement']) ?></td>
                    <td>
                        <form method="POST" style="display:inline-flex;gap:4px;align-items:center">
                            <?= csrfField() ?>
                            <input type="hidden" name="id_commande" value="<?= (int)$c['id_commande'] ?>">
                            <select name="statut" style="padding:5px 8px;border:2px solid var(--border);border-radius:6px;font-size:12px;font-family:inherit">
                                <?php foreach ($statuts as $s): ?>
                                    <option value="<?= e($s) ?>" <?= $c['statut']===$s?'selected':'' ?>><?= e($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="changer_statut" class="btn-mini btn-edit"><i class="fas fa-check"></i></button>
                        </form>
                    </td>
                    <td>
                        <a href="?detail=<?= (int)$c['id_commande'] ?>&filtre=<?= e($filtre) ?>" class="btn-mini btn-view"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION JOLIE -->
    <?php if ($total > 0): ?>
    <div class="pagination-bar">
        <div class="pagination-info">
            Affichage <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $par_page, $total) ?></strong>
            sur <strong><?= number_format($total) ?></strong> commandes
        </div>
        <nav class="pagination">
            <a href="?p=<?= max(1,$page-1) ?>&filtre=<?= e($filtre) ?>" class="page-fleche <?= $page<=1?'off':'' ?>"><i class="fas fa-chevron-left"></i></a>
            <?php
            $debut = max(1, $page - 2);
            $fin   = min($total_pages, $page + 2);
            if ($debut > 1) {
                echo '<a href="?p=1&filtre='.e($filtre).'">1</a>';
                if ($debut > 2) echo '<span class="page-points">…</span>';
            }
            for ($i = $debut; $i <= $fin; $i++) {
                if ($i == $page) echo '<span class="page-actuelle">'.$i.'</span>';
                else echo '<a href="?p='.$i.'&filtre='.e($filtre).'">'.$i.'</a>';
            }
            if ($fin < $total_pages) {
                if ($fin < $total_pages - 1) echo '<span class="page-points">…</span>';
                echo '<a href="?p='.$total_pages.'&filtre='.e($filtre).'">'.$total_pages.'</a>';
            }
            ?>
            <a href="?p=<?= min($total_pages,$page+1) ?>&filtre=<?= e($filtre) ?>" class="page-fleche <?= $page>=$total_pages?'off':'' ?>"><i class="fas fa-chevron-right"></i></a>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
// RECHERCHE INSTANTANÉE
(function() {
    const input  = document.getElementById('search-cmd');
    const clearB = document.getElementById('search-clear');
    const info   = document.getElementById('search-info');
    const rows   = document.querySelectorAll('#tbl-commandes tbody tr');
    function applyFilter() {
        const q = input.value.toLowerCase().trim();
        clearB.classList.toggle('visible', q.length > 0);
        let n = 0;
        rows.forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            const txt = row.textContent.toLowerCase();
            const show = !q || txt.includes(q);
            row.classList.toggle('hidden', !show);
            if (show) n++;
        });
        if (q) {
            info.textContent = `🔎 ${n} résultat(s) pour "${input.value}" (sur cette page)`;
            info.classList.add('visible');
        } else info.classList.remove('visible');
    }
    input.addEventListener('input', applyFilter);
    clearB.addEventListener('click', () => { input.value=''; applyFilter(); input.focus(); });
})();

// EXPORT DES COMMANDES (toutes, format archive)
const DATA_COMMANDES = <?= json_encode($export_commandes, JSON_UNESCAPED_UNICODE) ?>;
function exporterCommandes() {
    const csv = DATA_COMMANDES.map(ligne =>
        ligne.map(cell => {
            const v = (cell === null || cell === undefined) ? '' : String(cell);
            return '"' + v.replace(/"/g, '""') + '"';
        }).join(',')
    ).join('\r\n');
    const blob = new Blob(['\uFEFF' + csv], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'commandes_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
