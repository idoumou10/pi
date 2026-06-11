<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';

if (estConnecte()) {
    header('Location: index.php'); exit;
}

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrfVerify()) {
        $erreur = 'Session expirée. Rechargez la page.';
    } else {
        $nom        = clean($_POST['nom'] ?? '');
        $prenom     = clean($_POST['prenom'] ?? '');
        $email      = clean($_POST['email'] ?? '');
        $telephone  = clean($_POST['telephone'] ?? '');
        $adresse    = clean($_POST['adresse'] ?? '');
        $ville      = clean($_POST['ville'] ?? '');
        $pays       = clean($_POST['pays'] ?? 'Mauritanie');
        $pass       = $_POST['mot_de_pass'] ?? '';
        $pass2      = $_POST['mot_de_pass2'] ?? '';

        if (!$nom || !$prenom || !$email || !$pass) {
            $erreur = 'Veuillez remplir tous les champs obligatoires.';
        } elseif (!validEmail($email)) {
            $erreur = 'L\'email n\'a pas un format valide.';
        } elseif (mb_strlen($pass) < 8) {
            $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif (!preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
            $erreur = 'Le mot de passe doit contenir au moins une majuscule et un chiffre.';
        } elseif ($pass !== $pass2) {
            $erreur = 'Les mots de passe ne correspondent pas.';
        } elseif (mb_strlen($nom) > 100 || mb_strlen($prenom) > 100) {
            $erreur = 'Nom ou prénom trop long.';
        } else {
            $res = inscrireUtilisateur($conn, $nom, $prenom, $email, $telephone, $pass, $adresse, $ville, $pays);
            if ($res['succes']) {
                $succes = 'Compte créé avec succès ! <a href="login.php" style="color:var(--bleu);font-weight:600">Se connecter</a>';
            } else {
                $erreur = $res['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inscription — ElectroComposants</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/style.css">
<style>
body { background: linear-gradient(135deg, #0f172a 0%, #1a3a6b 100%); display: flex; align-items: center; justify-content: center; padding: 24px; }
.auth-card { background: white; border-radius: 20px; padding: 36px; width: 100%; max-width: 540px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.auth-card .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; margin-bottom: 24px; }
.auth-card h2 { font-family: 'Syne', sans-serif; font-size: 22px; margin-bottom: 6px; }
.auth-card .subtitle { color: var(--gris); font-size: 14px; margin-bottom: 22px; }
.row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.input-wrap { position: relative; }
.input-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gris); font-size: 14px; }
.input-wrap input, .input-wrap select { padding-left: 38px; }
.links { text-align: center; margin-top: 14px; font-size: 13px; color: var(--gris); }
.links a { color: var(--bleu); text-decoration: none; font-weight: 600; }
.pass-hint { font-size: 11px; color: var(--gris); margin-top: 4px; }
.eye-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--gris); font-size: 14px; padding: 4px; line-height: 1; }
.eye-btn:hover { color: var(--bleu); }
@media (max-width: 480px) {
    .auth-card { padding: 24px 18px; }
    .row { grid-template-columns: 1fr; }
    .input-wrap i.icon-left { display: none; }
    .input-wrap input, .input-wrap select { padding-left: 14px; }
}
</style>
</head>
<body>
<div class="auth-card">
    <a href="index.php" class="logo">
        <div class="logo-icon"><i class="fas fa-microchip"></i></div>
        <div><strong>Electro</strong><span>Composants</span></div>
    </a>
    <h2>Créer un compte</h2>
    <p class="subtitle">Rejoignez-nous pour commander vos composants.</p>

    <?php if ($erreur): ?>
        <div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur) ?></div>
    <?php endif; ?>
    <?php if ($succes): ?>
        <div class="alerte succes"><i class="fas fa-check-circle"></i> <?= $succes ?></div>
    <?php endif; ?>

    <form method="POST">
        <?= csrfField() ?>
        <div class="row">
            <div class="form-group">
                <label>Nom <span class="obligatoire">*</span></label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nom" required maxlength="100" placeholder="Votre nom"
                           value="<?= e($_POST['nom'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Prénom <span class="obligatoire">*</span></label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="prenom" required maxlength="100" placeholder="Votre prénom"
                           value="<?= e($_POST['prenom'] ?? '') ?>">
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Email <span class="obligatoire">*</span></label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required placeholder="votre@email.com"
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Téléphone</label>
            <div class="input-wrap">
                <i class="fas fa-phone"></i>
                <input type="text" name="telephone" maxlength="30" placeholder="+222 XX XX XX XX"
                       value="<?= e($_POST['telephone'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Adresse</label>
            <div class="input-wrap">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" name="adresse" maxlength="255" placeholder="Votre adresse"
                       value="<?= e($_POST['adresse'] ?? '') ?>">
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Ville</label>
                <div class="input-wrap">
                    <i class="fas fa-city"></i>
                    <input type="text" name="ville" maxlength="100" placeholder="Ville"
                           value="<?= e($_POST['ville'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Pays</label>
                <div class="input-wrap">
                    <i class="fas fa-globe"></i>
                    <input type="text" name="pays" maxlength="100"
                           value="<?= e($_POST['pays'] ?? 'Mauritanie') ?>">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Mot de passe <span class="obligatoire">*</span></label>
                <div class="input-wrap">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="mot_de_pass" id="pass1" required minlength="8" placeholder="••••••••">
                    <button type="button" class="eye-btn" onclick="toggleVisu('pass1','pass2',this)" tabindex="-1" aria-label="Afficher le mot de passe">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="pass-hint">8 caractères min, 1 majuscule, 1 chiffre</div>
            </div>
            <div class="form-group">
                <label>Confirmer <span class="obligatoire">*</span></label>
                <div class="input-wrap">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="mot_de_pass2" id="pass2" required minlength="8" placeholder="Répéter">
                    <button type="button" class="eye-btn" onclick="toggleVisu('pass1','pass2',this)" tabindex="-1" aria-label="Afficher le mot de passe">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:13px;margin-top:6px">
            <i class="fas fa-user-plus"></i> Créer mon compte
        </button>
    </form>
    <div class="links">Déjà un compte ? <a href="login.php">Se connecter</a></div>
    <div class="links" style="margin-top:4px"><a href="index.php">← Retour au site</a></div>
</div>
<script>
// Sur inscription, les deux champs se révèlent ensemble (même bouton)
function toggleVisu(id1, id2, btn) {
    const i1   = document.getElementById(id1);
    const i2   = id2 ? document.getElementById(id2) : null;
    const show = i1.type === 'password';
    i1.type = show ? 'text' : 'password';
    if (i2) i2.type = show ? 'text' : 'password';
    // Mettre à jour toutes les icônes œil de la page
    document.querySelectorAll('.eye-btn i').forEach(ic => {
        ic.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
}
</script>
</body>
</html>