<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';
require_once __DIR__ . '/commandes.php';

exigerConnexion();

$message = '';
$erreur  = '';
$user    = getUtilisateur($conn, $_SESSION['id_utilisateur']);

// Modifier infos
if (isset($_POST['modifier_infos'])) {
    if (!csrfVerify()) {
        $erreur = 'Session expirée.';
    } else {
        $nom       = clean($_POST['nom'] ?? '');
        $prenom    = clean($_POST['prenom'] ?? '');
        $email     = clean($_POST['email'] ?? '');
        $telephone = clean($_POST['telephone'] ?? '');
        $adresse   = clean($_POST['adresse'] ?? '');
        $ville     = clean($_POST['ville'] ?? '');
        $pays      = clean($_POST['pays'] ?? '');

        if (!$nom || !$prenom || !$email) {
            $erreur = 'Nom, prénom et email obligatoires.';
        } elseif (!validEmail($email)) {
            $erreur = 'Email invalide.';
        } else {
            $stmt = $conn->prepare(
                "UPDATE utilisateurs
                 SET nom=?, prenom=?, email=?, telephone=?, adresse=?, ville=?, pays=?
                 WHERE id_utilisateur=?"
            );
            $stmt->bind_param("sssssssi",
                $nom, $prenom, $email, $telephone, $adresse, $ville, $pays,
                $_SESSION['id_utilisateur']
            );
            if ($stmt->execute()) {
                $_SESSION['nom']    = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['email'] = $email;
                $message = 'Informations mises à jour.';
                $user = getUtilisateur($conn, $_SESSION['id_utilisateur']);
            } else {
                $erreur = 'Erreur lors de la mise à jour.';
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
            $erreur = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif (!preg_match('/[A-Z]/', $nouveau) || !preg_match('/[0-9]/', $nouveau)) {
            $erreur = 'Au moins une majuscule et un chiffre requis.';
        } elseif ($nouveau !== $confirm) {
            $erreur = 'Les mots de passe ne correspondent pas.';
        } else {
            $hash = password_hash($nouveau, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE utilisateurs SET mot_de_pass=? WHERE id_utilisateur=?");
            $stmt->bind_param("si", $hash, $_SESSION['id_utilisateur']);
            $stmt->execute();
            $message = 'Mot de passe changé avec succès.';
        }
    }
}

$commandes = getCommandesUtilisateur($conn, $_SESSION['id_utilisateur']);

$page_titre  = 'Mon Profil';
$extra_css = "
.profil-wrapper { max-width: 1100px; margin: 32px auto; padding: 0 24px; }
.profil-header { background: linear-gradient(135deg, var(--bleu), var(--bleu-dark)); color: white; padding: 32px; border-radius: 20px; display: flex; align-items: center; gap: 22px; margin-bottom: 28px; flex-wrap: wrap; }
.avatar { width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; }
.profil-info h2 { font-family: 'Syne', sans-serif; font-size: 22px; margin-bottom: 4px; }
.profil-info p { opacity: 0.9; font-size: 14px; }
.tabs { display: flex; gap: 4px; border-bottom: 2px solid var(--border); margin-bottom: 24px; flex-wrap: wrap; }
.tab-btn { padding: 10px 18px; background: none; border: none; cursor: pointer; font-size: 14px; font-weight: 600; color: var(--gris); border-bottom: 2px solid transparent; margin-bottom: -2px; font-family: inherit; }
.tab-btn.active { color: var(--bleu); border-bottom-color: var(--bleu); }
.tab-content { display: none; background: white; border: 1px solid var(--border); border-radius: 16px; padding: 28px; }
.tab-content.active { display: block; }
.row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 768px) { .row2 { grid-template-columns: 1fr; } }
";
include __DIR__ . '/includes/header.php';
?>

<main>
<div class="profil-wrapper">
    <div class="profil-header">
        <div class="avatar"><i class="fas fa-user"></i></div>
        <div class="profil-info">
            <h2><?= e($user['prenom'] . ' ' . $user['nom']) ?></h2>
            <p><i class="fas fa-envelope"></i> <?= e($user['email']) ?></p>
            <p style="font-size:12px;opacity:0.8"><i class="fas fa-calendar"></i> Membre depuis <?= e(date('d/m/Y', strtotime($user['date_inscription']))) ?></p>
        </div>
        <a href="deconnexion.php" class="btn-secondary" style="margin-left:auto">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>

    <?php if ($message): ?><div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($message) ?></div><?php endif; ?>
    <?php if ($erreur):  ?><div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur)  ?></div><?php endif; ?>

    <div class="tabs">
        <button class="tab-btn active" data-tab="infos"><i class="fas fa-user"></i> Mes informations</button>
        <button class="tab-btn" data-tab="mdp"><i class="fas fa-lock"></i> Mot de passe</button>
        <button class="tab-btn" data-tab="cmd"><i class="fas fa-shopping-bag"></i> Mes commandes (<?= count($commandes) ?>)</button>
    </div>

    <!-- INFOS -->
    <div class="tab-content active" id="tab-infos">
        <form method="POST">
            <?= csrfField() ?>
            <div class="row2">
                <div class="form-group">
                    <label>Nom <span class="obligatoire">*</span></label>
                    <input type="text" name="nom" required value="<?= e($user['nom']) ?>">
                </div>
                <div class="form-group">
                    <label>Prénom <span class="obligatoire">*</span></label>
                    <input type="text" name="prenom" required value="<?= e($user['prenom']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email <span class="obligatoire">*</span></label>
                <input type="email" name="email" required value="<?= e($user['email']) ?>">
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="telephone" value="<?= e($user['telephone']) ?>">
            </div>
            <div class="form-group">
                <label>Adresse</label>
                <input type="text" name="adresse" value="<?= e($user['adresse']) ?>">
            </div>
            <div class="row2">
                <div class="form-group">
                    <label>Ville</label>
                    <input type="text" name="ville" value="<?= e($user['ville']) ?>">
                </div>
                <div class="form-group">
                    <label>Pays</label>
                    <input type="text" name="pays" value="<?= e($user['pays']) ?>">
                </div>
            </div>
            <button type="submit" name="modifier_infos" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
        </form>
    </div>

    <!-- MDP -->
    <div class="tab-content" id="tab-mdp">
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
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_mdp" required minlength="8">
            </div>
            <button type="submit" name="changer_mdp" class="btn-primary"><i class="fas fa-key"></i> Changer le mot de passe</button>
        </form>
    </div>

    <!-- COMMANDES -->
    <div class="tab-content" id="tab-cmd">
        <?php if (empty($commandes)): ?>
            <div class="empty">
                <i class="fas fa-shopping-bag"></i>
                <p>Aucune commande pour le moment.</p>
                <a href="catalogue.php" class="btn-primary" style="display:inline-flex;margin-top:8px">Commencer</a>
            </div>
        <?php else: ?>
            <div style="text-align:center">
                <a href="mes_commandes.php" class="btn-primary">
                    <i class="fas fa-list"></i> Voir mes commandes (<?= count($commandes) ?>)
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
</main>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
