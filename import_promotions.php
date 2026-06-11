<?php
// ============================================================
// ADMIN/IMPORT_PROMOTIONS.PHP
// Import CSV de promotions — appelé en AJAX depuis promo.php
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

// Colonnes reconnues (s=string, i=integer, d=date)
$colonnes_valides = [
    'code_promo'       => 's',
    'description'      => 's',
    'type_remise'      => 's',
    'valeur_remise'    => 'i',
    'date_debut'       => 'd',
    'date_fin'         => 'd',
    'id_categorie'     => 'i',
    'min_commande_MRU' => 'i',
    'actif'            => 'i',
];
$valeurs_defaut = [
    'code_promo'       => '',
    'description'      => '',
    'type_remise'      => 'pourcentage',
    'valeur_remise'    => 0,
    'date_debut'       => date('Y-m-d'),
    'date_fin'         => date('Y-m-d', strtotime('+1 month')),
    'id_categorie'     => null,
    'min_commande_MRU' => 0,
    'actif'            => 1,
];

// Lire l'en-tête
$headers_raw = fgetcsv($handle, 0, ',');
if (!$headers_raw) {
    echo json_encode(['ok' => false, 'error' => 'Fichier vide ou mal formaté.']); exit;
}
$headers = array_map(fn($h) => trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)), $headers_raw);

// Colonnes obligatoires
foreach (['code_promo', 'valeur_remise'] as $ob) {
    if (!in_array($ob, $headers)) {
        echo json_encode(['ok' => false,
            'error' => "Colonne obligatoire manquante : « $ob ».\nColonnes trouvées : " . implode(', ', $headers)]);
        exit;
    }
}

$cols_utilisees = array_filter($headers, fn($h) => isset($colonnes_valides[$h]));
$cols_inconnues = array_values(array_filter($headers, fn($h) => !isset($colonnes_valides[$h])));

// Types de remise autorisés
$types_ok = ['pourcentage', 'montant_fixe', 'livraison_gratuite'];

// Catégories existantes
$cats_ok = [];
$res = $conn->query("SELECT id_categorie FROM categories");
while ($r = $res->fetch_assoc()) $cats_ok[(int)$r['id_categorie']] = true;

// Lire les lignes
$lignes = [];
while (($row = fgetcsv($handle, 0, ',')) !== false) {
    if (count($row) !== count($headers)) continue;
    $ligne = array_combine($headers, $row);
    $data  = $valeurs_defaut;
    foreach ($cols_utilisees as $col) {
        $val = trim($ligne[$col]);
        if ($val === '') continue;
        if ($colonnes_valides[$col] === 'i') {
            $data[$col] = (int)$val;
        } elseif ($colonnes_valides[$col] === 'd') {
            // Accepte AAAA-MM-JJ ou JJ/MM/AAAA
            $ts = strtotime(str_replace('/', '-', $val));
            $data[$col] = $ts ? date('Y-m-d', $ts) : $data[$col];
        } else {
            $data[$col] = $val;
        }
    }
    // Validations
    if (empty($data['code_promo']) || $data['valeur_remise'] <= 0) continue;
    if (!in_array($data['type_remise'], $types_ok, true)) $data['type_remise'] = 'pourcentage';
    // id_categorie : NULL si absent ou invalide
    if ($data['id_categorie'] !== null && !isset($cats_ok[$data['id_categorie']])) {
        $data['id_categorie'] = null;
    }
    $data['statut'] = $data['actif'] ? 'actif' : 'inactif';
    $lignes[] = $data;
}
fclose($handle);

if (empty($lignes)) {
    echo json_encode(['ok' => false, 'error' => 'Aucune ligne valide trouvée.']); exit;
}

// Insertion en transaction
$conn->begin_transaction();
$inseres = 0;
try {
    // id_promo n'a pas AUTO_INCREMENT : on génère MAX+1
    $next = (int)$conn->query("SELECT COALESCE(MAX(id_promo),0)+1 AS n FROM promotions")->fetch_assoc()['n'];

    $stmt = $conn->prepare(
        "INSERT INTO promotions
            (id_promo, code_promo, description, type_remise, valeur_remise,
             date_debut, date_fin, id_categorie, min_commande_MRU, actif, statut)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($lignes as $d) {
        $stmt->bind_param("isssissiiis",
            $next, $d['code_promo'], $d['description'], $d['type_remise'], $d['valeur_remise'],
            $d['date_debut'], $d['date_fin'], $d['id_categorie'], $d['min_commande_MRU'],
            $d['actif'], $d['statut']
        );
        if ($stmt->execute()) { $inseres++; $next++; }
    }
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]); exit;
}

echo json_encode([
    'ok'            => true,
    'inseres'       => $inseres,
    'total'         => count($lignes),
    'cols_ignorees' => $cols_inconnues,
]);
