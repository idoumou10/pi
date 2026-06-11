<?php
// ============================================================
// PANIER.PHP — Gestion du panier (sessions)
// ============================================================

require_once __DIR__ . '/config.php';

function ajouterAuPanier($conn, $id_produit, $quantite = 1) {
    $id_produit = (int)$id_produit;
    $quantite   = max(1, (int)$quantite);

    $stmt = $conn->prepare("SELECT stock_disponible, nom_produit, statut FROM produit WHERE id_produit = ?");
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $produit = $stmt->get_result()->fetch_assoc();

    if (!$produit || $produit['statut'] !== 'disponible') {
        return ['succes' => false, 'message' => 'Produit introuvable.'];
    }
    if ($produit['stock_disponible'] <= 0) {
        return ['succes' => false, 'message' => 'Ce produit est en rupture de stock.'];
    }

    if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

    $actuel  = $_SESSION['panier'][$id_produit] ?? 0;
    $nouveau = $actuel + $quantite;

    if ($nouveau > $produit['stock_disponible']) {
        return ['succes' => false, 'message' => 'Stock insuffisant (' . $produit['stock_disponible'] . ' restant(s)).'];
    }

    $_SESSION['panier'][$id_produit] = $nouveau;
    return ['succes' => true, 'message' => $produit['nom_produit'] . ' ajouté au panier.'];
}

function supprimerDuPanier($id_produit) {
    unset($_SESSION['panier'][(int)$id_produit]);
}

function modifierQuantite($conn, $id_produit, $quantite) {
    $id_produit = (int)$id_produit;
    $quantite   = (int)$quantite;

    if ($quantite <= 0) {
        supprimerDuPanier($id_produit);
        return ['succes' => true, 'message' => 'Produit retiré du panier.'];
    }
    $stmt = $conn->prepare("SELECT stock_disponible FROM produit WHERE id_produit = ?");
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    if (!$p) {
        return ['succes' => false, 'message' => 'Produit introuvable.'];
    }
    if ($quantite > $p['stock_disponible']) {
        return ['succes' => false, 'message' => 'Stock insuffisant (' . $p['stock_disponible'] . ').'];
    }
    $_SESSION['panier'][$id_produit] = $quantite;
    return ['succes' => true, 'message' => 'Quantité mise à jour.'];
}

function viderPanier() {
    $_SESSION['panier'] = [];
}

/**
 * Récupère le panier avec TOUS les détails (image incluse !).
 */
function getPanier($conn) {
    if (empty($_SESSION['panier'])) return [];

    $ids = array_map('intval', array_keys($_SESSION['panier']));
    $ids_csv = implode(',', $ids);  // déjà nettoyé par intval, donc sûr

    $sql = "SELECT id_produit, nom_produit, prix_unitaire_MRU, stock_disponible, image
            FROM produit
            WHERE id_produit IN ($ids_csv) AND statut = 'disponible'";

    $produits = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

    foreach ($produits as &$p) {
        $qte = $_SESSION['panier'][$p['id_produit']];
        if ($qte > $p['stock_disponible']) {
            $qte = $p['stock_disponible'];
            $_SESSION['panier'][$p['id_produit']] = $qte;
        }
        $p['quantite']   = (int)$qte;
        $p['sous_total'] = $p['prix_unitaire_MRU'] * $qte;
    }
    return $produits;
}

function getTotalPanier($conn) {
    $total = 0;
    foreach (getPanier($conn) as $p) $total += $p['sous_total'];
    return $total;
}

function getNombreArticles() {
    if (empty($_SESSION['panier'])) return 0;
    return array_sum($_SESSION['panier']);
}
