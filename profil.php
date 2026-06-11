<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
exigerAdmin();

$message = ''; $erreur = '';
$user = getUtilisateur($conn, $_SESSION['id_utilisateur']);

// Modifier infos
if (isset($_POST['modifier_infos'])) {
    if (!csrfVerify()) {
        $erreur = 'Session expirée.';
    } else {
        $nom       = clean($_POST['nom'] ?? '');
        $prenom    = clean($_POST['prenom'] ?? '');
        $email     = clean($_POST['email'] ?? '');
        $telephone = clean($_POST['telephone'] ?? '');

        if (!$nom || !$prenom || !validEmail($email)) {
            $erreur = 'Nom, prénom et email valides obligatoires.';
        } else {
            $stmt = $conn->prepare(
                "UPDATE utilisateurs SET nom=?, prenom=?, email=?, telephone=?
                 WHERE id_utilisateur=?"
            );
            $stmt->bind_param("ssssi", $nom, $prenom, $email, $telephone, $_SESSION['id_utilisateur']);
            if ($stmt->execute()) {
                $_SESSION['nom']    = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['email']  = $email;
                $message = 'Informations mises à jour.';
                $user = getUtilisateur($conn, $_SESSION['id_utilisateur']);
            }
        }
    }
}

// Changer mot de passe
if (isset($_POST['changer_mdp'])) {
    if (!csrfVerify()) {
        $erreur = 'Session expirée.';
    } else {
        $ancien  = $_POST['ancien_mdp']  ?? '';
        $nouveau = $_POST['nouveau_mdp'] ?? '';
        $confirm = $_POST['confirm_mdp'] ?? '';

        if (!password_verify($ancien, $user['mot_de_pass'])) {
            $erreur = 'Ancien mot de passe incorrect.';
        } elseif (mb_strlen($nouveau) < 8) {
            $erreur = 'Au moins 8 caractères.';
        } elseif (!preg_match('/[A-Z]/', $nouveau) || !preg_match('/[0-9]/', $nouveau)) {
            $erreur = 'Au moins une majuscule et un chiffre requis.';
        } elseif ($nouveau !== $confirm) {
            $erreur = 'Les mots de passe ne correspondent pas.';
        } else {
            $hash = password_hash($nouveau, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE utilisateurs SET mot_de_pass=? WHERE id_utilisateur=?");
            $stmt->bind_param("si", $hash, $_SESSION['id_utilisateur']);
            $stmt->execute();
            $message = 'Mot de passe changé.';
        }
    }
}

$page_titre = 'Mon profil';
$active = 'profil';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-header"><h1><i class="fas fa-user-shield"></i> Mon profil admin</h1></div>

<?php if ($message): ?><div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div><?php endif; ?>
<?php if ($erreur):  ?><div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur)  ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px" class="profil-2cols">
    <div class="form-box">
        <h3><i class="fas fa-user"></i> Mes informations</h3>
        <form method="POST">
            <?= csrfField() ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" name="nom" required value="<?= e($user['nom']) ?>">
                </div>
                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" name="prenom" required value="<?= e($user['prenom']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="<?= e($user['email']) ?>">
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="telephone" value="<?= e($user['telephone']) ?>">
            </div>
            <button type="submit" name="modifier_infos" class="btn-primary" style="width:100%;justify-content:center;padding:11px">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </form>
    </div>

    <div class="form-box">
        <h3><i class="fas fa-lock"></i> Changer le mot de passe</h3>
        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Ancien mot de passe</label>
                <input type="password" name="ancien_mdp" required>
            </div>
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="nouveau_mdp" required minlength="8">
                <div style="font-size:11px;color:var(--gris);margin-top:4px">8 caractères min, 1 majuscule, 1 chiffre</div>
            </div>
            <div class="form-group">
                <label>Confirmer</label>
                <input type="password" name="confirm_mdp" required minlength="8">
            </div>
            <button type="submit" name="changer_mdp" class="btn-primary" style="width:100%;justify-content:center;padding:11px">
                <i class="fas fa-key"></i> Changer
            </button>
        </form>
    </div>
</div>

<style>
@media (max-width: 1024px) { .profil-2cols { grid-template-columns: 1fr !important; } }
</style>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
