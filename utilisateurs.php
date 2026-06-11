<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
exigerAdmin();

$message = ''; $erreur = '';
$id      = getId($_GET['id'] ?? 0);
$action  = $_GET['action'] ?? '';
$page    = max(1, (int)($_GET['p'] ?? 1));
$par_page = 10;
$csrf_ok = hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '');

if ($id && $id === (int)$_SESSION['id_utilisateur'] && $action) {
    $erreur = 'Vous ne pouvez pas modifier votre propre compte ici. Utilisez « Mon profil ».';
} elseif ($id && $csrf_ok) {
    if ($action === 'activer') {
        $s = $conn->prepare("UPDATE utilisateurs SET statut='actif' WHERE id_utilisateur=?");
        $s->bind_param("i", $id); $s->execute();
        $message = 'Utilisateur activé.';
    } elseif ($action === 'desactiver') {
        $s = $conn->prepare("UPDATE utilisateurs SET statut='inactif' WHERE id_utilisateur=?");
        $s->bind_param("i", $id); $s->execute();
        $message = 'Utilisateur désactivé.';
    } elseif ($action === 'supprimer') {
        $s = $conn->prepare("DELETE FROM utilisateurs WHERE id_utilisateur=?");
        $s->bind_param("i", $id);
        if ($s->execute()) $message = 'Utilisateur supprimé.';
        else $erreur = 'Suppression impossible (commandes liées).';
    } elseif ($action === 'promouvoir') {
        $s = $conn->prepare("UPDATE utilisateurs SET role='admin' WHERE id_utilisateur=?");
        $s->bind_param("i", $id); $s->execute();
        $message = 'Promu admin.';
    } elseif ($action === 'retrograder') {
        $s = $conn->prepare("UPDATE utilisateurs SET role='client' WHERE id_utilisateur=?");
        $s->bind_param("i", $id); $s->execute();
        $message = 'Rétrogradé en client.';
    }
}

$total = (int)$conn->query("SELECT COUNT(*) AS n FROM utilisateurs")->fetch_assoc()['n'];
$total_pages = max(1, (int)ceil($total / $par_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $par_page;

$stmt = $conn->prepare("SELECT * FROM utilisateurs ORDER BY date_inscription DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $par_page, $offset);
$stmt->execute();
$utilisateurs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_titre = 'Utilisateurs';
$active = 'utilisateurs';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-users"></i> Utilisateurs (<?= number_format($total) ?>)</h1>
    <button class="btn-excel" onclick="exportExcel('tbl-users','utilisateurs')">
        <i class="fas fa-file-excel"></i> Exporter Excel
    </button>
</div>

<?php if ($message): ?><div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div><?php endif; ?>
<?php if ($erreur):  ?><div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur)  ?></div><?php endif; ?>

<div class="table-box">
    <div class="table-header">
        <h3>Tous les utilisateurs</h3>
        <div class="table-tools">
            <div class="admin-search">
                <i class="fas fa-search"></i>
                <input type="text" id="search-users" placeholder="Rechercher (nom, email...)..." autocomplete="off">
                <button type="button" class="search-clear" id="search-clear" aria-label="Effacer">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="search-results-info" id="search-info"></div>

    <div class="table-wrap">
        <table class="admin-table" id="tbl-users">
            <thead>
                <tr>
                    <th>ID</th><th>Nom complet</th><th>Email</th><th>Téléphone</th>
                    <th>Rôle</th><th>Statut</th><th>Inscrit le</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($utilisateurs)): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--gris);padding:32px">Aucun utilisateur.</td></tr>
                <?php else: foreach ($utilisateurs as $u):
                    $is_self = $u['id_utilisateur'] == $_SESSION['id_utilisateur'];
                    $initial = strtoupper(mb_substr($u['prenom'] ?? 'U', 0, 1));
                ?>
                <tr <?= $is_self?'style="background:var(--bleu-light)"':'' ?>>
                    <td>#<?= (int)$u['id_utilisateur'] ?></td>
                    <td>
                        <span class="avatar"><?= e($initial) ?></span>
                        <strong><?= e(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) ?></strong>
                        <?= $is_self?'<small style="color:var(--bleu);margin-left:4px">(vous)</small>':'' ?>
                    </td>
                    <td style="font-size:13px"><?= e($u['email']) ?></td>
                    <td style="font-size:13px"><?= e($u['telephone'] ?? '—') ?></td>
                    <td><span class="badge <?= $u['role']==='admin'?'badge-pct':'badge-fixe' ?>"><?= e($u['role']) ?></span></td>
                    <td><span class="badge badge-<?= $u['statut']==='actif'?'actif':'inactif' ?>"><?= e($u['statut']) ?></span></td>
                    <td style="font-size:13px"><?= $u['date_inscription'] ? e(date('d/m/Y', strtotime($u['date_inscription']))) : '—' ?></td>
                    <td style="white-space:nowrap">
                        <?php if (!$is_self): ?>
                            <?php if ($u['statut']==='actif'): ?>
                                <a href="?action=desactiver&id=<?= (int)$u['id_utilisateur'] ?>&p=<?= (int)$page ?>&csrf=<?= csrfToken() ?>"
                                   class="btn-mini btn-del" title="Désactiver"
                                   onclick="return confirm('Désactiver ?')"><i class="fas fa-ban"></i></a>
                            <?php else: ?>
                                <a href="?action=activer&id=<?= (int)$u['id_utilisateur'] ?>&p=<?= (int)$page ?>&csrf=<?= csrfToken() ?>"
                                   class="btn-mini btn-view" title="Activer"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                            <?php if ($u['role']!=='admin'): ?>
                                <a href="?action=promouvoir&id=<?= (int)$u['id_utilisateur'] ?>&p=<?= (int)$page ?>&csrf=<?= csrfToken() ?>"
                                   class="btn-mini btn-edit" title="Promouvoir admin"
                                   onclick="return confirm('Promouvoir en admin ?')"><i class="fas fa-user-shield"></i></a>
                            <?php else: ?>
                                <a href="?action=retrograder&id=<?= (int)$u['id_utilisateur'] ?>&p=<?= (int)$page ?>&csrf=<?= csrfToken() ?>"
                                   class="btn-mini btn-edit" title="Retirer admin"
                                   onclick="return confirm('Retirer le rôle admin ?')"><i class="fas fa-user-minus"></i></a>
                            <?php endif; ?>
                            <a href="?action=supprimer&id=<?= (int)$u['id_utilisateur'] ?>&p=<?= (int)$page ?>&csrf=<?= csrfToken() ?>"
                               class="btn-mini btn-del" title="Supprimer"
                               onclick="return confirm('Supprimer définitivement ?')"><i class="fas fa-trash"></i></a>
                        <?php else: ?>
                            <small style="color:var(--gris)">—</small>
                        <?php endif; ?>
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
            sur <strong><?= number_format($total) ?></strong> utilisateurs
        </div>
        <nav class="pagination" aria-label="Pagination">
            <a href="?p=<?= max(1,$page-1) ?>" class="page-fleche <?= $page<=1?'off':'' ?>"><i class="fas fa-chevron-left"></i></a>
            <?php
            $debut = max(1, $page - 2);
            $fin   = min($total_pages, $page + 2);
            if ($debut > 1) {
                echo '<a href="?p=1">1</a>';
                if ($debut > 2) echo '<span class="page-points">…</span>';
            }
            for ($i = $debut; $i <= $fin; $i++) {
                if ($i == $page) echo '<span class="page-actuelle">'.$i.'</span>';
                else echo '<a href="?p='.$i.'">'.$i.'</a>';
            }
            if ($fin < $total_pages) {
                if ($fin < $total_pages - 1) echo '<span class="page-points">…</span>';
                echo '<a href="?p='.$total_pages.'">'.$total_pages.'</a>';
            }
            ?>
            <a href="?p=<?= min($total_pages,$page+1) ?>" class="page-fleche <?= $page>=$total_pages?'off':'' ?>"><i class="fas fa-chevron-right"></i></a>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
// RECHERCHE
(function() {
    const input  = document.getElementById('search-users');
    const clearB = document.getElementById('search-clear');
    const info   = document.getElementById('search-info');
    const rows   = document.querySelectorAll('#tbl-users tbody tr');
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

// EXPORT EXCEL
function exportExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows  = table.querySelectorAll('tr');
    const csv   = [];
    rows.forEach(row => {
        if (row.classList.contains('hidden')) return;
        const cols = row.querySelectorAll('th,td');
        const line = [];
        cols.forEach(col => {
            if (col.querySelector('.btn-mini')) line.push('');
            else line.push('"' + col.innerText.replace(/"/g,'""').replace(/\n/g,' ').trim() + '"');
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


