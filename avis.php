<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
exigerAdmin();

$message = ''; $erreur = '';
$page    = max(1, (int)($_GET['p'] ?? 1));
$par_page = 10;

// Suppression
if (isset($_GET['supprimer'])) {
    $id_avis = getId($_GET['supprimer']);
    if ($id_avis && hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
        $stmt = $conn->prepare("DELETE FROM avis WHERE id_avis = ?");
        $stmt->bind_param("i", $id_avis);
        if ($stmt->execute()) $message = 'Avis supprimé.';
        else $erreur = 'Erreur lors de la suppression.';
    }
}

// Stats globales
$res = $conn->query("SELECT COUNT(*) AS n, AVG(note_sur_5) AS moy FROM avis")->fetch_assoc();
$total_avis = (int)$res['n'];
$moyenne    = $res['moy'] ? round($res['moy'], 1) : 0;
$nb_pos = (int)$conn->query("SELECT COUNT(*) AS n FROM avis WHERE note_sur_5 >= 4")->fetch_assoc()['n'];
$nb_neg = (int)$conn->query("SELECT COUNT(*) AS n FROM avis WHERE note_sur_5 <= 2")->fetch_assoc()['n'];

// Pagination
$total_pages = max(1, (int)ceil($total_avis / $par_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $par_page;

$stmt = $conn->prepare(
    "SELECT a.*, p.nom_produit, u.nom, u.prenom
     FROM avis a
     LEFT JOIN produit p ON a.id_produit = p.id_produit
     LEFT JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur
     ORDER BY a.date_avis DESC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param("ii", $par_page, $offset);
$stmt->execute();
$avis_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_titre = 'Avis';
$active = 'avis';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-star"></i> Modération des avis</h1>
    <button class="btn-excel" onclick="exportExcel('tbl-avis','avis')">
        <i class="fas fa-file-excel"></i> Exporter Excel
    </button>
</div>

<?php if ($message): ?><div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div><?php endif; ?>
<?php if ($erreur):  ?><div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur)  ?></div><?php endif; ?>

<!-- Statistiques -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-comments"></i></div>
        <div>
            <div class="stat-num"><?= number_format($total_avis) ?></div>
            <div class="stat-label">Avis au total</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-star"></i></div>
        <div>
            <div class="stat-num"><?= number_format($moyenne, 1) ?> / 5</div>
            <div class="stat-label">Note moyenne</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-thumbs-up"></i></div>
        <div>
            <div class="stat-num"><?= $nb_pos ?></div>
            <div class="stat-label">Avis positifs (4-5 ★)</div>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-thumbs-down"></i></div>
        <div>
            <div class="stat-num"><?= $nb_neg ?></div>
            <div class="stat-label">Avis négatifs (1-2 ★)</div>
        </div>
    </div>
</div>

<div class="table-box">
    <div class="table-header">
        <h3>Tous les avis</h3>
        <div class="table-tools">
            <div class="admin-search">
                <i class="fas fa-search"></i>
                <input type="text" id="search-avis" placeholder="Rechercher (client, produit, commentaire...)..." autocomplete="off">
                <button type="button" class="search-clear" id="search-clear" aria-label="Effacer">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="search-results-info" id="search-info"></div>

    <div class="table-wrap">
        <table class="admin-table" id="tbl-avis">
            <thead>
                <tr>
                    <th>Date</th><th>Produit</th><th>Client</th><th>Note</th>
                    <th>Commentaire</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($avis_list)): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--gris);padding:32px">Aucun avis pour le moment.</td></tr>
                <?php else: foreach ($avis_list as $a):
                    $initial = strtoupper(mb_substr($a['prenom'] ?? 'U', 0, 1));
                ?>
                <tr>
                    <td style="white-space:nowrap;font-size:13px"><?= e(date('d/m/Y', strtotime($a['date_avis']))) ?></td>
                    <td><?= e($a['nom_produit'] ?? '—') ?></td>
                    <td>
                        <span class="avatar"><?= e($initial) ?></span>
                        <?= e(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? '')) ?>
                    </td>
                    <td>
                        <span class="stars-y">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span <?= $i <= $a['note_sur_5'] ? '' : 'class="e"' ?>>★</span>
                            <?php endfor; ?>
                        </span>
                        <small style="color:var(--gris);margin-left:4px"><?= (int)$a['note_sur_5'] ?>/5</small>
                    </td>
                    <td style="max-width:340px;font-size:13px"><?= e($a['commentaire']) ?></td>
                    <td>
                        <a href="?supprimer=<?= (int)$a['id_avis'] ?>&p=<?= (int)$page ?>&csrf=<?= csrfToken() ?>"
                           class="btn-mini btn-del" title="Supprimer cet avis"
                           onclick="return confirm('Supprimer définitivement cet avis ?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_avis > 0): ?>
    <div class="pagination">
        <div class="pagination-info">
            Page <strong><?= $page ?></strong> / <strong><?= $total_pages ?></strong>
            — <?= count($avis_list) ?> avis sur <?= number_format($total_avis) ?>
        </div>
        <div class="pagination-controls">
            <a href="?p=<?= max(1, $page-1) ?>" class="page-link <?= $page<=1?'disabled':'' ?>"><i class="fas fa-chevron-left"></i></a>
            <?php
            $debut = max(1, $page - 2);
            $fin   = min($total_pages, $page + 2);
            if ($debut > 1) {
                echo '<a href="?p=1" class="page-link">1</a>';
                if ($debut > 2) echo '<span class="page-dots">…</span>';
            }
            for ($i = $debut; $i <= $fin; $i++) {
                $cls = $i == $page ? 'active' : '';
                echo "<a href=\"?p=$i\" class=\"page-link $cls\">$i</a>";
            }
            if ($fin < $total_pages) {
                if ($fin < $total_pages - 1) echo '<span class="page-dots">…</span>';
                echo "<a href=\"?p=$total_pages\" class=\"page-link\">$total_pages</a>";
            }
            ?>
            <a href="?p=<?= min($total_pages, $page+1) ?>" class="page-link <?= $page>=$total_pages?'disabled':'' ?>"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// ===== RECHERCHE =====
(function() {
    const input   = document.getElementById('search-avis');
    const clearB  = document.getElementById('search-clear');
    const info    = document.getElementById('search-info');
    const rows    = document.querySelectorAll('#tbl-avis tbody tr');
    function applyFilter() {
        const q = input.value.toLowerCase().trim();
        clearB.classList.toggle('visible', q.length > 0);
        let n = 0;
        rows.forEach(row => {
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

// ===== EXPORT EXCEL =====
function exportExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows  = table.querySelectorAll('tr');
    const csv   = [];
    rows.forEach(row => {
        if (row.classList.contains('hidden')) return;
        const cols = row.querySelectorAll('th,td');
        const line = [];
        cols.forEach(col => {
            if (col.querySelector('.btn-mini')) {
                line.push('');
            } else {
                line.push('"' + col.innerText.replace(/"/g,'""').replace(/\n/g,' ').trim() + '"');
            }
        });
        csv.push(line.join(';'));
    });
    const blob = new Blob(['\uFEFF' + csv.join('\n')], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename + '_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
