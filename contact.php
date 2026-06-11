<?php
require_once __DIR__ . '/config.php';

$succes = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $erreur = 'Session expirée. Rechargez la page.';
    } else {
        $nom     = clean($_POST['nom'] ?? '');
        $email   = clean($_POST['email'] ?? '');
        $sujet   = clean($_POST['sujet'] ?? '');
        $message = clean($_POST['message'] ?? '');

        if (!$nom || !$email || !$message) {
            $erreur = 'Veuillez remplir tous les champs obligatoires.';
        } elseif (!validEmail($email)) {
            $erreur = 'Email invalide.';
        } elseif (mb_strlen($message) < 10) {
            $erreur = 'Message trop court (min. 10 caractères).';
        } else {
            // TODO en production : envoyer par mail() ou stocker en BDD
            $succes = 'Votre message a bien été envoyé ! Nous vous répondrons dans les plus brefs délais.';
        }
    }
}

$page_titre  = 'Contact';
$page_active = 'contact';
include __DIR__ . '/includes/header.php';
?>
<style>
.contact-hero { background: linear-gradient(135deg, var(--noir), #1a3a6b); color: white; text-align: center; padding: 48px 24px; }
.contact-hero h1 { font-family: 'Syne', sans-serif; font-size: 30px; margin-bottom: 8px; }
.contact-hero p { color: #94a3b8; font-size: 15px; }
.contact-wrapper { max-width: 1100px; margin: 40px auto; padding: 0 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
.info-box { display: flex; flex-direction: column; gap: 14px; }
.info-card { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 22px; display: flex; align-items: flex-start; gap: 14px; }
.info-icon { width: 44px; height: 44px; background: var(--bleu); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; }
.info-icon.vert { background: var(--vert); }
.info-icon.orange { background: var(--orange); }
.info-content h3 { font-family: 'Syne', sans-serif; font-size: 15px; margin-bottom: 4px; }
.info-content p { color: var(--gris); font-size: 13px; line-height: 1.5; }
.contact-form-box { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 28px; }
.contact-form-box h3 { font-family: 'Syne', sans-serif; font-size: 18px; margin-bottom: 18px; }
@media (max-width: 1024px) {
    .contact-wrapper { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .contact-hero h1 { font-size: 24px; }
    .contact-form-box { padding: 22px 18px; }
}
</style>

<main>
<section class="contact-hero">
    <h1><i class="fas fa-envelope-open-text"></i> Contactez-nous</h1>
    <p>Nous sommes là pour vous aider</p>
</section>

<div class="contact-wrapper">
    <div class="info-box">
        <div class="info-card">
            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="info-content">
                <h3>Adresse</h3>
                <p>Tevragh Zeina, Nouakchott<br>Mauritanie</p>
            </div>
        </div>
        <div class="info-card">
            <div class="info-icon vert"><i class="fas fa-phone"></i></div>
            <div class="info-content">
                <h3>Téléphone</h3>
                <p>+222 22 55 44 33<br>Du lundi au vendredi, 9h - 18h</p>
            </div>
        </div>
        <div class="info-card">
            <div class="info-icon orange"><i class="fas fa-envelope"></i></div>
            <div class="info-content">
                <h3>Email</h3>
                <p>contact@electrocomposants.mr<br>support@electrocomposants.mr</p>
            </div>
        </div>
    </div>

    <div class="contact-form-box">
        <h3>Envoyez-nous un message</h3>

        <?php if ($succes): ?>
            <div class="alerte succes"><i class="fas fa-check-circle"></i> <?= e($succes) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte erreur"><i class="fas fa-exclamation-circle"></i> <?= e($erreur) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Nom <span class="obligatoire">*</span></label>
                <input type="text" name="nom" required maxlength="100"
                       value="<?= e($_POST['nom'] ?? ($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label>Email <span class="obligatoire">*</span></label>
                <input type="email" name="email" required
                       value="<?= e($_POST['email'] ?? ($_SESSION['email'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label>Sujet</label>
                <input type="text" name="sujet" maxlength="200"
                       value="<?= e($_POST['sujet'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Message <span class="obligatoire">*</span></label>
                <textarea name="message" required minlength="10" maxlength="2000" rows="5"><?= e($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:13px">
                <i class="fas fa-paper-plane"></i> Envoyer
            </button>
        </form>
    </div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
