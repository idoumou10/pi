<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';
require_once __DIR__ . '/produits.php';
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/includes/favoris_lib.php';

$id_cat    = getId($_GET['categorie'] ?? 0);
$recherche = clean($_GET['q'] ?? '');

if ($recherche !== '') {
    $tous = rechercherProduit($conn, $recherche);
} elseif ($id_cat) {
    $tous = getProduitParCategorie($conn, $id_cat);
} else {
    $tous = getTousProduits($conn);
}

// ── PAGINATION ──
$par_page    = 12;
$total       = count($tous);
$total_pages = max(1, (int)ceil($total / $par_page));
$page        = max(1, (int)($_GET['p'] ?? 1));
if ($page > $total_pages) $page = $total_pages;
$offset      = ($page - 1) * $par_page;
$produits    = array_slice($tous, $offset, $par_page);

// Conserver les paramètres (catégorie / recherche) dans les liens de page
$base_params = [];
if ($recherche !== '') $base_params['q'] = $recherche;
if ($id_cat)           $base_params['categorie'] = $id_cat;
function lienPage($p, $base) {
    $base['p'] = $p;
    return 'catalogue.php?' . http_build_query($base);
}

$categories    = getToutesCategories($conn);
$favoris_ids   = estConnecte() ? getIdsFavoris($conn, $_SESSION['id_utilisateur']) : [];
$seuil_nouveau = getSeuilNouveau($conn);

$page_titre  = 'Catalogue';
$page_active = 'catalogue';
$extra_css = "
.catalogue-wrapper { max-width: 1280px; margin: 24px auto; padding: 0 24px; }
.content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px; }
.content-header h2 { font-family: 'Syne', sans-serif; font-size: 20px; }
.content-header span { color: var(--gris); font-size: 14px; }
.card { cursor: pointer; transition: transform .15s, box-shadow .15s; }
.card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.10); }
#drawer-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; opacity:0; transition:opacity .25s; }
#drawer-overlay.open { display:block; opacity:1; }
#product-drawer { position:fixed; top:0; right:0; bottom:0; width:min(460px,100vw); background:#fff; z-index:1001; overflow-y:auto; box-shadow:-4px 0 32px rgba(0,0,0,.15); transform:translateX(100%); transition:transform .3s cubic-bezier(.4,0,.2,1); display:flex; flex-direction:column; }
#product-drawer.open { transform:translateX(0); }
.drawer-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border); position:sticky; top:0; background:#fff; z-index:2; }
.drawer-header h3 { font-family:'Syne',sans-serif; font-size:16px; margin:0; }
.drawer-close { background:none; border:none; font-size:22px; cursor:pointer; color:var(--gris); padding:4px 8px; border-radius:8px; transition:background .15s; }
.drawer-close:hover { background:var(--gris-light); }
.drawer-body { padding:20px; flex:1; }
.drawer-img { width:100%; height:210px; object-fit:contain; background:var(--gris-light); border-radius:12px; margin-bottom:16px; padding:12px; box-sizing:border-box; }
.drawer-img-placeholder { width:100%; height:210px; background:var(--gris-light); border-radius:12px; margin-bottom:16px; display:flex; align-items:center; justify-content:center; font-size:52px; color:#cbd5e1; }
.drawer-cat { font-size:12px; color:var(--bleu); font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px; }
.drawer-name { font-family:'Syne',sans-serif; font-size:20px; font-weight:700; margin-bottom:10px; line-height:1.3; }
.drawer-price { font-size:26px; font-weight:700; color:var(--bleu); margin-bottom:12px; }
.drawer-price small { font-size:14px; font-weight:400; color:var(--gris); }
.drawer-badge { display:inline-flex; align-items:center; gap:5px; font-size:13px; font-weight:600; padding:5px 12px; border-radius:20px; margin-bottom:16px; }
.drawer-badge.en-stock { background:#dcfce7; color:#15803d; }
.drawer-badge.rupture  { background:#fee2e2; color:#b91c1c; }
.drawer-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--gris); margin:16px 0 7px; }
.drawer-description { font-size:14px; line-height:1.7; color:#374151; background:var(--gris-light); border-radius:10px; padding:12px 14px; }
.drawer-meta { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:8px; }
.drawer-meta-item { background:var(--gris-light); border-radius:10px; padding:10px 12px; }
.drawer-meta-item .label { font-size:11px; color:var(--gris); margin-bottom:3px; }
.drawer-meta-item .value { font-size:13px; font-weight:600; }
.drawer-actions { padding:14px 20px; border-top:1px solid var(--border); display:flex; gap:10px; background:#fff; position:sticky; bottom:0; }
.drawer-actions a, .drawer-actions span { flex:1; display:flex; align-items:center; justify-content:center; gap:8px; padding:12px; border-radius:10px; font-size:14px; font-weight:600; text-decoration:none; transition:opacity .15s; }
.drawer-actions a:hover { opacity:.85; }
.btn-panier-drawer { background:var(--bleu); color:#fff !important; }
.btn-panier-drawer.disabled { background:#e2e8f0; color:#94a3b8 !important; pointer-events:none; }
.btn-fav-drawer { flex:0 0 48px !important; background:var(--gris-light); color:var(--noir) !important; font-size:18px; }
@media(max-width:768px){ #product-drawer{ width:100vw; } }

/* ===== PAGINATION JOLIE ===== */
.pagination { display:flex; justify-content:center; align-items:center; gap:6px; margin:34px 0 10px; flex-wrap:wrap; }
.pagination a, .pagination span {
    min-width:40px; height:40px; padding:0 12px;
    display:inline-flex; align-items:center; justify-content:center;
    border-radius:10px; font-size:14px; font-weight:600;
    text-decoration:none; border:1px solid var(--border);
    background:#fff; color:var(--noir); transition:all .15s;
}
.pagination a:hover { border-color:var(--bleu); color:var(--bleu); background:var(--bleu-light); }
.pagination .page-actuelle { background:var(--bleu); border-color:var(--bleu); color:#fff; cursor:default; }
.pagination .page-fleche { font-size:13px; }
.pagination .page-fleche.off { opacity:.4; pointer-events:none; }
.pagination .page-points { border:none; background:none; color:var(--gris); min-width:auto; padding:0 2px; }
.pagination-info { text-align:center; color:var(--gris); font-size:13px; margin-bottom:8px; }
@media(max-width:480px){
    .pagination a, .pagination span { min-width:36px; height:36px; font-size:13px; padding:0 9px; }
}
";
include __DIR__ . '/includes/header.php';
?>

<main>
<div class="catalogue-wrapper">

    <div class="content-header">
        <h2>
            <?php if ($recherche !== ''): ?>
                Résultats pour "<?= e($recherche) ?>"
            <?php elseif ($id_cat && $cat_sel = getCategorieParId($conn, $id_cat)): ?>
                <?= e($cat_sel['NOM']) ?>
            <?php else: ?>
                Tous les produits
            <?php endif; ?>
        </h2>
        <span><?= number_format($total) ?> produit(s) trouvé(s)</span>
    </div>

    <?php if (empty($tous)): ?>
        <div class="empty">
            <i class="fas fa-box-open"></i>
            <p>Aucun produit trouvé.</p>
            <a href="catalogue.php">Voir tous les produits</a>
        </div>
    <?php else: ?>
    <div class="produits-grid">
        <?php foreach ($produits as $p):
            $is_fav = in_array($p['id_produit'], $favoris_ids);
            $drawer_data = htmlspecialchars(json_encode([
                'id'          => (int)$p['id_produit'],
                'nom'         => $p['nom_produit'],
                'categorie'   => $p['NOM'] ?? '',
                'prix'        => number_format($p['prix_unitaire_MRU'], 2),
                'stock'       => (int)$p['stock_disponible'],
                'description' => $p['description'] ?? '',
                'sku'         => $p['reference_SKU'] ?? '',
                'image'       => $p['image'] ?? '',
                'marque'      => $p['nom_marque'] ?? '',
                'is_fav'      => $is_fav,
            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
            include __DIR__ . '/includes/product_card.php';
        endforeach; ?>
    </div>

    <!-- ===== PAGINATION ===== -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-info">
        Affichage <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $par_page, $total) ?></strong>
        sur <strong><?= number_format($total) ?></strong> produits
    </div>
    <nav class="pagination" aria-label="Pagination">
        <!-- Précédent -->
        <a href="<?= $page > 1 ? e(lienPage($page-1, $base_params)) : '#' ?>"
           class="page-fleche <?= $page <= 1 ? 'off' : '' ?>" aria-label="Page précédente">
            <i class="fas fa-chevron-left"></i>
        </a>

        <?php
        // Affichage intelligent : 1 … 4 5 [6] 7 8 … 20
        $debut = max(1, $page - 2);
        $fin   = min($total_pages, $page + 2);

        if ($debut > 1) {
            echo '<a href="'.e(lienPage(1, $base_params)).'">1</a>';
            if ($debut > 2) echo '<span class="page-points">…</span>';
        }
        for ($i = $debut; $i <= $fin; $i++) {
            if ($i == $page) {
                echo '<span class="page-actuelle">'.$i.'</span>';
            } else {
                echo '<a href="'.e(lienPage($i, $base_params)).'">'.$i.'</a>';
            }
        }
        if ($fin < $total_pages) {
            if ($fin < $total_pages - 1) echo '<span class="page-points">…</span>';
            echo '<a href="'.e(lienPage($total_pages, $base_params)).'">'.$total_pages.'</a>';
        }
        ?>

        <!-- Suivant -->
        <a href="<?= $page < $total_pages ? e(lienPage($page+1, $base_params)) : '#' ?>"
           class="page-fleche <?= $page >= $total_pages ? 'off' : '' ?>" aria-label="Page suivante">
            <i class="fas fa-chevron-right"></i>
        </a>
    </nav>
    <?php endif; ?>

    <?php endif; ?>

</div>
</main>

<div id="drawer-overlay" onclick="fermerDrawer()"></div>

<div id="product-drawer" role="dialog" aria-modal="true" aria-label="Détails du produit">
    <div class="drawer-header">
        <h3 id="drawer-title">Détails du produit</h3>
        <button class="drawer-close" onclick="fermerDrawer()" aria-label="Fermer">&times;</button>
    </div>
    <div class="drawer-body" id="drawer-body"></div>
    <div class="drawer-actions" id="drawer-actions"></div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const CSRF         = '<?= csrfToken() ?>';
const EST_CONNECTE = <?= estConnecte() ? 'true' : 'false' ?>;

function ouvrirDrawer(p) {
    const body    = document.getElementById('drawer-body');
    const actions = document.getElementById('drawer-actions');
    document.getElementById('drawer-title').textContent = p.nom;

    const imgHtml = p.image
        ? `<img src="images/${p.image}" alt="${p.nom}" class="drawer-img"
               onerror="this.style.display='none';document.getElementById('dfb').style.display='flex';">
           <div id="dfb" class="drawer-img-placeholder" style="display:none"><i class="fas fa-microchip"></i></div>`
        : `<div class="drawer-img-placeholder"><i class="fas fa-microchip"></i></div>`;

    const stockHtml = p.stock > 0
        ? `<span class="drawer-badge en-stock"><i class="fas fa-check-circle"></i> En stock (${p.stock} unités)</span>`
        : `<span class="drawer-badge rupture"><i class="fas fa-times-circle"></i> Rupture de stock</span>`;

    const descHtml = p.description
        ? `<div class="drawer-section-title">Description</div>
           <div class="drawer-description">${p.description}</div>` : '';

    const metas = [];
    if (p.categorie) metas.push({label:'Catégorie', value:p.categorie});
    if (p.marque)    metas.push({label:'Marque',    value:p.marque});
    if (p.sku)       metas.push({label:'Référence', value:p.sku});
    const metaHtml = metas.length
        ? `<div class="drawer-section-title">Informations</div>
           <div class="drawer-meta">${metas.map(m=>`
               <div class="drawer-meta-item">
                 <div class="label">${m.label}</div>
                 <div class="value">${m.value}</div>
               </div>`).join('')}</div>` : '';

    body.innerHTML = `${imgHtml}
        <div class="drawer-cat">${p.categorie}</div>
        <div class="drawer-name">${p.nom}</div>
        <div class="drawer-price">${p.prix} <small>MRU</small></div>
        ${stockHtml}${descHtml}${metaHtml}`;

    const btnPanier = p.stock > 0
        ? `<a href="ajouter_panier.php?id=${p.id}&csrf=${CSRF}" class="btn-panier-drawer">
               <i class="fas fa-shopping-cart"></i> Ajouter au panier</a>`
        : `<span class="btn-panier-drawer disabled"><i class="fas fa-times"></i> Indisponible</span>`;

    const btnFav = EST_CONNECTE
        ? `<a href="#" class="btn-fav-drawer" id="drawer-fav-btn"
              onclick="toggleFavoriAjax(event,${p.id},this)" aria-label="Favoris">
               <i class="${p.is_fav?'fas':'far'} fa-heart" style="color:${p.is_fav?'#e11d48':'inherit'}"></i></a>`
        : `<a href="login.php" class="btn-fav-drawer"><i class="far fa-heart"></i></a>`;

    actions.innerHTML = btnPanier + btnFav;

    document.getElementById('drawer-overlay').classList.add('open');
    document.getElementById('product-drawer').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function fermerDrawer() {
    document.getElementById('drawer-overlay').classList.remove('open');
    document.getElementById('product-drawer').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') fermerDrawer(); });

/* ── Favoris AJAX ── */
function toggleFavoriAjax(event, idProduit, btnEl) {
    event.preventDefault();
    event.stopPropagation();
    const icon = btnEl.querySelector('i');

    fetch(`favoris.php?action=toggle&id=${idProduit}&csrf=${CSRF}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) {
            if (data.error === 'non_connecte') window.location = 'login.php';
            return;
        }
        const actif = data.etat === 'ajoute';
        icon.className = actif ? 'fas fa-heart' : 'far fa-heart';
        icon.style.color = actif ? '#e11d48' : '';
        btnEl.classList.toggle('active', actif);

        if (btnEl.id === 'drawer-fav-btn') {
            document.querySelectorAll('.card-fav').forEach(cf => {
                if ((cf.getAttribute('onclick') || '').includes(`, ${idProduit},`)) {
                    cf.querySelector('i').className = actif ? 'fas fa-heart' : 'far fa-heart';
                    cf.classList.toggle('active', actif);
                }
            });
        }

        const badge = document.getElementById('fav-badge');
        if (badge) {
            badge.textContent = data.total;
            badge.style.display = data.total > 0 ? '' : 'none';
        }

        afficherToast(actif ? '\u2764\ufe0f Ajouté aux favoris' : 'Retiré des favoris');
    });
}

function afficherToast(msg) {
    let t = document.getElementById('fav-toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'fav-toast';
        t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:#1e293b;color:#fff;padding:10px 20px;border-radius:20px;font-size:14px;font-weight:500;z-index:9999;opacity:0;transition:opacity .25s,transform .25s;pointer-events:none;';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateX(-50%) translateY(20px)';
    }, 2200);
}
</script>

