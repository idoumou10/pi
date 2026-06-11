<?php
// ============================================================
// INCLUDES/ADMIN_FOOTER.PHP
// ============================================================
?>
</main>

<script>
// Burger admin
const adminBurger = document.getElementById('adminBurger');
const adminSidebar = document.getElementById('adminSidebar');
if (adminBurger) {
    adminBurger.addEventListener('click', () => adminSidebar.classList.toggle('open'));
}
</script>
</body>
</html>
