<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
exigerAdmin();

$message = '';
$erreur  = '';
$action  = $_GET['action'] ?? 'liste';
$id      = getId($_GET['id'] ?? 0);
$page    = max(1, (int)($_GET['p'] ?? 1));
$par_page = 10;
$offset  = ($page - 1) * $par_page;

// Données pour les selects
$categories = $conn->query("SELECT * FROM categories ORDER BY NOM ")->fetch_all(MYSQLI_ASSOC);
$marques    = $conn->query("SELECT * FROM marques ORDER BY nom_marque")->fetch_all(MYSQLI_ASSOC);
$fournis    = $conn->query("SELECT * FROM fournisseurs ORDER BY nom_fournisseur")->fetch_all(MYSQLI_ASSOC);
$types      = $conn->query("SELECT * FROM types_produits ORDER BY nom_type")->fetch_all(MYSQLI_ASSOC);

// SUPPRIMER
if ($action === 'supprimer' && $id && hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
    $stmt = $conn->prepare("DELETE FROM produit WHERE id_produit = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Produit supprimé.';
    } else {
        $erreur = 'Impossible de supprimer (peut-être utilisé dans des commandes).';
    }
    $action = 'liste';
}

// ENREGISTRER (ajout / modif)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $erreur = 'Session expirée.';
    } else {
        $nom_produit       = clean($_POST['nom_produit'] ?? '');
        $description       = clean($_POST['description'] ?? '');
        $id_categorie      = getId($_POST['id_categorie'] ?? 0);
        $id_type           = getId($_POST['id_type_produit'] ?? 0);
        $id_marque         = getId($_POST['id_marque'] ?? 0);
        $id_fournisseur    = getId($_POST['id_fournisseur'] ?? 0);
        $prix              = (float)($_POST['prix_unitaire_MRU'] ?? 0);
        $stock             = (int)($_POST['stock_disponible'] ?? 0);
        $seuil             = (int)($_POST['seuil_alerte_stock'] ?? 5);
        $sku               = clean($_POST['reference_SKU'] ?? '');
        $statut            = clean($_POST['statut'] ?? 'disponible');
        $rohs              = clean($_POST['conformite_rohs'] ?? 'oui');
        $image             = clean($_POST['image'] ?? 'default.jpg');

        if (!$nom_produit || $prix <= 0) {
            $erreur = 'Nom et prix sont obligatoires.';
        } else {
            if (!empty($_POST['id_produit'])) {
                $pid = getId($_POST['id_produit']);
                $stmt = $conn->prepare(
                    "UPDATE produit SET
                        nom_produit=?, description=?, id_categorie=?, id_type_produit=?,
                        id_marque=?, id_fournisseur=?, prix_unitaire_MRU=?,
                        stock_disponible=?, seuil_alerte_stock=?, reference_SKU=?,
                        statut=?, conformite_rohs=?, image=?
                     WHERE id_produit=?"
                );
                $stmt->bind_param("ssiiiidiissssi",
                    $nom_produit, $description, $id_categorie, $id_type,
                    $id_marque, $id_fournisseur, $prix,
                    $stock, $seuil, $sku, $statut, $rohs, $image, $pid
                );
                $stmt->execute();
                $message = 'Produit modifié.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO produit
                        (nom_produit, description, id_categorie, id_type_produit, id_marque,
                         id_fournisseur, prix_unitaire_MRU, stock_disponible, seuil_alerte_stock,
                         reference_SKU, statut, conformite_rohs, image)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("ssiiiidiissss",
                    $nom_produit, $description, $id_categorie, $id_type,
                    $id_marque, $id_fournisseur, $prix,
                    $stock, $seuil, $sku, $statut, $rohs, $image
                );
                $stmt->execute();
                $message = 'Produit ajouté.';
            }
            $action = 'liste';
        }
    }
}

// Produit à modifier
$prod_edit = null;
if ($action === 'modifier' && $id) {
    $stmt = $conn->prepare("SELECT * FROM produit WHERE id_produit = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $prod_edit = $stmt->get_result()->fetch_assoc();
}

// Total et pagination
$total = (int)$conn->query("SELECT COUNT(*) AS n FROM produit")->fetch_assoc()['n'];
$total_pages = max(1, (int)ceil($total / $par_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $par_page;

// Liste paginée
$stmt = $conn->prepare(
    "SELECT p.*, c.NOM, m.nom_marque
     FROM produit p
     LEFT JOIN categories c ON p.id_categorie = c.id_categorie
     LEFT JOIN marques m    ON p.id_marque    = m.id_marque
     ORDER BY p.id_produit DESC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param("ii", $par_page, $offset);
$stmt->execute();
$produits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Données pour l'EXPORT (toutes les lignes, pas seulement la page) ──
$tous_produits = $conn->query(
    "SELECT p.*, c.NOM, m.nom_marque, f.nom_fournisseur, t.nom_type
     FROM produit p
     LEFT JOIN categories c       ON p.id_categorie    = c.id_categorie
     LEFT JOIN marques m          ON p.id_marque       = m.id_marque
     LEFT JOIN fournisseurs f     ON p.id_fournisseur  = f.id_fournisseur
     LEFT JOIN types_produits t   ON p.id_type_produit = t.id_type
     ORDER BY p.id_produit"
)->fetch_all(MYSQLI_ASSOC);

// Export IMPORT-COMPATIBLE : exactement les colonnes que l'import attend
$export_import = [];
$export_import[] = ['nom_produit','description','prix_unitaire_MRU','stock_disponible',
                    'seuil_alerte_stock','reference_SKU','image','statut','conformite_rohs',
                    'id_categorie','id_marque','id_fournisseur','id_type_produit'];
foreach ($tous_produits as $p) {
    $export_import[] = [
        $p['nom_produit'], $p['description'], $p['prix_unitaire_MRU'], $p['stock_disponible'],
        $p['seuil_alerte_stock'], $p['reference_SKU'], $p['image'], $p['statut'],
        $p['conformite_rohs'], $p['id_categorie'], $p['id_marque'],
        $p['id_fournisseur'], $p['id_type_produit'],
    ];
}

// Export COMPLET : tout, avec ID et noms lisibles (pour archive / consultation)
$export_complet = [];
$export_complet[] = ['ID','Nom','Description','Catégorie','Marque','Fournisseur','Type',
                     'Prix MRU','Stock','Seuil alerte','SKU','Statut','RoHS'];
foreach ($tous_produits as $p) {
    $export_complet[] = [
        $p['id_produit'], $p['nom_produit'], $p['description'], $p['NOM'],
        $p['nom_marque'], $p['nom_fournisseur'], $p['nom_type'], $p['prix_unitaire_MRU'],
        $p['stock_disponible'], $p['seuil_alerte_stock'], $p['reference_SKU'],
        $p['statut'], $p['conformite_rohs'],
    ];
}

$page_titre = 'Produits';
$active = 'produits';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-box"></i> Produits (<?= number_format($total) ?>)</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn-excel" onclick="exportImport()">
            <i class="fas fa-file-csv"></i> Export import-compatible
        </button>
        <button class="btn-excel" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe" onclick="exportComplet()">
            <i class="fas fa-file-excel"></i> Export complet
        </button>
        <button id="btn-import" class="btn-excel" style="background:#fff7ed;color:#c2410c;border-color:#fed7aa">
            <i class="fas fa-file-import"></i> Importer CSV
        </button>
        <button id="btn-new-prod" class="btn-primary">
            <i class="fas fa-plus"></i> Nouveau produit
        </button>
    </div>
</div>

<?php if ($message): ?><div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div><?php endif; ?>
<?php if ($erreur):  ?><div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur)  ?></div><?php endif; ?>

<div class="table-box">
    <div class="table-header">
        <h3>Tous les produits</h3>
        <div class="admin-search">
            <i class="fas fa-search"></i>
            <input type="text" id="search-prod" placeholder="Rechercher un produit..." autocomplete="off">
        </div>
    </div>
    <div class="table-wrap">
        <table class="admin-table" id="tbl-produits">
            <thead><tr><th>ID</th><th>Image</th><th>Nom</th><th>Catégorie</th><th>Marque</th><th>Prix</th><th>Stock</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($produits)): ?>
                    <tr><td colspan="9" style="text-align:center;color:var(--gris);padding:32px">Aucun produit pour le moment.</td></tr>
                <?php else: foreach ($produits as $p): ?>
                <tr>
                    <td>#<?= (int)$p['id_produit'] ?></td>
                    <td>
                        <?php if (!empty($p['image'])): ?>
                            <img src="../images/<?= e($p['image']) ?>" alt="" style="width:38px;height:38px;object-fit:contain;border:1px solid var(--border);border-radius:6px;padding:2px;background:white">
                        <?php endif; ?>
                    </td>
                    <td><?= e($p['nom_produit']) ?></td>
                    <td><?= e($p['NOM'] ?? '—') ?></td>
                    <td><?= e($p['nom_marque'] ?? '—') ?></td>
                    <td><strong><?= number_format($p['prix_unitaire_MRU'], 2) ?></strong></td>
                    <td>
                        <span class="badge <?= $p['stock_disponible'] <= $p['seuil_alerte_stock'] ? 'badge-inactif' : 'badge-actif' ?>">
                            <?= (int)$p['stock_disponible'] ?>
                        </span>
                    </td>
                    <td><span class="badge <?= $p['statut']==='disponible' ? 'badge-actif' : 'badge-inactif' ?>"><?= e($p['statut']) ?></span></td>
                    <td>
                        <a href="?action=modifier&id=<?= (int)$p['id_produit'] ?>&p=<?= (int)$page ?>" class="btn-mini btn-edit"><i class="fas fa-edit"></i></a>
                        <a href="?action=supprimer&id=<?= (int)$p['id_produit'] ?>&p=<?= (int)$page ?>&csrf=<?= csrfToken() ?>"
                           class="btn-mini btn-del"
                           onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination">
        <div class="pagination-info">
            Page <strong><?= $page ?></strong> / <strong><?= $total_pages ?></strong>
            — <?= count($produits) ?> produit(s) affiché(s) sur <?= number_format($total) ?>
        </div>
        <div class="pagination-controls">
            <a href="?p=<?= max(1, $page-1) ?>" class="page-link <?= $page<=1?'disabled':'' ?>"><i class="fas fa-chevron-left"></i></a>
            <?php
            // Affichage intelligent : 1 … x-1 x x+1 … N
            $debut = max(1, $page - 2);
            $fin   = min($total_pages, $page + 2);
            if ($debut > 1) {
                echo '<a href="?p=1" class="page-link">1</a>';
                if ($debut > 2) echo '<span style="color:var(--gris);padding:0 4px">…</span>';
            }
            for ($i = $debut; $i <= $fin; $i++) {
                $cls = $i == $page ? 'active' : '';
                echo "<a href=\"?p=$i\" class=\"page-link $cls\">$i</a>";
            }
            if ($fin < $total_pages) {
                if ($fin < $total_pages - 1) echo '<span style="color:var(--gris);padding:0 4px">…</span>';
                echo "<a href=\"?p=$total_pages\" class=\"page-link\">$total_pages</a>";
            }
            ?>
            <a href="?p=<?= min($total_pages, $page+1) ?>" class="page-link <?= $page>=$total_pages?'disabled':'' ?>"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
</div>

<!-- PANNEAU COULISSANT -->
<div class="slide-overlay" id="slide-overlay"></div>
<aside class="slide-panel" id="slide-panel">
    <div class="slide-header">
        <h3 id="slide-title"><?= $prod_edit ? '✏️ Modifier le produit' : '➕ Nouveau produit' ?></h3>
        <button class="slide-close" id="slide-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="slide-body">
        <form method="POST">
            <?= csrfField() ?>
            <?php if ($prod_edit): ?>
                <input type="hidden" name="id_produit" value="<?= (int)$prod_edit['id_produit'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nom *</label>
                <input type="text" name="nom_produit" required maxlength="100"
                       value="<?= e($prod_edit['nom_produit'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2" maxlength="200"><?= e($prod_edit['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Catégorie</label>
                <select name="id_categorie">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id_categorie'] ?>"
                            <?= ($prod_edit['id_categorie'] ?? 0) == $c['id_categorie'] ? 'selected' : '' ?>>
                            <?= e($c['NOM']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="id_type_produit">
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int)$t['id_type'] ?>"
                            <?= ($prod_edit['id_type_produit'] ?? 0) == $t['id_type'] ? 'selected' : '' ?>>
                            <?= e($t['nom_type']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Marque</label>
                <select name="id_marque">
                    <?php foreach ($marques as $m): ?>
                        <option value="<?= (int)$m['id_marque'] ?>"
                            <?= ($prod_edit['id_marque'] ?? 0) == $m['id_marque'] ? 'selected' : '' ?>>
                            <?= e($m['nom_marque']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fournisseur</label>
                <select name="id_fournisseur">
                    <?php foreach ($fournis as $f): ?>
                        <option value="<?= (int)$f['id_fournisseur'] ?>"
                            <?= ($prod_edit['id_fournisseur'] ?? 0) == $f['id_fournisseur'] ? 'selected' : '' ?>>
                            <?= e($f['nom_fournisseur']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="form-group">
                    <label>Prix (MRU) *</label>
                    <input type="number" step="0.01" name="prix_unitaire_MRU" required
                           value="<?= e($prod_edit['prix_unitaire_MRU'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock_disponible"
                           value="<?= (int)($prod_edit['stock_disponible'] ?? 0) ?>">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="form-group">
                    <label>Seuil alerte</label>
                    <input type="number" name="seuil_alerte_stock"
                           value="<?= (int)($prod_edit['seuil_alerte_stock'] ?? 5) ?>">
                </div>
                <div class="form-group">
                    <label>SKU</label>
                    <input type="text" name="reference_SKU" maxlength="50"
                           value="<?= e($prod_edit['reference_SKU'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Image (nom du fichier dans /images)</label>
                <input type="text" name="image" maxlength="100"
                       value="<?= e($prod_edit['image'] ?? 'default.jpg') ?>">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut">
                        <option value="disponible"   <?= ($prod_edit['statut'] ?? '')==='disponible' ? 'selected':'' ?>>Disponible</option>
                        <option value="indisponible" <?= ($prod_edit['statut'] ?? '')==='indisponible' ? 'selected':'' ?>>Indisponible</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>RoHS</label>
                    <select name="conformite_rohs">
                        <option value="oui" <?= ($prod_edit['conformite_rohs'] ?? '')==='oui' ? 'selected':'' ?>>Oui</option>
                        <option value="non" <?= ($prod_edit['conformite_rohs'] ?? '')==='non' ? 'selected':'' ?>>Non</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:12px;margin-top:6px">
                <i class="fas fa-save"></i> <?= $prod_edit ? 'Enregistrer les modifications' : 'Ajouter le produit' ?>
            </button>
        </form>
    </div>
</aside>

<!-- ===== PANNEAU IMPORT CSV ===== -->
<aside class="slide-panel" id="import-panel">
    <div class="slide-header">
        <h3><i class="fas fa-file-import" style="color:#c2410c"></i> Importer des produits (CSV)</h3>
        <button class="slide-close" id="import-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="slide-body">
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px;font-size:13px;margin-bottom:16px">
            <strong>Format attendu :</strong> un fichier <code>.csv</code> séparé par des virgules,
            avec une ligne d'en-tête.<br><br>
            <strong>Colonnes obligatoires :</strong> <code>nom_produit</code>, <code>prix_unitaire_MRU</code><br>
            <strong>Colonnes optionnelles :</strong> <code>description</code>, <code>reference_SKU</code>,
            <code>image</code>, <code>statut</code>, <code>conformite_rohs</code>,
            <code>stock_disponible</code>, <code>seuil_alerte_stock</code>,
            <code>id_categorie</code>, <code>id_marque</code>, <code>id_fournisseur</code>,
            <code>id_type_produit</code>
        </div>

        <div class="form-group">
            <label>Fichier CSV</label>
            <label class="upload-zone" id="csv-zone" for="csv-input"
                   style="display:block;border:2px dashed var(--border);border-radius:10px;padding:20px;text-align:center;cursor:pointer">
                <i class="fas fa-cloud-upload-alt" style="font-size:26px;color:#c2410c"></i>
                <div id="csv-text" style="font-size:13px;margin-top:6px">
                    Cliquez pour choisir un fichier .csv
                </div>
            </label>
            <input type="file" id="csv-input" accept=".csv" style="display:none">
        </div>

        <button id="csv-submit" class="btn-primary" style="width:100%;justify-content:center;padding:12px"
                disabled>
            <i class="fas fa-upload"></i> Lancer l'import
        </button>

        <div id="import-result" style="margin-top:16px;display:none"></div>
    </div>
</aside>

<script>
// ===== PANNEAU COULISSANT (formulaire produit) =====
const panel = document.getElementById('slide-panel');
const overlay = document.getElementById('slide-overlay');
const btnNew = document.getElementById('btn-new-prod');
const btnClose = document.getElementById('slide-close');

const importPanel = document.getElementById('import-panel');
const btnImport   = document.getElementById('btn-import');
const importClose = document.getElementById('import-close');

function openPanel() {
    panel.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function openImport() {
    importPanel.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closePanel() {
    panel.classList.remove('open');
    importPanel.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    if (window.location.search.includes('action=modifier')) {
        window.location.href = 'produits.php?p=<?= (int)$page ?>';
    }
}

btnNew.addEventListener('click', openPanel);
btnClose.addEventListener('click', closePanel);
btnImport.addEventListener('click', openImport);
importClose.addEventListener('click', closePanel);
overlay.addEventListener('click', closePanel);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePanel(); });

<?php if ($prod_edit || $erreur): ?>
window.addEventListener('DOMContentLoaded', openPanel);
<?php endif; ?>

// ===== RECHERCHE INSTANTANÉE =====
const searchInput = document.getElementById('search-prod');
const rows = document.querySelectorAll('#tbl-produits tbody tr');
searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    rows.forEach(row => {
        const txt = row.textContent.toLowerCase();
        row.style.display = !q || txt.includes(q) ? '' : 'none';
    });
});

// ===== IMPORT CSV (AJAX) =====
(function() {
    const input  = document.getElementById('csv-input');
    const zone   = document.getElementById('csv-zone');
    const txt    = document.getElementById('csv-text');
    const submit = document.getElementById('csv-submit');
    const result = document.getElementById('import-result');
    let fichier  = null;

    input.addEventListener('change', () => {
        if (input.files.length) {
            fichier = input.files[0];
            txt.innerHTML = '<i class="fas fa-check" style="color:#16a34a"></i> ' + fichier.name;
            zone.style.borderColor = '#16a34a';
            submit.disabled = false;
        }
    });

    submit.addEventListener('click', () => {
        if (!fichier) return;
        submit.disabled = true;
        submit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Import en cours...';
        result.style.display = 'none';

        const fd = new FormData();
        fd.append('csv_file', fichier);
        fd.append('csrf_token', '<?= csrfToken() ?>');

        fetch('import_produits.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                submit.innerHTML = '<i class="fas fa-upload"></i> Lancer l\'import';
                submit.disabled = false;
                result.style.display = 'block';
                if (data.ok) {
                    let html = '<div class="alerte succes"><i class="fas fa-check-circle"></i> '
                             + data.inseres + ' produit(s) importé(s) sur ' + data.total + '.</div>';
                    if (data.cols_ignorees && data.cols_ignorees.length) {
                        html += '<div style="font-size:12px;color:#92400e;margin-top:6px">'
                              + 'Colonnes ignorées : ' + data.cols_ignorees.join(', ') + '</div>';
                    }
                    html += '<button onclick="location.reload()" class="btn-primary" '
                          + 'style="width:100%;justify-content:center;margin-top:10px;padding:10px">'
                          + '<i class="fas fa-sync"></i> Rafraîchir la liste</button>';
                    result.innerHTML = html;
                } else {
                    result.innerHTML = '<div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> '
                                     + (data.error || 'Erreur inconnue') + '</div>';
                }
            })
            .catch(() => {
                submit.innerHTML = '<i class="fas fa-upload"></i> Lancer l\'import';
                submit.disabled = false;
                result.style.display = 'block';
                result.innerHTML = '<div class="alerte erreur">Erreur réseau pendant l\'import.</div>';
            });
    });
})();

// ===== EXPORT CSV =====
// Les données viennent du PHP : exactes, propres, complètes (toutes les pages).
const DATA_IMPORT  = <?= json_encode($export_import, JSON_UNESCAPED_UNICODE) ?>;
const DATA_COMPLET = <?= json_encode($export_complet, JSON_UNESCAPED_UNICODE) ?>;

// Construit et télécharge un CSV à partir d'un tableau de lignes
function telechargerCSV(lignes, nomFichier, separateur) {
    const csv = lignes.map(ligne =>
        ligne.map(cell => {
            const v = (cell === null || cell === undefined) ? '' : String(cell);
            // On entoure de guillemets et on double les guillemets internes
            return '"' + v.replace(/"/g, '""') + '"';
        }).join(separateur)
    ).join('\r\n');

    const blob = new Blob(['\uFEFF' + csv], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = nomFichier + '_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}

// Export RÉIMPORTABLE : séparateur virgule, colonnes = celles de l'import
function exportImport() {
    telechargerCSV(DATA_IMPORT, 'produits_import', ',');
}
// Export COMPLET : pour archive/consultation (avec ID, noms lisibles)
function exportComplet() {
    telechargerCSV(DATA_COMPLET, 'produits_complet', ',');
}
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>

