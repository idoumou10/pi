<?php
// ============================================================
// CATEGORIES.PHP — Catégories et familles
// ============================================================

require_once __DIR__ . '/config.php';

function getToutesCategories($conn) {
    $sql = "SELECT c.*, f.nom_famille
            FROM categories c
            LEFT JOIN familles f ON c.id_famille = f.id_famille
            WHERE c.statut = 'actif'
            ORDER BY c.NOM";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function getCategorieParId($conn, $id) {
    $stmt = $conn->prepare(
        "SELECT c.*, f.nom_famille
         FROM categories c
         LEFT JOIN familles f ON c.id_famille = f.id_famille
         WHERE c.id_categorie = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getToutesFamilles($conn) {
    return $conn->query("SELECT * FROM familles ORDER BY nom_famille")->fetch_all(MYSQLI_ASSOC);
}

function getNombreProduitsParCategorie($conn) {
    $sql = "SELECT c.id_categorie, c.NOM, COUNT(p.id_produit) AS nb_produits
            FROM categories c
            LEFT JOIN produit p ON c.id_categorie = p.id_categorie
            WHERE c.statut = 'actif'
            GROUP BY c.id_categorie, c.NOM
            ";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Catégories les plus fournies, pour le bloc "Catégories populaires" de l'accueil.
 */
function getCategoriesPopulaires($conn, $limite = 8) {
    $stmt = $conn->prepare(
        "SELECT c.id_categorie, c.NOM, COUNT(p.id_produit) AS nb_produits
         FROM categories c
         LEFT JOIN produit p ON c.id_categorie = p.id_categorie AND p.statut = 'disponible'
         WHERE c.statut = 'actif'
         GROUP BY c.id_categorie, c.NOM
         ORDER BY nb_produits DESC, c.NOM
         LIMIT ?"
    );
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Icône Font Awesome représentative d'une catégorie (par mot-clé dans le nom).
 */
function getIconeCategorie($nom) {
    $nom = mb_strtolower($nom);
    $map = [
        'actifs'        => 'fa-microchip',
        'passifs'       => 'fa-sliders',
        'capteur'       => 'fa-satellite-dish',
        'mécanique'     => 'fa-cog',
        'alimentation'  => 'fa-bolt',
        'énergie'       => 'fa-bolt',
        'outils'        => 'fa-screwdriver-wrench',
        'instrument'    => 'fa-screwdriver-wrench',
        'circuits'      => 'fa-microchip',
        'rf'            => 'fa-wifi',
        'communication' => 'fa-wifi',
        'prototypage'   => 'fa-cubes',
        'kits'          => 'fa-cubes',
        'afficheur'     => 'fa-tv',
        'ihm'           => 'fa-tv',
        'mémoire'       => 'fa-sd-card',
        'stockage'      => 'fa-sd-card',
        'connectique'   => 'fa-plug',
        'câblage'       => 'fa-plug',
    ];
    foreach ($map as $mot => $icone) {
        if (mb_strpos($nom, $mot) !== false) return $icone;
    }
    return 'fa-microchip';
}

/**
 * Détecte les catégories en double (même nom, ids différents).
 * Retourne un tableau : NOM => liste des id_categorie (ordre croissant).
 */
function getCategoriesDoublons($conn) {
    $sql = "SELECT NOM, GROUP_CONCAT(id_categorie ORDER BY id_categorie) AS ids, COUNT(*) AS n
            FROM categories
            GROUP BY NOM
            HAVING n > 1";
    $res = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    $doublons = [];
    foreach ($res as $r) {
        $doublons[$r['NOM']] = array_map('intval', explode(',', $r['ids']));
    }
    return $doublons;
}

/**
 * Fusionne les catégories en double : conserve l'id le plus petit pour chaque
 * nom dupliqué, réassigne les produits/types/promotions vers cet id,
 * puis supprime les catégories devenues inutiles.
 * Retourne le nombre de catégories supprimées.
 */
function fusionnerCategoriesDoublons($conn) {
    $doublons   = getCategoriesDoublons($conn);
    $supprimees = 0;

    foreach ($doublons as $ids) {
        $idGarde = array_shift($ids); // le plus petit id
        foreach ($ids as $idDoublon) {
            foreach (['produit', 'types_produits', 'promotions'] as $table) {
                $stmt = $conn->prepare("UPDATE $table SET id_categorie = ? WHERE id_categorie = ?");
                $stmt->bind_param("ii", $idGarde, $idDoublon);
                $stmt->execute();
            }
            $stmt = $conn->prepare("DELETE FROM categories WHERE id_categorie = ?");
            $stmt->bind_param("i", $idDoublon);
            if ($stmt->execute()) $supprimees++;
        }
    }
    return $supprimees;
}
