<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';
require_once __DIR__ . '/panier.php';
require_once __DIR__ . '/commandes.php';

// ⚠️ PAS de exigerConnexion() : un invité peut commander.

$panier = getPanier($conn);
$total  = getTotalPanier($conn);

if (empty($panier)) {
    header('Location: panier_page.php'); exit;
}

$connecte = estConnecte();
$user     = $connecte ? getUtilisateur($conn, $_SESSION['id_utilisateur']) : null;

// Numéros configurés par l'admin
$num_whatsapp  = getParametre($conn, 'numero_whatsapp', '22200000000');
$num_bancaire  = getParametre($conn, 'numero_bancaire', '22200000000');
$nom_destinat  = getParametre($conn, 'nom_destinataire', 'ElectroComposants');

$message = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $erreur = 'Session expirée. Veuillez réessayer.';
    } else {
        $adresse  = clean($_POST['adresse_livraison'] ?? '');
        $ville    = clean($_POST['ville'] ?? '');
        $tel      = clean($_POST['telephone'] ?? '');
        $methode  = clean($_POST['paiement'] ?? 'sur_place');
        $nom_inv  = clean($_POST['nom_invite'] ?? '');

        // Validation commune
        if (!$adresse || !$ville) {
            $erreur = 'Adresse et ville sont obligatoires.';
        } elseif (!$connecte && !$nom_inv) {
            $erreur = 'Veuillez indiquer votre nom.';
        } elseif (!$connecte && !$tel) {
            $erreur = 'Le téléphone est obligatoire pour vous contacter.';
        } else {
            // Garder une copie du panier AVANT qu'il soit vidé
            $panier_copie = $panier;
            $total_copie  = $total;

            $full_adresse = $adresse . ', ' . $ville;

            $result = creerCommande($conn, $connecte ? $_SESSION['id_utilisateur'] : null, [
                'adresse'    => $full_adresse,
                'methode'    => $methode,
                'est_invite' => !$connecte,
                'nom_invite' => $connecte ? null : $nom_inv,
                'tel'        => $tel,
            ]);

            if ($result['succes']) {
                $id_cmd  = $result['id_commande'];
                $methode = $result['methode']; // méthode réellement appliquée (whitelistée)

                // Mémoriser l'ID de commande en session pour autoriser
                // l'accès à la page de confirmation (même invité).
                if (!isset($_SESSION['mes_commandes_invite'])) {
                    $_SESSION['mes_commandes_invite'] = [];
                }
                $_SESSION['mes_commandes_invite'][] = $id_cmd;

                // Cas WhatsApp : on prépare le lien et on redirige vers confirmation
                if ($methode === 'whatsapp') {
                    $nom_pour_wa = $connecte ? ($user['prenom'].' '.$user['nom']) : $nom_inv;
                    $lien = lienWhatsappCommande($num_whatsapp, $id_cmd, $panier_copie, $total_copie, $nom_pour_wa);
                    // On stocke le lien en session pour l'afficher sur la page confirmation
                    $_SESSION['wa_link_' . $id_cmd] = $lien;
                }

                header('Location: confirmation.php?id=' . $id_cmd);
                exit;
            }
            $erreur = $result['message'];
        }
    }
}

$page_titre  = 'Passer la commande';
$page_active = '';
$extra_css = "
.commande-wrapper { max-width: 1100px; margin: 32px auto; padding: 0 24px; display: grid; grid-template-columns: 1fr 360px; gap: 24px; }
.commande-wrapper h2 { font-family: 'Syne', sans-serif; font-size: 22px; margin-bottom: 20px; }
.commande-form { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 28px; }
.commande-form h3 { font-family: 'Syne', sans-serif; font-size: 16px; margin-bottom: 14px; }
.resume { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 24px; height: fit-content; position: sticky; top: 90px; }
.resume h3 { font-family: 'Syne', sans-serif; font-size: 16px; margin-bottom: 16px; }
.resume-item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border); align-items: center; }
.resume-item:last-of-type { border-bottom: none; }
.resume-item-img { width: 42px; height: 42px; background: var(--gris-light); border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
.resume-item-img img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }
.resume-item-info { flex: 1; min-width: 0; }
.resume-item-info .name { font-weight: 600; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.resume-item-info .qte { color: var(--gris); font-size: 12px; }
.resume-item-price { font-weight: 700; font-size: 13px; }
.resume-total { display: flex; justify-content: space-between; font-size: 18px; font-weight: 700; padding-top: 14px; margin-top: 12px; border-top: 2px solid var(--border); }
.resume-total span:last-child { color: var(--bleu); }
.pay-option { display:flex; align-items:flex-start; gap:10px; border:2px solid var(--border); background:#fff; padding:14px 16px; border-radius:12px; cursor:pointer; transition:border-color .15s, background .15s; }
.pay-option:hover { border-color: var(--bleu); }
.pay-option.selected { border-color: var(--bleu); background: var(--bleu-light); }
.pay-option input { margin-top:3px; }
.pay-option .pay-txt strong { display:block; font-size:14px; }
.pay-option .pay-txt small { color: var(--gris); font-size:12px; }
.pay-info-box { border-radius:12px; padding:16px; margin-top:6px; font-size:14px; }
.pay-info-bank { background:#eff6ff; border:1.5px solid #bfdbfe; }
.pay-info-wa { background:#f0fdf4; border:1.5px solid #86efac; }
.pay-info-box ol, .pay-info-box ul { margin:6px 0 0; padding-left:20px; line-height:1.9; }
.bank-apps { display:flex; gap:8px; flex-wrap:wrap; margin:8px 0; }
.bank-app { background:white; border:1px solid var(--border); border-radius:8px; padding:5px 10px; font-size:12px; font-weight:600; }
.num-box { background:white; border:1px dashed var(--bleu); border-radius:8px; padding:8px 12px; font-family:monospace; font-size:16px; font-weight:700; color:var(--bleu); display:inline-block; margin:4px 0; }
.guest-banner { background:#fffbeb; border:1.5px solid #fde68a; border-radius:12px; padding:14px 16px; font-size:13.5px; margin-bottom:18px; }
.guest-banner a { color:var(--bleu); font-weight:600; }
@media (max-width: 1024px) {
    .commande-wrapper { grid-template-columns: 1fr; }
    .resume { position: static; }
}";
include __DIR__ . '/includes/header.php';
?>

<main>
<div class="commande-wrapper">
    <div>
        <h2><i class="fas fa-credit-card" style="color:var(--bleu)"></i> Passer la commande</h2>

        <?php if ($erreur): ?>
            <div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur) ?></div>
        <?php endif; ?>

        <?php if (!$connecte): ?>
            <div class="guest-banner">
                <i class="fas fa-info-circle" style="color:#d97706"></i>
                Vous commandez en tant qu'<strong>invité</strong>. Nous vous contacterons par
                WhatsApp pour le paiement et la livraison.<br>
                <a href="login.php?retour=commande">Se connecter</a> pour payer en ligne par application bancaire.
            </div>
        <?php endif; ?>

        <form class="commande-form" method="POST">
            <?= csrfField() ?>

            <h3>Vos informations</h3>

            <?php if (!$connecte): ?>
            <div class="form-group">
                <label>Votre nom <span class="obligatoire">*</span></label>
                <input type="text" name="nom_invite" required maxlength="120"
                       placeholder="Nom complet"
                       value="<?= e($_POST['nom_invite'] ?? '') ?>">
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Adresse complète <span class="obligatoire">*</span></label>
                <input type="text" name="adresse_livraison" required maxlength="200"
                       placeholder="Rue, quartier, numéro..."
                       value="<?= e($_POST['adresse_livraison'] ?? $user['adresse'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Ville <span class="obligatoire">*</span></label>
                <input type="text" name="ville" required maxlength="100"
                       placeholder="Nouakchott"
                       value="<?= e($_POST['ville'] ?? $user['ville'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Téléphone <?= $connecte ? '' : '<span class="obligatoire">*</span>' ?></label>
                <input type="text" name="telephone" <?= $connecte ? '' : 'required' ?> maxlength="40"
                       placeholder="+222 XX XX XX XX"
                       value="<?= e($_POST['telephone'] ?? $user['telephone'] ?? '') ?>">
            </div>

            <h3 style="margin-top:24px">Mode de paiement</h3>
            <div style="display:flex;flex-direction:column;gap:10px">

                <?php if ($connecte): ?>
                <!-- Paiement bancaire : réservé aux comptes -->
                <label class="pay-option" data-pay="bancaire">
                    <input type="radio" name="paiement" value="bancaire">
                    <span class="pay-txt">
                        <strong><i class="fas fa-mobile-alt" style="color:#00a651"></i> Paiement par application bancaire</strong>
                        <small>Bankily, Sedad, Masrvi, Click — vérification par notre équipe</small>
                    </span>
                </label>
                <?php endif; ?>

                <!-- Sur place / cash -->
                <label class="pay-option selected" data-pay="sur_place">
                    <input type="radio" name="paiement" value="sur_place" checked>
                    <span class="pay-txt">
                        <strong><i class="fas fa-money-bill-wave" style="color:#2563eb"></i> Paiement à la livraison (cash)</strong>
                        <small>Vous payez en espèces à la réception</small>
                    </span>
                </label>

                <!-- WhatsApp -->
                <label class="pay-option" data-pay="whatsapp">
                    <input type="radio" name="paiement" value="whatsapp">
                    <span class="pay-txt">
                        <strong><i class="fab fa-whatsapp" style="color:#25d366"></i> Discuter sur WhatsApp</strong>
                        <small>On vous contacte pour fixer le paiement et la livraison</small>
                    </span>
                </label>
            </div>

            <!-- Info paiement bancaire -->
            <?php if ($connecte): ?>
            <div class="pay-info-box pay-info-bank" id="info-bancaire" style="display:none">
                <div style="font-weight:700;margin-bottom:6px;color:#1d4ed8">
                    <i class="fas fa-info-circle"></i> Comment payer par application bancaire
                </div>
                <div class="bank-apps">
                    <span class="bank-app">📱 Bankily</span>
                    <span class="bank-app">📱 Sedad</span>
                    <span class="bank-app">📱 Masrvi</span>
                    <span class="bank-app">📱 Click</span>
                </div>
                <ol>
                    <li>Ouvrez votre application et envoyez
                        <strong><?= number_format($total, 2) ?> MRU</strong> au numéro :
                        <br><span class="num-box"><?= e($num_bancaire) ?></span>
                        <small>(au nom de <?= e($nom_destinat) ?>)</small></li>
                    <li>Notez la <strong>référence de la transaction</strong> donnée par l'application</li>
                    <li>Faites une <strong>capture d'écran</strong> de la confirmation</li>
                    <li>Après avoir confirmé la commande, vous pourrez
                        <strong>saisir la référence et envoyer la capture</strong></li>
                </ol>
                <div style="margin-top:8px;padding-top:8px;border-top:1px solid #bfdbfe;color:#6b7280;font-size:12px">
                    🔒 Votre commande sera validée seulement après vérification du paiement par notre équipe.
                </div>
            </div>
            <?php endif; ?>

            <!-- Info WhatsApp -->
            <div class="pay-info-box pay-info-wa" id="info-whatsapp" style="display:none">
                <div style="font-weight:700;margin-bottom:6px;color:#15803d">
                    <i class="fab fa-whatsapp"></i> Paiement via WhatsApp
                </div>
                <p style="margin:0">
                    Après confirmation, un bouton ouvrira WhatsApp avec le récapitulatif
                    de votre commande déjà rempli. Nous vous répondrons pour organiser
                    le paiement et la livraison.
                </p>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:14px;margin-top:14px">
                <i class="fas fa-check-circle"></i> Confirmer la commande (<?= number_format($total, 2) ?> MRU)
            </button>
            <a href="panier_page.php" style="display:block;text-align:center;margin-top:12px;color:var(--gris);font-size:13px;text-decoration:none">
                ← Retour au panier
            </a>
        </form>
    </div>

    <div class="resume">
        <h3>Récapitulatif (<?= count($panier) ?>)</h3>
        <?php foreach ($panier as $p): ?>
        <div class="resume-item">
            <div class="resume-item-img">
                <?php if (!empty($p['image'])): ?>
                    <img src="images/<?= e($p['image']) ?>" alt="" onerror="this.style.display='none'">
                <?php else: ?>
                    <i class="fas fa-microchip" style="color:#cbd5e1"></i>
                <?php endif; ?>
            </div>
            <div class="resume-item-info">
                <div class="name"><?= e($p['nom_produit']) ?></div>
                <div class="qte">Qté : <?= (int)$p['quantite'] ?></div>
            </div>
            <div class="resume-item-price"><?= number_format($p['sous_total'], 2) ?> MRU</div>
        </div>
        <?php endforeach; ?>

        <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);font-size:13px;color:var(--gris);display:flex;justify-content:space-between">
            <span>Livraison</span><span style="color:var(--vert)">À convenir</span>
        </div>
        <div class="resume-total">
            <span>Total</span>
            <span><?= number_format($total, 2) ?> MRU</span>
        </div>
    </div>
</div>
</main>

<script>
(function() {
    const options = document.querySelectorAll('.pay-option');
    const infoBank = document.getElementById('info-bancaire');
    const infoWa   = document.getElementById('info-whatsapp');

    function refresh() {
        let choisi = 'sur_place';
        options.forEach(opt => {
            const radio = opt.querySelector('input');
            opt.classList.toggle('selected', radio.checked);
            if (radio.checked) choisi = radio.value;
        });
        if (infoBank) infoBank.style.display = (choisi === 'bancaire')  ? 'block' : 'none';
        if (infoWa)   infoWa.style.display   = (choisi === 'whatsapp') ? 'block' : 'none';
    }
    options.forEach(opt => {
        opt.querySelector('input').addEventListener('change', refresh);
    });
    refresh();
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>


