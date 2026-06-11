<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
require_once __DIR__ . '/../categories.php';
exigerAdmin();

$message = ''; $erreur = '';
$action  = $_GET['action'] ?? 'liste';
$id      = getId($_GET['id'] ?? 0);
$page    = max(1, (int)($_GET['p'] ?? 1));
$par_page = 10;

$familles = $conn->query("SELECT * FROM familles ORDER BY nom_famille")->fetch_all(MYSQLI_ASSOC);

// SUPPRIMER
if ($action === 'supprimer' && $id && hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id_categorie = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $message = 'Catégorie supprimée.';
    else $erreur = 'Suppression impossible (catégorie utilisée).';
    $action = 'liste';
}

// FUSIONNER LES CATEGORIES EN DOUBLE
if ($action === 'fusionner_doublons' && hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
    $nb = fusionnerCategoriesDoublons($conn);
    $message = $nb > 0
        ? $nb . ' catégorie(s) en double fusionnée(s).'
        : 'Aucune catégorie en double trouvée.';
    $action = 'liste';
}

$categories_doublons = getCategoriesDoublons($conn);

// ENREGISTRER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_cat'])) {
    if (!csrfVerify()) {
        $erreur = 'Session expirée.';
    } else {
        $nom    = clean($_POST['NOM'] ?? '');
        $desc   = clean($_POST['description'] ?? '');
        $id_fam = getId($_POST['id_famille'] ?? 0);
        $statut = clean($_POST['statut'] ?? 'actif');

        if (!$nom || !$id_fam) {
            $erreur = 'Nom et famille sont obligatoires.';
        } else {
            if (!empty($_POST['id_categorie'])) {
                $cid = getId($_POST['id_categorie']);
                $stmt = $conn->prepare("UPDATE categories SET NOM =?, description=?, id_famille=?, statut=? WHERE id_categorie=?");
                $stmt->bind_param("ssisi", $nom, $desc, $id_fam, $statut, $cid);
            } else {
                $max = (int)$conn->query("SELECT COALESCE(MAX(id_categorie),0)+1 AS nid FROM categories")->fetch_assoc()['nid'];
                $stmt = $conn->prepare("INSERT INTO categories (id_categorie, id_famille, NOM , description, statut) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $max, $id_fam, $nom, $desc, $statut);
            }
            if ($stmt->execute()) $message = 'Catégorie enregistrée.';
            else $erreur = 'Erreur SQL.';
            $action = 'liste';
        }
    }
}

$cat_edit = null;
if ($action === 'modifier' && $id) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id_categorie = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $cat_edit = $stmt->get_result()->fetch_assoc();
}

// Total + pagination
$total = (int)$conn->query("SELECT COUNT(*) AS n FROM categories")->fetch_assoc()['n'];
$total_pages = max(1, (int)ceil($total / $par_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $par_page;

$stmt = $conn->prepare(
    "SELECT c.*, f.nom_famille,
            (SELECT COUNT(*) FROM produit p WHERE p.id_categorie = c.id_categorie) AS nb_produits
     FROM categories c
     LEFT JOIN familles f ON c.id_famille = f.id_famille
     ORDER BY c.NOM
     LIMIT ? OFFSET ?"
);
$stmt->bind_param("ii", $par_page, $offset);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Données pour l'EXPORT (toutes les catégories) ──
$toutes_cats = $conn->query(
    "SELECT c.*, f.nom_famille FROM categories c
     LEFT JOIN familles f ON c.id_famille = f.id_famille
     ORDER BY c.id_categorie"
)->fetch_all(MYSQLI_ASSOC);

// Export IMPORT-COMPATIBLE
$export_import = [];
$export_import[] = ['NOM','description','id_famille','statut'];
foreach ($toutes_cats as $c) {
    $export_import[] = [$c['NOM'], $c['description'], $c['id_famille'], $c['statut']];
}
// Export COMPLET
$export_complet = [];
$export_complet[] = ['ID','Nom','Famille','Description','Statut'];
foreach ($toutes_cats as $c) {
    $export_complet[] = [$c['id_categorie'], $c['NOM'], $c['nom_famille'],
                         $c['description'], $c['statut']];
}

$page_titre = 'Catégories';
$active = 'categories';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-tags"></i> Catégories (<?= number_format($total) ?>)</h1>
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
        <button id="btn-new-cat" class="btn-primary">
            <i class="fas fa-plus"></i> Nouvelle catégorie
        </button>
    </div>
</div>

<?php if ($message): ?><div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div><?php endif; ?>
<?php if ($erreur):  ?><div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur)  ?></div><?php endif; ?>

<?php if (!empty($categories_doublons)): ?>
<div class="alerte erreur" style="align-items:flex-start;flex-direction:column;gap:8px">
    <div><i class="fas fa-exclamation-triangle"></i> <strong>Catégories en double détectées :</strong></div>
    <ul style="margin:0;padding-left:24px">
        <?php foreach ($categories_doublons as $nom => $ids): ?>
            <li><?= e($nom) ?> (IDs : <?= implode(', ', array_map(fn($i) => '#' . $i, $ids)) ?>)</li>
        <?php endforeach; ?>
    </ul>
    <p style="font-size:13px;margin:0">
        La fusion conserve l'ID le plus petit pour chaque nom, réassigne les produits/types/promotions
        liés aux doublons, puis supprime les catégories devenues inutiles.
    </p>
    <a href="?action=fusionner_doublons&csrf=<?= csrfToken() ?>" class="btn-primary"
       onclick="return confirm('Fusionner les catégories en double ?')">
        <i class="fas fa-code-merge"></i> Fusionner les doublons
    </a>
</div>
<?php endif; ?>

<div class="table-box">
    <div class="table-header">
        <h3>Toutes les catégories</h3>
        <div class="table-tools">
            <div class="admin-search">
                <i class="fas fa-search"></i>
                <input type="text" id="search-cat" placeholder="Rechercher une catégorie..." autocomplete="off">
                <button type="button" class="search-clear" id="search-clear"><i class="fas fa-times-circle"></i></button>
            </div>
        </div>
    </div>
    <div class="search-results-info" id="search-info"></div>

    <div class="table-wrap">
        <table class="admin-table" id="tbl-categories">
            <thead>
                <tr><th>ID</th><th>Nom</th><th>Famille</th><th>Description</th><th>Produits</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--gris);padding:32px">Aucune catégorie.</td></tr>
                <?php else: foreach ($categories as $c): ?>
                <tr>
                    <td>#<?= (int)$c['id_categorie'] ?></td>
                    <td><strong><?= e($c['NOM']) ?></strong></td>
                    <td><?= e($c['nom_famille'] ?? '—') ?></td>
                    <td style="max-width:280px;color:var(--gris);font-size:13px"><?= e($c['description']) ?></td>
                    <td><span class="badge badge-fixe"><?= (int)$c['nb_produits'] ?></span></td>
                    <td><span class="badge badge-<?= $c['statut']==='actif'?'actif':'inactif' ?>"><?= e($c['statut']) ?></span></td>
                    <td style="white-space:nowrap">
                        <a href="?action=modifier&id=<?= (int)$c['id_categorie'] ?>&p=<?= (int)$page ?>" class="btn-mini btn-edit"><i class="fas fa-edit"></i></a>
                        <a href="?action=supprimer&id=<?= (int)$c['id_categorie'] ?>&p=<?= (int)$page ?>&csrf=<?= csrfToken() ?>"
                           class="btn-mini btn-del" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total > 0): ?>
    <div class="pagination-bar">
        <div class="pagination-info">
            Affichage <strong><?= $offset+1 ?></strong>–<strong><?= min($offset+$par_page,$total) ?></strong>
            sur <strong><?= number_format($total) ?></strong> catégories
        </div>
        <nav class="pagination">
            <a href="?p=<?= max(1,$page-1) ?>" class="page-fleche <?= $page<=1?'off':'' ?>"><i class="fas fa-chevron-left"></i></a>
            <?php
            $debut = max(1, $page-2); $fin = min($total_pages, $page+2);
            if ($debut > 1) { echo '<a href="?p=1">1</a>'; if ($debut>2) echo '<span class="page-points">…</span>'; }
            for ($i=$debut;$i<=$fin;$i++) {
                if ($i==$page) echo '<span class="page-actuelle">'.$i.'</span>';
                else echo '<a href="?p='.$i.'">'.$i.'</a>';
            }
            if ($fin < $total_pages) { if ($fin<$total_pages-1) echo '<span class="page-points">…</span>'; echo '<a href="?p='.$total_pages.'">'.$total_pages.'</a>'; }
            ?>
            <a href="?p=<?= min($total_pages,$page+1) ?>" class="page-fleche <?= $page>=$total_pages?'off':'' ?>"><i class="fas fa-chevron-right"></i></a>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- ===== PANNEAU FORMULAIRE ===== -->
<div class="slide-overlay" id="slide-overlay"></div>
<aside class="slide-panel" id="slide-panel">
    <div class="slide-header">
        <h3><?= $cat_edit ? '✏️ Modifier la catégorie' : '➕ Nouvelle catégorie' ?></h3>
        <button class="slide-close" id="slide-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="slide-body">
        <form method="POST">
            <?= csrfField() ?>
            <?php if ($cat_edit): ?>
                <input type="hidden" name="id_categorie" value="<?= (int)$cat_edit['id_categorie'] ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Nom *</label>
                <input type="text" name="NOM" required maxlength="72" value="<?= e($cat_edit['NOM'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Famille *</label>
                <select name="id_famille" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($familles as $f): ?>
                        <option value="<?= (int)$f['id_famille'] ?>" <?= ($cat_edit['id_famille'] ?? 0)==$f['id_famille']?'selected':'' ?>>
                            <?= e($f['nom_famille']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" maxlength="180"><?= e($cat_edit['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Statut</label>
                <select name="statut">
                    <option value="actif"   <?= ($cat_edit['statut'] ?? 'actif')==='actif'?'selected':'' ?>>Actif</option>
                    <option value="inactif" <?= ($cat_edit['statut'] ?? '')==='inactif'?'selected':'' ?>>Inactif</option>
                </select>
            </div>
            <button type="submit" name="enregistrer_cat" class="btn-primary" style="width:100%;justify-content:center;padding:12px;margin-top:6px">
                <i class="fas fa-save"></i> <?= $cat_edit ? 'Enregistrer' : 'Ajouter la catégorie' ?>
            </button>
        </form>
    </div>
</aside>

<!-- ===== PANNEAU IMPORT CSV ===== -->
<aside class="slide-panel" id="import-panel">
    <div class="slide-header">
        <h3><i class="fas fa-file-import" style="color:#c2410c"></i> Importer des catégories (CSV)</h3>
        <button class="slide-close" id="import-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="slide-body">
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px;font-size:13px;margin-bottom:16px">
            <strong>Format :</strong> fichier <code>.csv</code> séparé par des virgules, avec ligne d'en-tête.<br><br>
            <strong>Colonne obligatoire :</strong> <code>NOM</code><br>
            <strong>Optionnelles :</strong> <code>description</code>, <code>id_famille</code>, <code>statut</code>
        </div>
        <div class="form-group">
            <label>Fichier CSV</label>
            <label class="upload-zone" id="csv-zone" for="csv-input"
                   style="display:block;border:2px dashed var(--border);border-radius:10px;padding:20px;text-align:center;cursor:pointer">
                <i class="fas fa-cloud-upload-alt" style="font-size:26px;color:#c2410c"></i>
                <div id="csv-text" style="font-size:13px;margin-top:6px">Cliquez pour choisir un fichier .csv</div>
            </label>
            <input type="file" id="csv-input" accept=".csv" style="display:none">
        </div>
        <button id="csv-submit" class="btn-primary" style="width:100%;justify-content:center;padding:12px" disabled>
            <i class="fas fa-upload"></i> Lancer l'import
        </button>
        <div id="import-result" style="margin-top:16px;display:none"></div>
    </div>
</aside>

<script>
const panel       = document.getElementById('slide-panel');
const overlay     = document.getElementById('slide-overlay');
const btnNew      = document.getElementById('btn-new-cat');
const btnClose    = document.getElementById('slide-close');
const importPanel = document.getElementById('import-panel');
const btnImport   = document.getElementById('btn-import');
const importClose = document.getElementById('import-close');

function openPanel()  { panel.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow='hidden'; }
function openImport() { importPanel.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow='hidden'; }
function closePanel() {
    panel.classList.remove('open'); importPanel.classList.remove('open');
    overlay.classList.remove('open'); document.body.style.overflow='';
    if (window.location.search.includes('action=modifier')) window.location.href = 'catergories.php?p=<?= (int)$page ?>';
}
btnNew.addEventListener('click', openPanel);
btnClose.addEventListener('click', closePanel);
btnImport.addEventListener('click', openImport);
importClose.addEventListener('click', closePanel);
overlay.addEventListener('click', closePanel);
document.addEventListener('keydown', e => { if (e.key==='Escape') closePanel(); });
<?php if ($cat_edit || $erreur): ?>
window.addEventListener('DOMContentLoaded', openPanel);
<?php endif; ?>

// RECHERCHE
(function() {
    const input=document.getElementById('search-cat'), clearB=document.getElementById('search-clear'),
          info=document.getElementById('search-info'), rows=document.querySelectorAll('#tbl-categories tbody tr');
    function f() {
        const q=input.value.toLowerCase().trim();
        clearB.classList.toggle('visible', q.length>0);
        let n=0;
        rows.forEach(r=>{ if(r.querySelector('td[colspan]'))return;
            const s=!q||r.textContent.toLowerCase().includes(q); r.classList.toggle('hidden',!s); if(s)n++; });
        if(q){ info.textContent=`🔎 ${n} résultat(s) pour "${input.value}"`; info.classList.add('visible'); }
        else info.classList.remove('visible');
    }
    input.addEventListener('input',f);
    clearB.addEventListener('click',()=>{input.value='';f();input.focus();});
})();

// IMPORT CSV
(function() {
    const input=document.getElementById('csv-input'), zone=document.getElementById('csv-zone'),
          txt=document.getElementById('csv-text'), submit=document.getElementById('csv-submit'),
          result=document.getElementById('import-result');
    let fichier=null;
    input.addEventListener('change',()=>{
        if(input.files.length){ fichier=input.files[0];
            txt.innerHTML='<i class="fas fa-check" style="color:#16a34a"></i> '+fichier.name;
            zone.style.borderColor='#16a34a'; submit.disabled=false; }
    });
    submit.addEventListener('click',()=>{
        if(!fichier)return;
        submit.disabled=true; submit.innerHTML='<i class="fas fa-spinner fa-spin"></i> Import...';
        result.style.display='none';
        const fd=new FormData();
        fd.append('csv_file',fichier); fd.append('csrf_token','<?= csrfToken() ?>');
        fetch('import_categories.php',{method:'POST',body:fd})
            .then(r=>r.json())
            .then(d=>{
                submit.innerHTML='<i class="fas fa-upload"></i> Lancer l\'import'; submit.disabled=false;
                result.style.display='block';
                if(d.ok){
                    let h='<div class="alerte succes"><i class="fas fa-check-circle"></i> '+d.inseres+' catégorie(s) importée(s) sur '+d.total+'.</div>';
                    if(d.cols_ignorees&&d.cols_ignorees.length) h+='<div style="font-size:12px;color:#92400e;margin-top:6px">Colonnes ignorées : '+d.cols_ignorees.join(', ')+'</div>';
                    h+='<button onclick="location.reload()" class="btn-primary" style="width:100%;justify-content:center;margin-top:10px;padding:10px"><i class="fas fa-sync"></i> Rafraîchir</button>';
                    result.innerHTML=h;
                } else {
                    result.innerHTML='<div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> '+(d.error||'Erreur')+'</div>';
                }
            })
            .catch(()=>{
                submit.innerHTML='<i class="fas fa-upload"></i> Lancer l\'import'; submit.disabled=false;
                result.style.display='block';
                result.innerHTML='<div class="alerte erreur">Erreur réseau.</div>';
            });
    });
})();

// EXPORT CSV
const DATA_IMPORT  = <?= json_encode($export_import, JSON_UNESCAPED_UNICODE) ?>;
const DATA_COMPLET = <?= json_encode($export_complet, JSON_UNESCAPED_UNICODE) ?>;

function telechargerCSV(lignes, nomFichier, separateur) {
    const csv = lignes.map(ligne =>
        ligne.map(cell => {
            const v = (cell === null || cell === undefined) ? '' : String(cell);
            return '"' + v.replace(/"/g, '""') + '"';
        }).join(separateur)
    ).join('\r\n');
    const blob = new Blob(['\uFEFF' + csv], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = nomFichier + '_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
function exportImport()  { telechargerCSV(DATA_IMPORT,  'categories_import',  ','); }
function exportComplet() { telechargerCSV(DATA_COMPLET, 'categories_complet', ','); }
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
