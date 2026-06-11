<?php
// ============================================================
// PRODUITS.PHP — Récupération des produits
// ============================================================

require_once __DIR__ . '/config.php';

// Fragment SELECT pour la note + le statut promo (à utiliser après p.*, c.NOM, m.nom_marque)
const SQL_SELECT_AVIS_PROMO = "
        av.note_moyenne, av.nb_avis,
        EXISTS(
            SELECT 1 FROM promotions pr
            WHERE pr.actif = 1
              AND CURDATE() BETWEEN pr.date_debut AND pr.date_fin
              AND (pr.id_categorie = p.id_categorie OR pr.id_categorie IS NULL)
        ) AS en_promo
";

function getTousProduits($conn) {
    $sql = "SELECT p.*, c.NOM, m.nom_marque," . SQL_SELECT_AVIS_PROMO . "
            FROM produit p
            LEFT JOIN categories c ON p.id_categorie = c.id_categorie
            LEFT JOIN marques m    ON p.id_marque    = m.id_marque
            LEFT JOIN (
                SELECT id_produit, AVG(note_sur_5) AS note_moyenne, COUNT(*) AS nb_avis
                FROM avis GROUP BY id_produit
            ) av ON av.id_produit = p.id_produit
            WHERE p.statut = 'disponible'
            ORDER BY p.id_produit DESC";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function getProduitParId($conn, $id) {
    $stmt = $conn->prepare(
        "SELECT p.*, c.NOM, m.nom_marque," . SQL_SELECT_AVIS_PROMO . "
         FROM produit p
         LEFT JOIN categories c ON p.id_categorie = c.id_categorie
         LEFT JOIN marques m    ON p.id_marque    = m.id_marque
         LEFT JOIN (
             SELECT id_produit, AVG(note_sur_5) AS note_moyenne, COUNT(*) AS nb_avis
             FROM avis GROUP BY id_produit
         ) av ON av.id_produit = p.id_produit
         WHERE p.id_produit = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getProduitParCategorie($conn, $id_categorie) {
    $stmt = $conn->prepare(
        "SELECT p.*, c.NOM, m.nom_marque," . SQL_SELECT_AVIS_PROMO . "
         FROM produit p
         LEFT JOIN categories c ON p.id_categorie = c.id_categorie
         LEFT JOIN marques m    ON p.id_marque    = m.id_marque
         LEFT JOIN (
             SELECT id_produit, AVG(note_sur_5) AS note_moyenne, COUNT(*) AS nb_avis
             FROM avis GROUP BY id_produit
         ) av ON av.id_produit = p.id_produit
         WHERE p.id_categorie = ? AND p.statut = 'disponible'"
    );
    $stmt->bind_param("i", $id_categorie);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function rechercherProduit($conn, $mot_cle) {
    $mot_cle = "%" . $mot_cle . "%";
    $stmt = $conn->prepare(
        "SELECT p.*, c.NOM, m.nom_marque," . SQL_SELECT_AVIS_PROMO . "
         FROM produit p
         LEFT JOIN categories c ON p.id_categorie = c.id_categorie
         LEFT JOIN marques m    ON p.id_marque    = m.id_marque
         LEFT JOIN (
             SELECT id_produit, AVG(note_sur_5) AS note_moyenne, COUNT(*) AS nb_avis
             FROM avis GROUP BY id_produit
         ) av ON av.id_produit = p.id_produit
         WHERE (p.nom_produit LIKE ? OR p.description LIKE ?)
           AND p.statut = 'disponible'"
    );
    $stmt->bind_param("ss", $mot_cle, $mot_cle);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Produits en promotion : retourne les produits dont la catégorie a
 * une promotion active (ou aucune catégorie = promo globale).
 */
function getProduitsEnPromo($conn) {
    $sql = "SELECT p.*, c.NOM, m.nom_marque,
                   pr.code_promo, pr.type_remise, pr.valeur_remise, pr.date_fin,
                   av.note_moyenne, av.nb_avis, 1 AS en_promo
            FROM produit p
            LEFT JOIN categories c ON p.id_categorie = c.id_categorie
            LEFT JOIN marques m    ON p.id_marque    = m.id_marque
            LEFT JOIN (
                SELECT id_produit, AVG(note_sur_5) AS note_moyenne, COUNT(*) AS nb_avis
                FROM avis GROUP BY id_produit
            ) av ON av.id_produit = p.id_produit
            INNER JOIN promotions pr
                ON (pr.id_categorie = p.id_categorie OR pr.id_categorie IS NULL)
            WHERE pr.actif = 1
              AND CURDATE() BETWEEN pr.date_debut AND pr.date_fin
              AND p.statut = 'disponible'
            ORDER BY pr.valeur_remise DESC";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Identifiant de produit à partir duquel un produit est considéré "Nouveau"
 * (les 5 derniers produits ajoutés, par ordre d'id_produit décroissant).
 */
function getSeuilNouveau($conn, $nb = 5) {
    $res = $conn->query("SELECT MAX(id_produit) AS m, COUNT(*) AS n FROM produit")->fetch_assoc();
    $max = (int)($res['m'] ?? 0);
    $n   = (int)($res['n'] ?? 0);
    return max(1, $max - min($nb, $n) + 1);
}
