<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
require_once __DIR__ . '/../commandes.php';
exigerAdmin();

$message = ''; $erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $erreur = 'Session expirée.';
    } elseif (isset($_POST['offre_action'])) {
        $maj = function($cle, $val) use ($conn) {
            $stmt = $conn->prepare(
                "INSERT INTO parametres (cle, valeur) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)"
            );
            $stmt->bind_param("ss", $cle, $val);
            $stmt->execute();
        };
        if ($_POST['offre_action'] === 'supprimer') {
            $maj('offre_du_jour_produit', '');
            $maj('offre_du_jour_fin', '');
            $message = "Offre du jour supprimée.";
        } else {
            $id_offre  = (int)($_POST['offre_du_jour_produit'] ?? 0);
            $fin_offre = trim($_POST['offre_du_jour_fin'] ?? '');
            if ($id_offre <= 0 || $fin_offre === '') {
                $erreur = 'Veuillez choisir un produit et une date de fin.';
            } else {
                $maj('offre_du_jour_produit', (string)$id_offre);
                $maj('offre_du_jour_fin', $fin_offre);
                $message = "Offre du jour enregistrée.";
            }
        }
    } else {
        // On ne garde que les chiffres pour les numéros
        $num_wa   = preg_replace('/\D/', '', $_POST['numero_whatsapp'] ?? '');
        $num_bank = preg_replace('/\D/', '', $_POST['numero_bancaire'] ?? '');
        $nom_dest = clean($_POST['nom_destinataire'] ?? '');

        if (strlen($num_wa) < 8 || strlen($num_bank) < 8) {
            $erreur = 'Les numéros doivent contenir au moins 8 chiffres.';
        } else {
            $maj = function($cle, $val) use ($conn) {
                $stmt = $conn->prepare(
                    "INSERT INTO parametres (cle, valeur) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)"
                );
                $stmt->bind_param("ss", $cle, $val);
                $stmt->execute();
            };
            $maj('numero_whatsapp',  $num_wa);
            $maj('numero_bancaire',  $num_bank);
            $maj('nom_destinataire', $nom_dest);
            $message = 'Paramètres enregistrés.';
        }
    }
}

$num_wa   = getParametre($conn, 'numero_whatsapp', '');
$num_bank = getParametre($conn, 'numero_bancaire', '');
$nom_dest = getParametre($conn, 'nom_destinataire', '');

$offre_produit_id = (int)getParametre($conn, 'offre_du_jour_produit', '0');
$offre_fin        = getParametre($conn, 'offre_du_jour_fin', '');
$liste_produits   = $conn->query("SELECT id_produit, nom_produit FROM produit ORDER BY nom_produit")->fetch_all(MYSQLI_ASSOC);

$page_titre = 'Paramètres';
$active = 'parametres';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-cog"></i> Paramètres du site</h1>
</div>

<?php if ($message): ?><div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div><?php endif; ?>
<?php if ($erreur):  ?><div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur)  ?></div><?php endif; ?>

<div style="max-width:560px">
    <div style="background:white;border:1px solid var(--border);border-radius:14px;padding:24px">
        <h3 style="font-family:'Syne',sans-serif;font-size:16px;margin-bottom:6px">Numéros de paiement & contact</h3>
        <p style="color:var(--gris);font-size:13px;margin-bottom:18px">
            Ces numéros sont affichés aux clients lors du paiement.
        </p>

        <form method="POST">
            <?= csrfField() ?>

            <div class="form-group">
                <label><i class="fab fa-whatsapp" style="color:#25d366"></i> Numéro WhatsApp</label>
                <input type="text" name="numero_whatsapp" required
                       placeholder="22236XXXXXX"
                       value="<?= e($num_wa) ?>">
                <small style="color:var(--gris);font-size:12px">
                    Format international sans le « + » (ex : 22236123456). Utilisé pour les commandes WhatsApp.
                </small>
            </div>

            <div class="form-group">
                <label><i class="fas fa-mobile-alt" style="color:#00a651"></i> Numéro pour paiement bancaire</label>
                <input type="text" name="numero_bancaire" required
                       placeholder="36XXXXXX"
                       value="<?= e($num_bank) ?>">
                <small style="color:var(--gris);font-size:12px">
                    Le numéro qui reçoit les paiements Bankily / Sedad / Masrvi / Click.
                </small>
            </div>

            <div class="form-group">
                <label><i class="fas fa-user"></i> Nom du destinataire</label>
                <input type="text" name="nom_destinataire" maxlength="120"
                       placeholder="ElectroComposants"
                       value="<?= e($nom_dest) ?>">
                <small style="color:var(--gris);font-size:12px">
                    Le nom affiché au client (« envoyez au nom de … »).
                </small>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:12px;margin-top:6px">
                <i class="fas fa-save"></i> Enregistrer les paramètres
            </button>
        </form>
    </div>

    <div style="background:white;border:1px solid var(--border);border-radius:14px;padding:24px;margin-top:24px">
        <h3 style="font-family:'Syne',sans-serif;font-size:16px;margin-bottom:6px"><i class="fas fa-bolt" style="color:var(--orange)"></i> Offre du jour</h3>
        <p style="color:var(--gris);font-size:13px;margin-bottom:18px">
            Le produit choisi sera mis en avant sur la page d'accueil avec un badge « Promo » et un compte à rebours.
        </p>

        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="offre_action" value="enregistrer">

            <div class="form-group">
                <label><i class="fas fa-microchip"></i> Produit</label>
                <select name="offre_du_jour_produit" required>
                    <option value="">— Choisir un produit —</option>
                    <?php foreach ($liste_produits as $prod): ?>
                        <option value="<?= (int)$prod['id_produit'] ?>" <?= $offre_produit_id === (int)$prod['id_produit'] ? 'selected' : '' ?>>
                            <?= e($prod['nom_produit']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label><i class="far fa-clock"></i> Fin du compte à rebours</label>
                <input type="datetime-local" name="offre_du_jour_fin" required
                       value="<?= e($offre_fin) ?>">
                <small style="color:var(--gris);font-size:12px">
                    Date et heure auxquelles l'offre du jour expire.
                </small>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:12px;margin-top:6px">
                <i class="fas fa-save"></i> Enregistrer l'offre du jour
            </button>
        </form>

        <?php if ($offre_produit_id > 0): ?>
            <form method="POST" style="margin-top:10px">
                <?= csrfField() ?>
                <input type="hidden" name="offre_action" value="supprimer">
                <button type="submit" class="btn-secondary" style="width:100%;justify-content:center;padding:12px">
                    <i class="fas fa-trash"></i> Retirer l'offre du jour
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
