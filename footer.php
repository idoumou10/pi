<?php
// ============================================================
// INCLUDES/FOOTER.PHP — Pied de page mutualisé
// ============================================================
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col">
            <div class="footer-logo">
                <i class="fas fa-microchip"></i>
                <span><strong>Electro</strong>Composants</span>
            </div>
            <p>Tout pour vos projets électroniques en Mauritanie.</p>
        </div>
        <div class="footer-col">
            <h4>Navigation</h4>
            <a href="index.php">Accueil</a>
            <a href="catalogue.php">Catalogue</a>
            <a href="promotions.php">Promotions</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="footer-col">
            <h4>Mon compte</h4>
            <?php if (estConnecte()): ?>
                <a href="profil.php">Mon profil</a>
                <a href="mes_commandes.php">Mes commandes</a>
                <a href="favoris.php">Mes favoris</a>
                <a href="deconnexion.php">Déconnexion</a>
            <?php else: ?>
                <a href="login.php">Connexion</a>
                <a href="inscription.php">Inscription</a>
            <?php endif; ?>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <p><i class="fas fa-phone"></i> +222 22 55 44 33</p>
            <p><i class="fas fa-envelope"></i> contact@electrocomposants.mr</p>
            <p><i class="fas fa-map-marker-alt"></i> Nouakchott, Mauritanie</p>
        </div>
    </div>
    <div class="footer-bottom">
        © <?= date('Y') ?> <span>ElectroComposants</span> — Projet SUPNUM
    </div>
</footer>

<script>
// Burger menu mobile
const burger  = document.getElementById('burger');
const mainNav = document.getElementById('mainNav');
if (burger && mainNav) {
    burger.addEventListener('click', () => {
        const open = mainNav.classList.toggle('open');
        burger.setAttribute('aria-expanded', open);
    });
}
// Dropdown catégories
document.querySelectorAll('.dropdown-toggle').forEach(btn => {
    btn.addEventListener('click', e => {
        e.stopPropagation();
        const dd = btn.parentElement;
        const wasOpen = dd.classList.contains('open');
        document.querySelectorAll('.dropdown.open').forEach(o => o.classList.remove('open'));
        if (!wasOpen) dd.classList.add('open');
        btn.setAttribute('aria-expanded', !wasOpen);
    });
});
document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown.open').forEach(o => o.classList.remove('open'));
});
</script>
</body>
</html>
