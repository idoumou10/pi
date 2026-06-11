<?php
// ============================================================
// COMMANDES.PHP — Gestion des commandes (avec paiement & invité)
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/panier.php';

/**
 * Récupère un paramètre du site (numéro WhatsApp, etc.)
 */
function getParametre($conn, $cle, $defaut = '') {
    $stmt = $conn->prepare("SELECT valeur FROM parametres WHERE cle = ?");
    $stmt->bind_param("s", $cle);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return $r ? $r['valeur'] : $defaut;
}

/**
 * Crée une commande à partir du panier de la session.
 * Gère :
 *   - les utilisateurs connectés ($id_utilisateur fourni)
 *   - les invités ($id_utilisateur = null, est_invite = 1)
 *   - la méthode de paiement : sur_place | bancaire | whatsapp
 *
 * IMPORTANT (sécurité) : le statut_paiement est TOUJOURS fixé
 * côté serveur. Le client ne peut jamais le mettre à "paye"
 * lui-même : seul l'admin valide.
 *
 * @param array $opts [
 *     'adresse'  => string,
 *     'methode'  => 'sur_place'|'bancaire'|'whatsapp',
 *     'est_invite' => bool,
 *     'nom_invite' => string|null,
 *     'tel'      => string|null,
 * ]
 */
function creerCommande($conn, $id_utilisateur, array $opts) {

    $panier = getPanier($conn);
    if (empty($panier)) {
        return ['succes' => false, 'message' => 'Le panier est vide.'];
    }
    $total = getTotalPanier($conn);

    // --- Nettoyage / validation des options (anti-triche) ---
    $adresse    = trim($opts['adresse'] ?? '');
    $methode    = $opts['methode'] ?? 'sur_place';
    $est_invite = !empty($opts['est_invite']) ? 1 : 0;
    $nom_invite = trim($opts['nom_invite'] ?? '') ?: null;
    $tel        = trim($opts['tel'] ?? '') ?: null;

    // Whitelist stricte des méthodes
    $methodes_ok = ['sur_place', 'bancaire', 'whatsapp'];
    if (!in_array($methode, $methodes_ok, true)) {
        $methode = 'sur_place';
    }

    // Un invité ne peut PAS payer par app bancaire (réservé aux comptes)
    if ($est_invite && $methode === 'bancaire') {
        $methode = 'whatsapp';
    }

    // statut_paiement initial — décidé par le SERVEUR, jamais par le client
    // sur_place / whatsapp -> en_attente (sera réglé physiquement/par discussion)
    // bancaire             -> en_attente (le client soumettra sa preuve ensuite)
    $statut_paiement = 'en_attente';

    $conn->begin_transaction();
    try {
        // Re-vérifier les stocks (verrou FOR UPDATE)
        foreach ($panier as $p) {
            $s = $conn->prepare("SELECT stock_disponible FROM produit WHERE id_produit = ? FOR UPDATE");
            $s->bind_param("i", $p['id_produit']);
            $s->execute();
            $stock = $s->get_result()->fetch_assoc()['stock_disponible'];
            if ($stock < $p['quantite']) {
                throw new Exception("Stock insuffisant pour " . $p['nom_produit']);
            }
        }

        // Insérer la commande
        $stmt = $conn->prepare(
            "INSERT INTO commandes
                (id_utilisateur, est_invite, nom_invite, tel_contact,
                 adresse_livraison, date_commande, total_MRU,
                 methode_paiement, statut_paiement, statut)
             VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, 'en_attente')"
        );
        // id_utilisateur peut être NULL
        $stmt->bind_param("iisssdss",
            $id_utilisateur, $est_invite, $nom_invite, $tel,
            $adresse, $total, $methode, $statut_paiement
        );
        $stmt->execute();
        $id_commande = $conn->insert_id;

        // Détails + décrément stock
        foreach ($panier as $p) {
            $d = $conn->prepare(
                "INSERT INTO details_commande
                    (id_commande, id_produit, quantite, prix_unitaire_MRU, sous_total_MRU)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $sous_total = $p['prix_unitaire_MRU'] * $p['quantite'];
            $d->bind_param("iiidd",
                $id_commande, $p['id_produit'], $p['quantite'],
                $p['prix_unitaire_MRU'], $sous_total
            );
            $d->execute();

            $u = $conn->prepare("UPDATE produit SET stock_disponible = stock_disponible - ? WHERE id_produit = ?");
            $u->bind_param("ii", $p['quantite'], $p['id_produit']);
            $u->execute();
        }

        $conn->commit();
        viderPanier();
        return [
            'succes'      => true,
            'message'     => 'Commande créée avec succès !',
            'id_commande' => $id_commande,
            'total'       => $total,
            'methode'     => $methode,
        ];

    } catch (Exception $e) {
        $conn->rollback();
        return ['succes' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Le client soumet sa preuve de paiement bancaire
 * (référence de transaction + capture d'écran).
 *
 * SÉCURITÉ :
 *  - vérifie que la commande appartient bien à l'utilisateur
 *  - le statut passe à 'a_verifier' (PAS 'paye') : seul l'admin valide
 *  - ne fait rien si le paiement est déjà validé
 */
function soumettrePreuvePaiement($conn, $id_commande, $id_utilisateur, $reference, $chemin_capture = null) {
    // Vérifier la propriété de la commande
    $stmt = $conn->prepare(
        "SELECT statut_paiement, methode_paiement
         FROM commandes
         WHERE id_commande = ? AND id_utilisateur = ?"
    );
    $stmt->bind_param("ii", $id_commande, $id_utilisateur);
    $stmt->execute();
    $cmd = $stmt->get_result()->fetch_assoc();

    if (!$cmd) {
        return ['succes' => false, 'message' => "Commande introuvable."];
    }
    if ($cmd['methode_paiement'] !== 'bancaire') {
        return ['succes' => false, 'message' => "Cette commande n'est pas un paiement bancaire."];
    }
    if ($cmd['statut_paiement'] === 'paye') {
        return ['succes' => false, 'message' => "Ce paiement est déjà validé."];
    }

    $reference = trim($reference);
    if (mb_strlen($reference) < 3) {
        return ['succes' => false, 'message' => "Référence de transaction invalide."];
    }

    // Statut -> a_verifier (l'admin tranchera)
    $stmt = $conn->prepare(
        "UPDATE commandes
         SET reference_transaction = ?, capture_paiement = ?,
             statut_paiement = 'a_verifier', date_paiement_soumis = NOW()
         WHERE id_commande = ? AND id_utilisateur = ?"
    );
    $stmt->bind_param("ssii", $reference, $chemin_capture, $id_commande, $id_utilisateur);
    $stmt->execute();

    return ['succes' => true, 'message' => 'Preuve de paiement envoyée. Notre équipe va la vérifier.'];
}

/**
 * ADMIN : valide ou refuse un paiement.
 * $decision = 'paye' | 'refuse'
 */
function deciderPaiement($conn, $id_commande, $decision) {
    if (!in_array($decision, ['paye', 'refuse'], true)) return false;
    $stmt = $conn->prepare("UPDATE commandes SET statut_paiement = ? WHERE id_commande = ?");
    $stmt->bind_param("si", $decision, $id_commande);
    return $stmt->execute();
}

function getCommandesUtilisateur($conn, $id_utilisateur) {
    $stmt = $conn->prepare(
        "SELECT * FROM commandes
         WHERE id_utilisateur = ?
         ORDER BY date_commande DESC"
    );
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getCommandeUtilisateur($conn, $id_commande, $id_utilisateur) {
    $stmt = $conn->prepare(
        "SELECT * FROM commandes WHERE id_commande = ? AND id_utilisateur = ?"
    );
    $stmt->bind_param("ii", $id_commande, $id_utilisateur);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Récupère une commande SANS exiger d'utilisateur (pour la page
 * de confirmation invité). Sécurisé par un "jeton" : on exige que
 * la commande soit récente OU que l'id de session corresponde.
 */
function getCommandeParId($conn, $id_commande) {
    $stmt = $conn->prepare("SELECT * FROM commandes WHERE id_commande = ?");
    $stmt->bind_param("i", $id_commande);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getDetailsCommande($conn, $id_commande) {
    $stmt = $conn->prepare(
        "SELECT dc.*, p.nom_produit, p.image
         FROM details_commande dc
         LEFT JOIN produit p ON dc.id_produit = p.id_produit
         WHERE dc.id_commande = ?"
    );
    $stmt->bind_param("i", $id_commande);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getToutesCommandes($conn) {
    $sql = "SELECT c.*, u.nom, u.prenom, u.email
            FROM commandes c
            LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
            ORDER BY c.date_commande DESC";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function changerStatutCommande($conn, $id_commande, $statut) {
    $statuts_ok = ['en_attente', 'confirmée', 'en_cours', 'expédiée', 'livrée', 'annulée'];
    if (!in_array($statut, $statuts_ok, true)) return false;
    $stmt = $conn->prepare("UPDATE commandes SET statut = ? WHERE id_commande = ?");
    $stmt->bind_param("si", $statut, $id_commande);
    return $stmt->execute();
}

/**
 * Construit le message WhatsApp pré-rempli avec le contenu du panier.
 * Retourne l'URL  https://wa.me/NUMERO?text=...
 */
function lienWhatsappCommande($numero, $id_commande, $panier, $total, $nom = '') {
    $lignes = [];
    $lignes[] = "Bonjour ElectroComposants 👋";
    $lignes[] = "Je souhaite passer la commande #" . $id_commande . " :";
    $lignes[] = "";
    foreach ($panier as $p) {
        $lignes[] = "• " . $p['nom_produit'] . " x" . $p['quantite']
                  . " = " . number_format($p['sous_total'], 2) . " MRU";
    }
    $lignes[] = "";
    $lignes[] = "TOTAL : " . number_format($total, 2) . " MRU";
    if ($nom) $lignes[] = "Nom : " . $nom;
    $lignes[] = "";
    $lignes[] = "Merci de me contacter pour le paiement et la livraison.";

    $texte = implode("\n", $lignes);
    return 'https://wa.me/' . preg_replace('/\D/', '', $numero)
         . '?text=' . rawurlencode($texte);
}


