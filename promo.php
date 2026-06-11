<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
exigerAdmin();

$message = ''; $erreur = '';
$action  = $_GET['action'] ?? 'liste';
$id      = getId($_GET['id'] ?? 0);
$page    = max(1, (int)($_GET['p'] ?? 1));
$par_page = 10;

$categories = $conn->query("SELECT * FROM categories ORDER BY NOM ")->fetch_all(MYSQLI_ASSOC);

// SUPPRIMER
if ($action === 'supprimer' && $id && hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
    $stmt = $conn->prepare("DELETE FROM promotions WHERE id_promo = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $message = 'Promotion supprimée.';
    $action = 'liste';
}

// ENREGISTRER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_promo'])) {
    if (!csrfVerify()) {
        $erreur = 'Session expirée.';
    } else {
        $code        = clean($_POST['code_promo'] ?? '');
        $description = clean($_POST['description'] ?? '');
        $type        = clean($_POST['type_remise'] ?? 'pourcentage');
        $valeur      = (int)($_POST['valeur_remise'] ?? 0);
        $date_debut  = clean($_POST['date_debut'] ?? '');
        $date_fin    = clean($_POST['date_fin'] ?? '');
        $id_cat_raw  = $_POST['id_categorie'] ?? '';
        $id_cat      = $id_cat_raw ? getId($id_cat_raw) : null;
        $min_cmd     = (int)($_POST['min_commande_MRU'] ?? 0);
        $actif       = isset($_POST['actif']) ? 1 : 0;
        $statut_txt  = $actif ? 'actif' : 'inactif';

        if (!$code || $valeur <= 0) {
            $erreur = 'Le code et la valeur sont obligatoires.';
        } else {
            if (!empty($_POST['id_promo'])) {
                $pid = getId($_POST['id_promo']);
                $stmt = $conn->prepare(
                    "UPDATE promotions SET code_promo=?, description=?, type_remise=?, valeur_remise=?,
                            date_debut=?, date_fin=?, id_categorie=?, min_commande_MRU=?, actif=?, statut=?
                     WHERE id_promo=?"
                );
                $stmt->bind_param("sssissiissi",
                    $code, $description, $type, $valeur, $date_debut, $date_fin,
                    $id_cat, $min_cmd, $actif, $statut_txt, $pid
                );
            } else {
                $max = (int)$conn->query("SELECT COALESCE(MAX(id_promo),0)+1 AS nid FROM promotions")->fetch_assoc()['nid'];
                $stmt = $conn->prepare(
                    "INSERT INTO promotions
                        (id_promo, code_promo, description, type_remise, valeur_remise,
                         date_debut, date_fin, id_categorie, min_commande_MRU, actif, statut)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("isssissiiis",
                    $max, $code, $description, $type, $valeur, $date_debut, $date_fin,
                    $id_cat, $min_cmd, $actif, $statut_txt
                );
            }
            $stmt->execute();
            $message = 'Promotion enregistrée.';
            $action  = 'liste';
        }
    }
}

$promo_edit = null;
if ($action === 'modifier' && $id) {
    $stmt = $conn->prepare("SELECT * FROM promotions WHERE id_promo = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $promo_edit = $stmt->get_result()->fetch_assoc();
}

// Total + pagination
$total = (int)$conn->query("SELECT COUNT(*) AS n FROM promotions")->fetch_assoc()['n'];
$total_pages = max(1, (int)ceil($total / $par_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $par_page;

$stmt = $conn->prepare(
    "SELECT p.*, c.NOM FROM promotions p
     LEFT JOIN categories c ON p.id_categorie = c.id_categorie
     ORDER BY p.id_promo DESC LIMIT ? OFFSET ?"
);
$stmt->bind_param("ii", $par_page, $offset);
$stmt->execute();
$promotions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Données pour l'EXPORT (toutes les promotions) ──
$toutes_promos = $conn->query(
    "SELECT p.*, c.NOM FROM promotions p
     LEFT JOIN categories c ON p.id_categorie = c.id_categorie
     ORDER BY p.id_promo"
)->fetch_all(MYSQLI_ASSOC);

// Export IMPORT-COMPATIBLE
$export_import = [];
$export_import[] = ['code_promo','description','type_remise','valeur_remise',
                    'date_debut','date_fin','id_categorie','min_commande_MRU','actif'];
foreach ($toutes_promos as $p) {
    $export_import[] = [
        $p['code_promo'], $p['description'], $p['type_remise'], $p['valeur_remise'],
        $p['date_debut'], $p['date_fin'], $p['id_categorie'],
        $p['min_commande_MRU'], $p['actif'],
    ];
}
// Export COMPLET
$export_complet = [];
$export_complet[] = ['ID','Code','Description','Type remise','Valeur','Date début',
                     'Date fin','Catégorie','Min commande MRU','Actif','Statut'];
foreach ($toutes_promos as $p) {
    $export_complet[] = [
        $p['id_promo'], $p['code_promo'], $p['description'], $p['type_remise'],
        $p['valeur_remise'], $p['date_debut'], $p['date_fin'],
        $p['NOM'] ?? 'Toutes', $p['min_commande_MRU'],
        $p['actif'] ? 'oui' : 'non', $p['statut'],
    ];
}

$page_titre = 'Promotions';
$active = 'promotions';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-percent"></i> Promotions (<?= number_format($total) ?>)</h1>
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
        <button id="btn-new-promo" class="btn-primary">
            <i class="fas fa-plus"></i> Nouvelle promotion
        </button>
    </div>
</div>

<?php if ($message): ?><div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div><?php endif; ?>
<?php if ($erreur):  ?><div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur)  ?></div><?php endif; ?>

<div class="table-box">
    <div class="table-header">
        <h3>Toutes les promotions</h3>
        <div class="table-tools">
            <div class="admin-search">
                <i class="fas fa-search"></i>
                <input type="text" id="search-promo" placeholder="Rechercher (code, description...)..." autocomplete="off">
                <button type="button" class="search-clear" id="search-clear"><i class="fas fa-times-circle"></i></button>
            </div>
        </div>
    </div>
    <div class="search-results-info" id="search-info"></div>

    <div class="table-wrap">
        <table class="admin-table" id="tbl-promo">
            <thead>
                <tr><th>Code</th><th>Description</th><th>Type</th><th>Valeur</th><th>Catégorie</th><th>Du</th><th>Au</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($promotions)): ?>
                    <tr><td colspan="9" style="text-align:center;color:var(--gris);padding:32px">Aucune promotion.</td></tr>
                <?php else: foreach ($promotions as $p): ?>
                <tr>
                    <td><span class="code-display"><?= e($p['code_promo']) ?></span></td>
                    <td style="max-width:220px;color:var(--gris);font-size:13px"><?= e($p['description']) ?></td>
                    <td><span class="badge <?= $p['type_remise']==='pourcentage'?'badge-pct':'badge-fixe' ?>"><?= e($p['type_remise']) ?></span></td>
                    <td><strong><?= e($p['valeur_remise']) ?><?= $p['type_remise']==='pourcentage'?'%':' MRU' ?></strong></td>
                    <td><?= e($p['NOM'] ?? 'Toutes') ?></td>
                    <td><?= $p['date_debut'] ? e(date('d/m/Y', strtotime($p['date_debut']))) : '—' ?></td>
                    <td><?= $p['date_fin']   ? e(date('d/m/Y', strtotime($p['date_fin'])))   : '—' ?></td>
                    <td><span class="badge <?= $p['actif']?'badge-actif':'badge-inactif' ?>"><?= $p['actif']?'Actif':'Inactif' ?></span></td>
                    <td style="white-space:nowrap">
                        <a href="?action=modifier&id=<?= (int)$p['id_promo'] ?>&p=<?= (int)$page ?>" class="btn-mini btn-edit"><i class="fas fa-edit"></i></a>
                        <a href="?action=supprimer&id=<?= (int)$p['id_promo'] ?>&p=<?= (int)$page ?>&csrf=<?= csrfToken() ?>"
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
            sur <strong><?= number_format($total) ?></strong> promotions
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
        <h3><?= $promo_edit ? '✏️ Modifier la promotion' : '➕ Nouvelle promotion' ?></h3>
        <button class="slide-close" id="slide-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="slide-body">
        <form method="POST">
            <?= csrfField() ?>
            <?php if ($promo_edit): ?>
                <input type="hidden" name="id_promo" value="<?= (int)$promo_edit['id_promo'] ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Code promo *</label>
                <input type="text" name="code_promo" required maxlength="44" style="text-transform:uppercase"
                       placeholder="EX: PROMO20" value="<?= e($promo_edit['code_promo'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" maxlength="112" value="<?= e($promo_edit['description'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type_remise">
                    <option value="pourcentage"        <?= ($promo_edit['type_remise'] ?? '')==='pourcentage'?'selected':'' ?>>Pourcentage (%)</option>
                    <option value="montant_fixe"       <?= ($promo_edit['type_remise'] ?? '')==='montant_fixe'?'selected':'' ?>>Montant fixe (MRU)</option>
                    <option value="livraison_gratuite" <?= ($promo_edit['type_remise'] ?? '')==='livraison_gratuite'?'selected':'' ?>>Livraison gratuite</option>
                </select>
            </div>
            <div class="form-group">
                <label>Valeur *</label>
                <input type="number" min="0" name="valeur_remise" required value="<?= e($promo_edit['valeur_remise'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Catégorie (optionnel)</label>
                <select name="id_categorie">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id_categorie'] ?>" <?= ($promo_edit['id_categorie'] ?? '')==$c['id_categorie']?'selected':'' ?>>
                            <?= e($c['NOM']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="form-group">
                    <label>Date début</label>
                    <input type="date" name="date_debut" value="<?= e($promo_edit['date_debut'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Date fin</label>
                    <input type="date" name="date_fin" value="<?= e($promo_edit['date_fin'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Minimum de commande (MRU)</label>
                <input type="number" name="min_commande_MRU" min="0" value="<?= (int)($promo_edit['min_commande_MRU'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="actif" value="1" <?= ($promo_edit['actif'] ?? 1)?'checked':'' ?> style="width:auto">
                    Promotion active
                </label>
            </div>
            <button type="submit" name="enregistrer_promo" class="btn-primary" style="width:100%;justify-content:center;padding:12px;margin-top:6px">
                <i class="fas fa-save"></i> <?= $promo_edit ? 'Enregistrer' : 'Ajouter la promotion' ?>
            </button>
        </form>
    </div>
</aside>

<!-- ===== PANNEAU IMPORT CSV ===== -->
<aside class="slide-panel" id="import-panel">
    <div class="slide-header">
        <h3><i class="fas fa-file-import" style="color:#c2410c"></i> Importer des promotions (CSV)</h3>
        <button class="slide-close" id="import-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="slide-body">
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px;font-size:13px;margin-bottom:16px">
            <strong>Format :</strong> fichier <code>.csv</code> séparé par des virgules, avec ligne d'en-tête.<br><br>
            <strong>Obligatoires :</strong> <code>code_promo</code>, <code>valeur_remise</code><br>
            <strong>Optionnelles :</strong> <code>description</code>, <code>type_remise</code>,
            <code>date_debut</code>, <code>date_fin</code>, <code>id_categorie</code>,
            <code>min_commande_MRU</code>, <code>actif</code><br><br>
            <small><code>type_remise</code> = pourcentage / montant_fixe / livraison_gratuite —
            dates au format AAAA-MM-JJ</small>
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
const btnNew      = document.getElementById('btn-new-promo');
const btnClose    = document.getElementById('slide-close');
const importPanel = document.getElementById('import-panel');
const btnImport   = document.getElementById('btn-import');
const importClose = document.getElementById('import-close');

function openPanel()  { panel.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow='hidden'; }
function openImport() { importPanel.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow='hidden'; }
function closePanel() {
    panel.classList.remove('open'); importPanel.classList.remove('open');
    overlay.classList.remove('open'); document.body.style.overflow='';
    if (window.location.search.includes('action=modifier')) window.location.href = 'promo.php?p=<?= (int)$page ?>';
}
btnNew.addEventListener('click', openPanel);
btnClose.addEventListener('click', closePanel);
btnImport.addEventListener('click', openImport);
importClose.addEventListener('click', closePanel);
overlay.addEventListener('click', closePanel);
document.addEventListener('keydown', e => { if (e.key==='Escape') closePanel(); });
<?php if ($promo_edit || $erreur): ?>
window.addEventListener('DOMContentLoaded', openPanel);
<?php endif; ?>

// RECHERCHE
(function() {
    const input=document.getElementById('search-promo'), clearB=document.getElementById('search-clear'),
          info=document.getElementById('search-info'), rows=document.querySelectorAll('#tbl-promo tbody tr');
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
        fetch('import_promotions.php',{method:'POST',body:fd})
            .then(r=>r.json())
            .then(d=>{
                submit.innerHTML='<i class="fas fa-upload"></i> Lancer l\'import'; submit.disabled=false;
                result.style.display='block';
                if(d.ok){
                    let h='<div class="alerte succes"><i class="fas fa-check-circle"></i> '+d.inseres+' promotion(s) importée(s) sur '+d.total+'.</div>';
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
function exportImport()  { telechargerCSV(DATA_IMPORT,  'promotions_import',  ','); }
function exportComplet() { telechargerCSV(DATA_COMPLET, 'promotions_complet', ','); }
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
