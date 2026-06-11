<?php
// ============================================================
// UTILISATEURS.PHP — Inscription, Login, Déconnexion (sécurisé)
// ============================================================

require_once __DIR__ . '/config.php';

// ---- Inscription ----
function inscrireUtilisateur($conn, $nom, $prenom, $email, $telephone, $mot_de_pass, $adresse, $ville, $pays) {

    // Email déjà utilisé ?
    $stmt = $conn->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        return ['succes' => false, 'message' => 'Cet email est déjà utilisé.'];
    }
    $stmt->close();

    // Hasher le mot de passe (PASSWORD_DEFAULT = bcrypt actuellement)
    $hash = password_hash($mot_de_pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO utilisateurs
            (nom, prenom, email, telephone, mot_de_pass, adresse, ville, pays, role, date_inscription, statut)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'client', NOW(), 'actif')"
    );
    $stmt->bind_param("ssssssss", $nom, $prenom, $email, $telephone, $hash, $adresse, $ville, $pays);

    if ($stmt->execute()) {
        return ['succes' => true, 'message' => 'Inscription réussie !'];
    }
    return ['succes' => false, 'message' => 'Erreur lors de l\'inscription.'];
}

// ---- Connexion ----
function connecterUtilisateur($conn, $email, $mot_de_pass) {

    $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ? AND statut = 'actif' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($mot_de_pass, $user['mot_de_pass'])) {

        // Régénérer l'ID (anti-fixation)
        session_regenerate_id(true);

        $_SESSION['id_utilisateur'] = (int)$user['id_utilisateur'];
        $_SESSION['nom']            = $user['nom'];
        $_SESSION['prenom']         = $user['prenom'];
        $_SESSION['email']          = $user['email'];
        $_SESSION['role']           = $user['role'];
        $_SESSION['last_regen']     = time();

        // Rehasher si l'algo a évolué
        if (password_needs_rehash($user['mot_de_pass'], PASSWORD_DEFAULT)) {
            $new = password_hash($mot_de_pass, PASSWORD_DEFAULT);
            $u = $conn->prepare("UPDATE utilisateurs SET mot_de_pass=? WHERE id_utilisateur=?");
            $u->bind_param("si", $new, $user['id_utilisateur']);
            $u->execute();
        }
        return ['succes' => true, 'message' => 'Connexion réussie !'];
    }
    return ['succes' => false, 'message' => 'Email ou mot de passe incorrect.'];
}

// ---- Helpers ----
function estConnecte() {
    return isset($_SESSION['id_utilisateur']);
}

function estAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function exigerConnexion() {
    if (!estConnecte()) {
        header('Location: login.php');
        exit;
    }
}

function exigerAdmin() {
    if (!estConnecte() || !estAdmin()) {
        header('Location: ../login.php');
        exit;
    }
}

// ---- Récupérer infos utilisateur ----
function getUtilisateur($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
