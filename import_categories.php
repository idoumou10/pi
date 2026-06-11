<?php
// ============================================================
// ADMIN/IMPORT_CATEGORIES.PHP
// Import CSV de catégories — appelé en AJAX depuis catergories.php
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

// Colonnes reconnues (s=string, i=integer)
$colonnes_valides = [
    'NOM' => 's',
    'description'   => 's',
    'id_famille'    => 'i',
    'statut'        => 's',
];
$valeurs_defaut = [
    'NOM' => '',
    'description'   => '',
    'id_famille'    => 1,
    'statut'        => 'actif',
];

// Lire l'en-tête
$headers_raw = fgetcsv($handle, 0, ',');
if (!$headers_raw) {
    echo json_encode(['ok' => false, 'error' => 'Fichier vide ou mal formaté.']); exit;
}
$headers = array_map(fn($h) => trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)), $headers_raw);

// Colonne obligatoire
if (!in_array('NOM', $headers)) {
    echo json_encode(['ok' => false,
        'error' => "Colonne obligatoire manquante : « NOM ».\nColonnes trouvées : " . implode(', ', $headers)]);
    exit;
}

$cols_utilisees = array_filter($headers, fn($h) => isset($colonnes_valides[$h]));
$cols_inconnues = array_values(array_filter($headers, fn($h) => !isset($colonnes_valides[$h])));

// Familles existantes (pour valider id_famille)
$familles_ok = [];
$res = $conn->query("SELECT id_famille FROM familles");
while ($r = $res->fetch_assoc()) $familles_ok[(int)$r['id_famille']] = true;

// Lire les lignes
$lignes = [];
while (($row = fgetcsv($handle, 0, ',')) !== false) {
    if (count($row) !== count($headers)) continue;
    $ligne = array_combine($headers, $row);
    $data  = $valeurs_defaut;
    foreach ($cols_utilisees as $col) {
        $val = trim($ligne[$col]);
        if ($val === '') continue;
        $data[$col] = $colonnes_valides[$col] === 'i' ? (int)$val : $val;
    }
    if (empty($data['NOM'])) continue;
    // Si id_famille invalide, on prend la 1re famille dispo
    if (!isset($familles_ok[$data['id_famille']])) {
        $data['id_famille'] = array_key_first($familles_ok) ?? 1;
    }
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
    // id_categorie n'a pas AUTO_INCREMENT : on génère MAX+1
    $next = (int)$conn->query("SELECT COALESCE(MAX(id_categorie),0)+1 AS n FROM categories")->fetch_assoc()['n'];

    $stmt = $conn->prepare(
        "INSERT INTO categories (id_categorie, id_famille, NOM, description, statut)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($lignes as $d) {
        $stmt->bind_param("iisss",
            $next, $d['id_famille'], $d['NOM'], $d['description'], $d['statut']
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
