<?php
// ============================================================
// INCLUDES/FAVORIS_LIB.PHP — Fonctions métier favoris ❤️
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../produits.php';

function ajouterFavori($conn, $id_utilisateur, $id_produit) {
    $stmt = $conn->prepare(
        "INSERT IGNORE INTO favoris (id_utilisateur, id_produit, date_ajout)
         VALUES (?, ?, NOW())"
    );
    $stmt->bind_param("ii", $id_utilisateur, $id_produit);
    return $stmt->execute();
}

function retirerFavori($conn, $id_utilisateur, $id_produit) {
    $stmt = $conn->prepare(
        "DELETE FROM favoris WHERE id_utilisateur = ? AND id_produit = ?"
    );
    $stmt->bind_param("ii", $id_utilisateur, $id_produit);
    return $stmt->execute();
}

function toggleFavori($conn, $id_utilisateur, $id_produit) {
    if (estFavori($conn, $id_utilisateur, $id_produit)) {
        retirerFavori($conn, $id_utilisateur, $id_produit);
        return 'retire';
    }
    ajouterFavori($conn, $id_utilisateur, $id_produit);
    return 'ajoute';
}

function estFavori($conn, $id_utilisateur, $id_produit) {
    $stmt = $conn->prepare(
        "SELECT 1 FROM favoris WHERE id_utilisateur = ? AND id_produit = ? LIMIT 1"
    );
    $stmt->bind_param("ii", $id_utilisateur, $id_produit);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_assoc();
}

function getIdsFavoris($conn, $id_utilisateur) {
    if (!$id_utilisateur) return [];
    $stmt = $conn->prepare("SELECT id_produit FROM favoris WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    return array_column($rows, 'id_produit');
}

function getFavorisUtilisateur($conn, $id_utilisateur) {
    $stmt = $conn->prepare(
        "SELECT p.*, c.NOM, m.nom_marque, f.date_ajout," . SQL_SELECT_AVIS_PROMO . "
         FROM favoris f
         INNER JOIN produit p    ON f.id_produit   = p.id_produit
         LEFT  JOIN categories c ON p.id_categorie = c.id_categorie
         LEFT  JOIN marques m    ON p.id_marque    = m.id_marque
         LEFT JOIN (
             SELECT id_produit, AVG(note_sur_5) AS note_moyenne, COUNT(*) AS nb_avis
             FROM avis GROUP BY id_produit
         ) av ON av.id_produit = p.id_produit
         WHERE f.id_utilisateur = ?
         ORDER BY f.date_ajout DESC"
    );
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getNombreFavoris($conn, $id_utilisateur) {
    if (!$id_utilisateur) return 0;
    $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM favoris WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();
    return (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0);
}
