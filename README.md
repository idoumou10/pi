# 🔌 ElectroComposants — E-commerce de composants électroniques

Projet PHP / MySQL — SUPNUM Mauritanie

---

## 📦 Contenu du projet

```
projet pi 2/
├── admin/                      # Back-office (admin)
│   ├── index.php               # Tableau de bord
│   ├── produits.php            # Gestion des produits
│   ├── catergories.php         # Gestion des catégories
│   ├── promo.php               # Gestion des promotions
│   ├── commandes.php           # Gestion des commandes
│   ├── utilisateurs.php        # Gestion des utilisateurs
│   ├── avis.php                # Modération des avis
│   └── profil.php              # Profil admin
│
├── assets/
│   ├── style.css               # Styles côté client
│   └── admin.css               # Styles back-office
│
├── images/                     # 20 images produits
│
├── includes/
│   ├── header.php              # En-tête commun (avec menu déroulant)
│   ├── footer.php              # Pied de page commun
│   ├── admin_header.php        # En-tête admin
│   ├── admin_footer.php        # Pied admin
│   ├── security.php            # Fonctions de sécurité (CSRF, validation...)
│   └── favoris_lib.php         # Bibliothèque favoris ❤️
│
├── config.php                  # Connexion BDD + session sécurisée
├── index.php                   # Page d'accueil
├── catalogue.php               # Catalogue produits
├── promotions.php              # Page promotions (client)
├── favoris.php                 # ❤️ Mes favoris
├── panier_page.php             # Mon panier (avec images des produits ✅)
├── commande.php                # Passer une commande
├── confirmation.php            # Confirmation de commande
├── mes_commandes.php           # Historique des commandes
├── profil.php                  # Mon profil
├── login.php                   # Connexion
├── inscription.php             # Inscription
├── deconnexion.php             # Déconnexion
├── contact.php                 # Formulaire de contact
├── avis.php                    # Laisser un avis
│
├── (fichiers métier appelés par les pages)
│   ├── utilisateurs.php
│   ├── produits.php
│   ├── categories.php
│   ├── panier.php
│   ├── commandes.php
│   └── ajouter_panier.php
│
├── database_patch.sql          # ⚠️ PATCH SQL à exécuter avant tout !
└── README.md                   # Ce fichier
```

---

## 🚀 Installation

### Étape 1 — Prérequis

- **XAMPP** ou **WAMP** (PHP 7.4+, MySQL 5.7+ ou MariaDB 10.3+)
- Un navigateur récent

### Étape 2 — Mettre les fichiers en place

Décompresser le ZIP dans le dossier `htdocs` de XAMPP :

```
C:\xampp\htdocs\projet pi 2\
```

### Étape 3 — Importer la base de données

1. Démarrer XAMPP (Apache + MySQL)
2. Ouvrir phpMyAdmin : http://localhost/phpmyadmin
3. Créer la base **composants_electroniques** (si elle n'existe pas)
4. **Importer d'abord** le fichier original `composants_electroniques.sql` (la base de départ)
5. **Puis exécuter** `database_patch.sql` (livré dans ce projet) — il :
   - Corrige les colonnes incohérentes
   - Crée les tables manquantes (commandes, details_commande, avis, favoris)
   - Crée un compte admin par défaut

### Étape 4 — Configurer la connexion BDD

Ouvrir `config.php` et vérifier les paramètres :

```php
$conn = new mysqli('localhost', 'root', '', 'composants_electroniques');
```

(Par défaut, XAMPP n'a pas de mot de passe pour `root`.)

### Étape 5 — Lancer le site

Aller sur : http://localhost/projet%20pi%202/

---

## 🔑 Compte admin par défaut

| | |
|---|---|
| **Email** | `admin@electrocomposants.mr` |
| **Mot de passe** | `Admin@2026` |

> ⚠️ **TRÈS IMPORTANT** : changez ce mot de passe immédiatement après votre première connexion via **Admin → Mon profil**.

---

## ✨ Fonctionnalités

### 🛒 Côté client
- Catalogue de composants électroniques avec filtres et recherche
- **Menu déroulant par catégorie** dans la barre de navigation
- **Système de favoris** ❤️ (cliquer sur le cœur d'un produit)
- Panier avec **images des produits** et gestion des quantités
- Tunnel de commande sécurisé (paiement à la livraison)
- Page promotions avec codes promo copiables
- Page contact, profil, avis sur les commandes livrées

### 🔧 Côté admin
- Tableau de bord avec statistiques (CA, commandes en attente, stock faible…)
- CRUD complet : Produits, Catégories, Promotions
- Gestion des commandes (changement de statut, détail)
- Gestion des utilisateurs (activer/désactiver, promouvoir admin)
- Modération des avis clients

### 🔒 Sécurité
- ✅ **Toutes les requêtes SQL** utilisent des `prepared statements`
- ✅ **Protection CSRF** sur tous les formulaires et actions sensibles
- ✅ **Hash bcrypt** pour les mots de passe (avec rehash automatique)
- ✅ **Session sécurisée** : HttpOnly, SameSite=Lax, regénération d'ID
- ✅ **Rate-limiting** sur la connexion (5 essais max / 15 min)
- ✅ **Échappement HTML** systématique (XSS)
- ✅ **Validation stricte** des entrées utilisateur

### 📱 Responsive
Le site s'adapte à tous les écrans (desktop, tablette, mobile) avec des breakpoints à 1024px, 768px et 480px.

---

## 🆘 Aide

### Si le site n'affiche pas les images
Les images sont dans `/images/`. Vérifiez que le dossier a été bien décompressé.

### Si la connexion BDD échoue
Vérifiez les identifiants dans `config.php`. Sous XAMPP par défaut :
- Hôte : `localhost`
- Utilisateur : `root`
- Mot de passe : *(vide)*

### Si une page admin affiche une erreur SQL
C'est probablement que le `database_patch.sql` n'a pas été exécuté. Lancez-le dans phpMyAdmin.

---

## 👩‍💻 Auteure

**Fatimetou** — SUPNUM Mauritanie  
Audité et sécurisé avec ❤️

---
