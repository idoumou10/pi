<?php
// ============================================================
// INCLUDES/ADMIN_HEADER.PHP — Sidebar admin commune
// ============================================================
// Variables attendues : $page_titre, $active (key parmi: dashboard, produits, categories, promotions, commandes, utilisateurs, avis, profil)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';

exigerAdmin();

$page_titre = $page_titre ?? 'Admin';
$active     = $active     ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_titre) ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">

<button class="admin-burger" id="adminBurger" aria-label="Menu admin">
    <i class="fas fa-bars"></i>
</button>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo">
        <div class="icon"><i class="fas fa-microchip"></i></div>
        <div><span>ElectroComposants</span><small>Administration</small></div>
    </div>
    <nav class="sidebar-menu">
        <div class="menu-section">Principal</div>
        <a href="index.php"        class="menu-item <?= $active==='dashboard'   ?'active':'' ?>"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="produits.php"     class="menu-item <?= $active==='produits'    ?'active':'' ?>"><i class="fas fa-box"></i> Produits</a>
        <a href="catergories.php"  class="menu-item <?= $active==='categories'  ?'active':'' ?>"><i class="fas fa-tags"></i> Catégories</a>
        <a href="promo.php"        class="menu-item <?= $active==='promotions'  ?'active':'' ?>"><i class="fas fa-percent"></i> Promotions</a>

        <div class="menu-section">Gestion</div>
        <a href="commandes.php"    class="menu-item <?= $active==='commandes'   ?'active':'' ?>"><i class="fas fa-shopping-bag"></i> Commandes</a>
        <a href="utilisateurs.php" class="menu-item <?= $active==='utilisateurs'?'active':'' ?>"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="avis.php"         class="menu-item <?= $active==='avis'       ?'active':'' ?>"><i class="fas fa-star"></i> Avis</a>

        <div class="menu-section">Mon compte</div>
        <a href="parametres.php"    class="menu-item <?= $active==='parametres' ?'active':'' ?>"><i class="fas fa-cog"></i> Paramètres</a>
        <a href="profil.php"        class="menu-item <?= $active==='profil'    ?'active':'' ?>"><i class="fas fa-user-shield"></i> Mon profil</a>
        <a href="../index.php"      class="menu-item" target="_blank"><i class="fas fa-external-link-alt"></i> Voir le site</a>
        <a href="../deconnexion.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </nav>
</aside>

<main class="admin-main">

