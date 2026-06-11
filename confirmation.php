<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';
require_once __DIR__ . '/commandes.php';

$id_commande = getId($_GET['id'] ?? 0);
if (!$id_commande) { header('Location: index.php'); exit; }

$connecte = estConnecte();

// --- Charger la commande ---
$cmd = getCommandeParId($conn, $id_commande);
if (!$cmd) { header('Location: index.php'); exit; }

// --- Contrôle d'accès (sécurité) ---
// 1) Si connecté : la commande doit lui appartenir
// 2) Si invité   : l'id de commande doit être dans sa session
$autorise = false;
if ($connecte && (int)$cmd['id_utilisateur'] === (int)$_SESSION['id_utilisateur']) {
    $autorise = true;
} elseif (!empty($_SESSION['mes_commandes_invite'])
          && in_array((int)$id_commande, $_SESSION['mes_commandes_invite'], true)) {
    $autorise = true;
}
if (!$autorise) { header('Location: index.php'); exit; }

$details = getDetailsCommande($conn, $id_commande);

// Numéros configurés
$num_whatsapp = getParametre($conn, 'numero_whatsapp', '22200000000');
$num_bancaire = getParametre($conn, 'numero_bancaire', '22200000000');
$nom_destinat = getParametre($conn, 'nom_destinataire', 'ElectroComposants');

$message = '';
$erreur  = '';

// ============================================================
// TRAITEMENT : soumission de la preuve de paiement bancaire
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soumettre_preuve'])) {
    if (!csrfVerify()) {
        $erreur = 'Session expirée. Réessayez.';
    } elseif (!$connecte) {
        $erreur = 'Vous devez être connecté pour soumettre une preuve.';
    } else {
        $reference = clean($_POST['reference'] ?? '');
        $chemin_capture = null;

        // --- Gestion de l'upload de la capture ---
        if (!empty($_FILES['capture']['name']) && $_FILES['capture']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['capture'];

            // Sécurité : taille max 4 Mo
            if ($f['size'] > 4 * 1024 * 1024) {
                $erreur = 'La capture est trop lourde (max 4 Mo).';
            } else {
                // Sécurité : vérifier le VRAI type MIME (pas l'extension)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $f['tmp_name']);
                finfo_close($finfo);

                $types_ok = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];
                if (!isset($types_ok[$mime])) {
                    $erreur = 'Format non accepté. Utilisez une image JPG, PNG ou WEBP.';
                } else {
                    $ext = $types_ok[$mime];
                    // Nom de fichier non devinable, pas fourni par le client
                    $nom_fichier = 'preuve_' . $id_commande . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $dest_dir = __DIR__ . '/uploads/preuves/';
                    if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);

                    if (move_uploaded_file($f['tmp_name'], $dest_dir . $nom_fichier)) {
                        $chemin_capture = 'uploads/preuves/' . $nom_fichier;
                    } else {
                        $erreur = "Échec de l'enregistrement de la capture.";
                    }
                }
            }
        }

        // Si pas d'erreur d'upload, on enregistre la preuve
        if (!$erreur) {
            $res = soumettrePreuvePaiement($conn, $id_commande, $_SESSION['id_utilisateur'], $reference, $chemin_capture);
            if ($res['succes']) {
                $message = $res['message'];
                // Recharger la commande pour afficher le nouveau statut
                $cmd = getCommandeParId($conn, $id_commande);
            } else {
                $erreur = $res['message'];
            }
        }
    }
}

// Lien WhatsApp (récupéré depuis la session, ou reconstruit)
$wa_link = $_SESSION['wa_link_' . $id_commande] ?? null;
if (!$wa_link && $cmd['methode_paiement'] === 'whatsapp') {
    // Reconstruire à partir des détails de la commande
    $panier_like = [];
    foreach ($details as $d) {
        $panier_like[] = [
            'nom_produit' => $d['nom_produit'],
            'quantite'    => $d['quantite'],
            'sous_total'  => $d['sous_total_MRU'],
        ];
    }
    $nom = $cmd['nom_invite'] ?: '';
    $wa_link = lienWhatsappCommande($num_whatsapp, $id_commande, $panier_like, $cmd['total_MRU'], $nom);
}

$methode = $cmd['methode_paiement'];
$st_pay  = $cmd['statut_paiement'];

$page_titre  = 'Commande confirmée';
$page_active = '';
$extra_css = "
.cf-wrap { max-width: 680px; margin: 36px auto; padding: 0 24px; }
.cf-card { background:white; border:1px solid var(--border); border-radius:20px; overflow:hidden; }
.cf-top { padding:34px 30px; text-align:center; }
.cf-icon { width:72px; height:72px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:32px; margin:0 auto 14px; }
.cf-icon.ok { background:#dcfce7; color:#16a34a; }
.cf-top h1 { font-family:'Syne',sans-serif; font-size:23px; margin-bottom:6px; }
.cf-top .num { display:inline-block; background:var(--gris-light); border-radius:8px; padding:6px 14px; font-weight:700; margin-top:8px; }
.cf-body { padding:0 30px 30px; }
.cf-section { border-top:1px solid var(--border); padding:18px 0; }
.cf-section h3 { font-family:'Syne',sans-serif; font-size:15px; margin-bottom:10px; }
.cf-line { display:flex; gap:10px; align-items:center; padding:7px 0; font-size:14px; }
.cf-line-img { width:38px; height:38px; background:var(--gris-light); border-radius:7px; overflow:hidden; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cf-line-img img { width:100%; height:100%; object-fit:contain; padding:3px; }
.cf-line .nm { flex:1; }
.cf-total { display:flex; justify-content:space-between; font-weight:700; font-size:17px; padding-top:12px; margin-top:6px; border-top:2px solid var(--border); }
.cf-total span:last-child { color:var(--bleu); }
.pay-status { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:13px; font-weight:600; }
.ps-attente   { background:#fef9c3; color:#a16207; }
.ps-verifier  { background:#dbeafe; color:#1d4ed8; }
.ps-paye      { background:#dcfce7; color:#15803d; }
.ps-refuse    { background:#fee2e2; color:#b91c1c; }
.bank-box { background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:12px; padding:16px; }
.num-box { background:white; border:1px dashed var(--bleu); border-radius:8px; padding:8px 14px; font-family:monospace; font-size:17px; font-weight:700; color:var(--bleu); display:inline-block; margin:6px 0; }
.wa-btn { display:flex; align-items:center; justify-content:center; gap:10px; background:#25d366; color:white !important; padding:14px; border-radius:12px; font-weight:700; font-size:15px; text-decoration:none; }
.wa-btn:hover { background:#1da851; }
.btn-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
.btn-row a { flex:1; text-align:center; }
.upload-zone { border:2px dashed var(--border); border-radius:10px; padding:16px; text-align:center; cursor:pointer; transition:border-color .15s; }
.upload-zone:hover { border-color:var(--bleu); }
.upload-zone.has-file { border-color:var(--vert); background:#f0fdf4; }
@media(max-width:520px){ .cf-body{ padding:0 18px 22px; } }
";
include __DIR__ . '/includes/header.php';
?>

<main>
<div class="cf-wrap">
    <div class="cf-card">

        <div class="cf-top">
            <div class="cf-icon ok"><i class="fas fa-check"></i></div>
            <h1>Commande enregistrée !</h1>
            <p style="color:var(--gris);font-size:14px">Merci, votre commande a bien été reçue.</p>
            <div class="num">Commande #<?= (int)$id_commande ?></div>
        </div>

        <div class="cf-body">

            <?php if ($message): ?>
                <div class="alerte succes" style="margin-bottom:0"><i class="fas fa-check-circle"></i> <?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($erreur): ?>
                <div class="alerte erreur" style="margin-bottom:0"><i class="fas fa-exclamation-circle"></i> <?= e($erreur) ?></div>
            <?php endif; ?>

            <!-- Récapitulatif produits -->
            <div class="cf-section">
                <h3>Récapitulatif</h3>
                <?php foreach ($details as $d): ?>
                <div class="cf-line">
                    <div class="cf-line-img">
                        <?php if (!empty($d['image'])): ?>
                            <img src="images/<?= e($d['image']) ?>" alt="" onerror="this.style.display='none'">
                        <?php else: ?>
                            <i class="fas fa-microchip" style="color:#cbd5e1"></i>
                        <?php endif; ?>
                    </div>
                    <span class="nm"><?= e($d['nom_produit']) ?> × <?= (int)$d['quantite'] ?></span>
                    <span><?= number_format($d['sous_total_MRU'], 2) ?> MRU</span>
                </div>
                <?php endforeach; ?>
                <div class="cf-total">
                    <span>Total</span>
                    <span><?= number_format($cmd['total_MRU'], 2) ?> MRU</span>
                </div>
            </div>

            <!-- ========== SECTION PAIEMENT selon la méthode ========== -->

            <?php if ($methode === 'sur_place'): ?>
            <div class="cf-section">
                <h3><i class="fas fa-money-bill-wave" style="color:#2563eb"></i> Paiement à la livraison</h3>
                <p style="font-size:14px;color:#374151">
                    Vous réglerez votre commande <strong>en espèces</strong> au moment de la
                    réception. Notre équipe vous contactera pour organiser la livraison.
                </p>
            </div>

            <?php elseif ($methode === 'whatsapp'): ?>
            <div class="cf-section">
                <h3><i class="fab fa-whatsapp" style="color:#25d366"></i> Finaliser sur WhatsApp</h3>
                <p style="font-size:14px;color:#374151;margin-bottom:12px">
                    Cliquez ci-dessous pour nous envoyer votre commande sur WhatsApp.
                    Le récapitulatif est déjà pré-rempli — il ne reste qu'à envoyer le message.
                </p>
                <a href="<?= e($wa_link) ?>" target="_blank" rel="noopener" class="wa-btn">
                    <i class="fab fa-whatsapp" style="font-size:20px"></i>
                    Envoyer ma commande sur WhatsApp
                </a>
                <p style="font-size:12px;color:var(--gris);margin-top:10px;text-align:center">
                    Nous vous répondrons pour le paiement et la livraison.
                </p>
            </div>

            <?php elseif ($methode === 'bancaire'): ?>
            <div class="cf-section">
                <h3>
                    <i class="fas fa-mobile-alt" style="color:#00a651"></i> Paiement par application bancaire
                    <?php
                    $ps_class = ['en_attente'=>'ps-attente','a_verifier'=>'ps-verifier','paye'=>'ps-paye','refuse'=>'ps-refuse'][$st_pay] ?? 'ps-attente';
                    $ps_label = ['en_attente'=>'En attente de paiement','a_verifier'=>'En cours de vérification','paye'=>'Paiement validé','refuse'=>'Paiement refusé'][$st_pay] ?? 'En attente';
                    ?>
                    <span class="pay-status <?= $ps_class ?>" style="margin-left:6px"><?= e($ps_label) ?></span>
                </h3>

                <?php if ($st_pay === 'paye'): ?>
                    <div class="alerte succes" style="margin:8px 0 0">
                        <i class="fas fa-check-circle"></i>
                        Votre paiement a été validé par notre équipe. Merci !
                    </div>

                <?php elseif ($st_pay === 'a_verifier'): ?>
                    <div class="alerte info" style="margin:8px 0 0;background:#dbeafe;border:1px solid #bfdbfe;color:#1d4ed8">
                        <i class="fas fa-hourglass-half"></i>
                        Nous avons bien reçu votre preuve de paiement
                        (réf. <strong><?= e($cmd['reference_transaction']) ?></strong>).
                        Notre équipe la vérifie — vous serez notifié(e) après validation.
                    </div>

                <?php else: ?>
                    <!-- en_attente OU refuse : (re)proposer le formulaire -->
                    <?php if ($st_pay === 'refuse'): ?>
                        <div class="alerte erreur" style="margin:8px 0 12px">
                            <i class="fas fa-times-circle"></i>
                            Votre précédente preuve a été refusée. Vérifiez votre paiement
                            puis renvoyez une référence correcte.
                        </div>
                    <?php endif; ?>

                    <div class="bank-box" style="margin-bottom:14px">
                        <strong>Étape 1 — Effectuez le virement</strong>
                        <p style="margin:6px 0;font-size:13.5px">
                            Envoyez <strong><?= number_format($cmd['total_MRU'], 2) ?> MRU</strong>
                            (Bankily / Sedad / Masrvi / Click) au numéro :
                        </p>
                        <span class="num-box"><?= e($num_bancaire) ?></span>
                        <small style="display:block;color:var(--gris)">au nom de <?= e($nom_destinat) ?></small>
                    </div>

                    <?php if ($connecte): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <strong style="font-size:14px">Étape 2 — Envoyez votre preuve</strong>

                        <div class="form-group" style="margin-top:8px">
                            <label>Référence de la transaction <span class="obligatoire">*</span></label>
                            <input type="text" name="reference" required maxlength="120"
                                   placeholder="Ex : TRX123456789"
                                   value="<?= e($_POST['reference'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Capture d'écran de la confirmation</label>
                            <label class="upload-zone" id="upload-zone" for="capture-input">
                                <i class="fas fa-cloud-upload-alt" style="font-size:24px;color:var(--bleu)"></i>
                                <div id="upload-text" style="font-size:13px;margin-top:6px">
                                    Cliquez pour choisir une image (JPG, PNG, WEBP — max 4 Mo)
                                </div>
                            </label>
                            <input type="file" name="capture" id="capture-input"
                                   accept="image/jpeg,image/png,image/webp" style="display:none">
                        </div>

                        <button type="submit" name="soumettre_preuve" class="btn-primary"
                                style="width:100%;justify-content:center;padding:12px">
                            <i class="fas fa-paper-plane"></i> Envoyer ma preuve de paiement
                        </button>
                    </form>
                    <?php else: ?>
                        <div class="alerte info" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e">
                            Connectez-vous pour soumettre votre preuve de paiement.
                        </div>
                    <?php endif; ?>

                    <p style="font-size:12px;color:var(--gris);margin-top:10px">
                        🔒 Votre commande sera traitée uniquement après vérification
                        du paiement par notre équipe. Aucun paiement n'est validé automatiquement.
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Boutons de navigation -->
            <div class="btn-row">
                <?php if ($connecte): ?>
                    <a href="mes_commandes.php" class="btn-secondary"><i class="fas fa-list"></i> Mes commandes</a>
                <?php endif; ?>
                <a href="catalogue.php" class="btn-primary"><i class="fas fa-shopping-bag"></i> Continuer mes achats</a>
            </div>

        </div>
    </div>
</div>
</main>

<script>
// Aperçu du nom de fichier choisi pour la capture
(function() {
    const input = document.getElementById('capture-input');
    if (!input) return;
    const zone = document.getElementById('upload-zone');
    const txt  = document.getElementById('upload-text');
    input.addEventListener('change', function() {
        if (input.files.length) {
            zone.classList.add('has-file');
            txt.innerHTML = '<i class="fas fa-check" style="color:#16a34a"></i> ' + input.files[0].name;
        } else {
            zone.classList.remove('has-file');
            txt.textContent = 'Cliquez pour choisir une image (JPG, PNG, WEBP — max 4 Mo)';
        }
    });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

