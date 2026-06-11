<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';

if (estConnecte()) {
    header('Location: ' . (estAdmin() ? 'admin/index.php' : 'index.php'));
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrfVerify()) {
        $erreur = 'Session expirée. Rechargez la page.';
    } elseif (!loginRateLimit()) {
        $erreur = 'Trop de tentatives. Réessayez dans 15 minutes.';
    } else {
        $email = clean($_POST['email'] ?? '');
        $pass  = $_POST['mot_de_pass'] ?? '';

        if (!validEmail($email) || !$pass) {
            $erreur = 'Email ou mot de passe invalide.';
            loginRateBump();
        } else {
            $res = connecterUtilisateur($conn, $email, $pass);
            if ($res['succes']) {
                loginRateReset();
                $retour = ($_GET['retour'] ?? '') === 'commande' ? 'commande.php' :
                          (estAdmin() ? 'admin/index.php' : 'index.php');
                header('Location: ' . $retour);
                exit;
            }
            loginRateBump();
            $erreur = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — ElectroComposants</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/style.css">
<style>
body { background: linear-gradient(135deg, #0f172a 0%, #1a3a6b 100%); display: flex; align-items: center; justify-content: center; padding: 24px; }
.auth-card { background: white; border-radius: 20px; padding: 40px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.auth-card .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; margin-bottom: 28px; }
.auth-card h2 { font-family: 'Syne', sans-serif; font-size: 22px; margin-bottom: 6px; }
.auth-card .subtitle { color: var(--gris); font-size: 14px; margin-bottom: 24px; }
.input-wrap { position: relative; }
.input-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gris); font-size: 15px; }
.input-wrap input { padding-left: 40px; }
.divider { display: flex; align-items: center; gap: 10px; margin: 20px 0; color: var(--gris); font-size: 12px; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
.links { text-align: center; margin-top: 14px; font-size: 13px; color: var(--gris); }
.links a { color: var(--bleu); text-decoration: none; font-weight: 600; }
.eye-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--gris); font-size: 15px; padding: 4px; line-height: 1; }
.eye-btn:hover { color: var(--bleu); }
.input-wrap input[type=password], .input-wrap input[type=text].pass-input { padding-right: 38px; }
@media (max-width: 480px) {
    .auth-card { padding: 28px 20px; }
    .input-wrap i.icon-left { display: none; }
    .input-wrap input { padding-left: 14px; }
}
</style>
</head>
<body>
<div class="auth-card">
    <a href="index.php" class="logo">
        <div class="logo-icon"><i class="fas fa-microchip"></i></div>
        <div><strong>Electro</strong><span>Composants</span></div>
    </a>
    <h2>Connexion</h2>
    <p class="subtitle">Bienvenue ! Connectez-vous à votre compte.</p>

    <?php if ($erreur): ?>
        <div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <?= csrfField() ?>
        <div class="form-group">
            <label>Adresse email</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="votre@email.com">
            </div>
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <div class="input-wrap">
                <i class="fas fa-lock icon-left"></i>
                <input type="password" name="mot_de_pass" id="pass-login" required placeholder="••••••••">
                <button type="button" class="eye-btn" onclick="toggleVisu('pass-login', this)" tabindex="-1" aria-label="Afficher le mot de passe">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:13px">
            <i class="fas fa-sign-in-alt"></i> Se connecter
        </button>
    </form>

    <div class="divider">ou</div>
    <div class="links">Pas encore de compte ? <a href="inscription.php">S'inscrire</a></div>
    <div class="links" style="margin-top:6px"><a href="index.php">← Retour au site</a></div>
</div>
<script>
function toggleVisu(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
</body>
</html>
