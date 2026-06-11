<?php
// ============================================================
// ADMIN/IMPORT_PRODUITS.PHP
// Import CSV de produits — appelé en AJAX depuis produits.php
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utilisateurs.php';
exigerAdmin();

header('Content-Type: application/json');

if (!csrfVerify()) {
    echo json_encode(['ok' => false, 'error' => 'Session expirée.']); exit;
}
if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Aucun fichier reçu.']); exit;
}

$handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['ok' => false, 'error' => 'Impossible de lire le fichier.']); exit;
}

// Colonnes reconnues et leur type (s=string, d=decimal, i=integer)
$colonnes_valides = [
    'nom_produit'        => 's',
    'description'        => 's',
    'reference_SKU'      => 's',
    'image'              => 's',
    'statut'             => 's',
    'conformite_rohs'    => 's',
    'prix_unitaire_MRU'  => 'd',
    'stock_disponible'   => 'i',
    'seuil_alerte_stock' => 'i',
    'id_categorie'       => 'i',
    'id_marque'          => 'i',
    'id_fournisseur'     => 'i',
    'id_type_produit'    => 'i',
];

$valeurs_defaut = [
    'nom_produit'        => '',
    'description'        => '',
    'reference_SKU'      => '',
    'image'              => 'default.jpg',
    'statut'             => 'disponible',
    'conformite_rohs'    => 'oui',
    'prix_unitaire_MRU'  => 0,
    'stock_disponible'   => 0,
    'seuil_alerte_stock' => 5,
    'id_categorie'       => null,
    'id_marque'          => null,
    'id_fournisseur'     => null,
    'id_type_produit'    => null,
];

// Lire headers (première ligne)
$headers_raw = fgetcsv($handle, 0, ',');
if (!$headers_raw) {
    echo json_encode(['ok' => false, 'error' => 'Fichier vide ou mal formaté.']); exit;
}
$headers = array_map(fn($h) => trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)), $headers_raw);

// Vérifier colonnes obligatoires
foreach (['nom_produit', 'prix_unitaire_MRU'] as $ob) {
    if (!in_array($ob, $headers)) {
        echo json_encode(['ok' => false,
            'error' => "Colonne obligatoire manquante : « $ob ».\nColonnes trouvées : " . implode(', ', $headers)]);
        exit;
    }
}

$cols_utilisees = array_filter($headers, fn($h) => isset($colonnes_valides[$h]));
$cols_inconnues = array_values(array_filter($headers, fn($h) => !isset($colonnes_valides[$h])));

// Lire les lignes de données
$lignes = [];
while (($row = fgetcsv($handle, 0, ',')) !== false) {
    if (count($row) !== count($headers)) continue;
    $ligne = array_combine($headers, $row);
    $data  = $valeurs_defaut;
    foreach ($cols_utilisees as $col) {
        $val = trim($ligne[$col]);
        if ($val === '') continue; // garder défaut
        $data[$col] = match($colonnes_valides[$col]) {
            'i'     => (int)$val,
            'd'     => (float)str_replace(',', '.', $val),
            default => $val,
        };
    }
    if (empty($data['nom_produit']) || $data['prix_unitaire_MRU'] <= 0) continue;
    $lignes[] = $data;
}
fclose($handle);

if (empty($lignes)) {
    echo json_encode(['ok' => false, 'error' => 'Aucune ligne valide trouvée.']); exit;
}

// Insérer en transaction
$conn->begin_transaction();
$inseres = 0;
$erreurs = [];

try {
    $stmt = $conn->prepare(
        "INSERT INTO produit
            (nom_produit, description, reference_SKU, image, statut, conformite_rohs,
             prix_unitaire_MRU, stock_disponible, seuil_alerte_stock,
             id_categorie, id_marque, id_fournisseur, id_type_produit)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($lignes as $i => $d) {
        $stmt->bind_param("ssssssdiiiii",
            $d['nom_produit'], $d['description'], $d['reference_SKU'],
            $d['image'], $d['statut'], $d['conformite_rohs'],
            $d['prix_unitaire_MRU'], $d['stock_disponible'], $d['seuil_alerte_stock'],
            $d['id_categorie'], $d['id_marque'], $d['id_fournisseur'], $d['id_type_produit']
        );
        $stmt->execute() ? $inseres++ : ($erreurs[] = "Ligne " . ($i+2) . " : " . $conn->error);
    }
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]); exit;
}

echo json_encode([
    'ok'           => true,
    'inseres'      => $inseres,
    'total'        => count($lignes),
    'cols_ignorees'=> $cols_inconnues,
    'erreurs'      => $erreurs,
]);