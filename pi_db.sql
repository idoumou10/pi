-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 20 mai 2026 à 18:01
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `pi_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

DROP TABLE IF EXISTS `avis`;
CREATE TABLE IF NOT EXISTS `avis` (
  `id_avis` int NOT NULL AUTO_INCREMENT,
  `id_produit` int NOT NULL,
  `id_utilisateur` int NOT NULL,
  `note_sur_5` tinyint NOT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_avis` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_avis`),
  KEY `idx_produit` (`id_produit`),
  KEY `idx_utilisateur` (`id_utilisateur`)
) ;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id_categorie` int NOT NULL,
  `id_famille` int NOT NULL,
  `nom_categorie` varchar(72) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(182) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id_categorie`, `id_famille`, `nom_categorie`, `description`, `statut`) VALUES
(1, 2, 'Composants actifs', 'Composants qui peuvent contrôler ou amplifier un signal électrique.', 'actif'),
(2, 1, 'Composants Passifs', 'Composants qui ne produisent pas d’énergie et ne peuvent pas amplifier un signal.', 'actif'),
(3, 5, 'Capteurs & transducteurs', 'Composants qui transforment une grandeur physique en signal électrique', 'actif'),
(4, 7, 'Électromécanique', 'Composants qui combinent électricité et mouvement mécanique.', 'actif'),
(5, 3, 'Alimentation & énergie', 'Composants et systèmes qui fournissent ou convertissent l’énergie électrique', 'actif'),
(6, 1, 'Outils & instruments', 'Appareils utilisés pour mesurer et tester les circuits électroniques', 'actif'),
(7, 2, 'Circuits intégrés', 'Amplificateurs op, comparateurs, timers, convertisseurs, puces logiques.', 'actif'),
(8, 4, 'Modules RF & communication', 'Modules Wi-Fi, Bluetooth, ZigBee, LoRa, NRF24, GSM, GPS.', 'actif'),
(9, 5, 'Prototypage & kits', 'Breadboards, shields, kits de composants, câbles Dupont, jumper wires.', 'actif'),
(10, 7, 'Afficheurs & IHM', 'Écrans LCD, OLED, TFT, 7 segments, matrices LED, claviers.', 'actif'),
(11, 6, 'Mémoires & stockage', 'EEPROM, Flash SPI, cartes SD, modules RTC.', 'actif'),
(12, 3, 'Connectique & câblage', 'Connecteurs, prises, bornes, fils, câbles coaxiaux, borniers.', 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

DROP TABLE IF EXISTS `commandes`;
CREATE TABLE IF NOT EXISTS `commandes` (
  `id_commande` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int DEFAULT NULL,
  `est_invite` tinyint(1) NOT NULL DEFAULT '0',
  `nom_invite` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel_contact` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_livraison` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_commande` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_MRU` decimal(10,2) DEFAULT '0.00',
  `methode_paiement` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'sur_place',
  `statut_paiement` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `reference_transaction` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capture_paiement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_paiement_soumis` datetime DEFAULT NULL,
  `statut` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `mode_paiement` enum('bankily','masrivi','sur_place') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sur_place',
  PRIMARY KEY (`id_commande`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_statut` (`statut`),
  KEY `idx_date` (`date_commande`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id_commande`, `id_utilisateur`, `est_invite`, `nom_invite`, `tel_contact`, `adresse_livraison`, `date_commande`, `total_MRU`, `methode_paiement`, `statut_paiement`, `reference_transaction`, `capture_paiement`, `date_paiement_soumis`, `statut`, `mode_paiement`) VALUES
(1, 3, 0, NULL, NULL, 'TVZ, ouakchott', '2026-05-20 18:05:43', 30.00, 'sur_place', 'en_attente', NULL, NULL, NULL, 'en_attente', 'sur_place'),
(2, 3, 0, NULL, '+22200000000', 'TVZ, Nouakchott', '2026-05-20 18:07:48', 65.00, 'sur_place', 'en_attente', NULL, NULL, NULL, 'annulée', 'sur_place');

-- --------------------------------------------------------

--
-- Structure de la table `details_commande`
--

DROP TABLE IF EXISTS `details_commande`;
CREATE TABLE IF NOT EXISTS `details_commande` (
  `id_detail` int NOT NULL AUTO_INCREMENT,
  `id_commande` int NOT NULL,
  `id_produit` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '1',
  `prix_unitaire_MRU` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sous_total_MRU` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detail`),
  KEY `idx_commande` (`id_commande`),
  KEY `idx_produit` (`id_produit`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `details_commande`
--

INSERT INTO `details_commande` (`id_detail`, `id_commande`, `id_produit`, `quantite`, `prix_unitaire_MRU`, `sous_total_MRU`) VALUES
(1, 1, 17, 1, 22.00, 22.00),
(2, 1, 18, 1, 8.00, 8.00),
(3, 2, 15, 1, 35.00, 35.00),
(4, 2, 17, 1, 22.00, 22.00),
(5, 2, 18, 1, 8.00, 8.00);

-- --------------------------------------------------------

--
-- Structure de la table `fabricants`
--

DROP TABLE IF EXISTS `fabricants`;
CREATE TABLE IF NOT EXISTS `fabricants` (
  `id_fabricant` int NOT NULL,
  `nom_fabricant` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pays` varchar(46) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(54) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_web` varchar(72) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `certification` varchar(76) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_fabricant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fabricants`
--

INSERT INTO `fabricants` (`id_fabricant`, `nom_fabricant`, `pays`, `telephone`, `site_web`, `certification`) VALUES
(1, 'Arduino S.r.l.', 'Italie', '+39-0125-656535', 'www.arduino.cc', 'CE, RoHS, WEEE'),
(2, 'Raspberry Pi Ltd', 'Royaume-Uni', '+44-1223-763200', 'www.raspberrypi.com', 'CE, FCC, IC, RoHS'),
(3, 'Nichicon Corporation', 'Japon', '+81-75-321-3600', 'www.nichicon.co.jp', 'AEC-Q200, RoHS, ISO 9001'),
(4, 'Vishay Dale', 'USA', '+1-402-563-6866', 'www.vishay.com', 'AEC-Q200, RoHS, ISO/TS 16949'),
(5, 'Elecfreaks Technology Co.', 'Chine', '+86-755-82795492', 'www.elecfreaks.com', 'CE, RoHS, FCC'),
(6, 'STMicroelectronics N.V.', 'France/Italie', '+33-1-55-24-24-24', 'www.st.com', 'CE, RoHS, AEC-Q100, ISO 9001'),
(7, 'Texas Instruments Inc.', 'USA', '+1-972-995-3773', 'www.ti.com', 'AEC-Q100, RoHS, ISO/TS 16949'),
(8, 'Espressif Systems Co.', 'Chine', '+86-21-6132-5988', 'www.espressif.com', 'CE, FCC, RoHS, SRRC'),
(9, 'Murata Manufacturing Co.', 'Japon', '+81-75-955-6789', 'www.murata.com', 'AEC-Q200, RoHS, ISO 9001'),
(10, 'YAGEO Corporation', 'Taïwan', '+886-2-2270-8899', 'www.yageo.com', 'AEC-Q200, RoHS, ISO/TS 16949'),
(11, 'Infineon Technologies AG', 'Allemagne', '+49-89-234-0', 'www.infineon.com', 'AEC-Q100, RoHS, ISO 9001'),
(12, 'NXP Semiconductors N.V.', 'Pays-Bas', '+31-40-272-9999', 'www.nxp.com', 'AEC-Q100, RoHS, ISO/TS 16949'),
(13, 'Adafruit Industries LLC', 'USA', '+1-212-945-0311', 'www.adafruit.com', 'CE, FCC, RoHS'),
(14, 'DFRobot Co. Ltd', 'Chine', '+86-21-5126-8899', 'www.dfrobot.com', 'CE, FCC, RoHS'),
(15, 'TDK Corporation', 'Japon', '+81-3-6778-1055', 'www.tdk.com', 'AEC-Q200, RoHS, ISO 9001'),
(16, 'Microchip Technology Inc.', 'USA', '1-480-792-7200', 'www.microchip.com', 'AEC-Q100, RoHS, ISO/TS 16949'),
(17, 'ON Semiconductor (onsemi)', 'USA', '1-602-244-6600', 'www.onsemi.com', 'AEC-Q100, RoHS, ISO 9001'),
(18, 'Broadcom Inc.', 'USA', '+1-408-433-8000', 'www.broadcom.com', 'CE, FCC, RoHS, UL'),
(19, 'Samsung Electro-Mechanics', 'Corée du Sud', '+82-31-210-5114', 'www.samsungsem.com', 'AEC-Q200, RoHS, ISO/TS 16949'),
(20, 'Panasonic Industry Co.', 'Japon', '-7954', 'www.industry.panasonic.com', 'AEC-Q200, RoHS, ISO 9001'),
(21, 'u-blox AG', 'Suisse', '-8169', 'www.u-blox.com', 'CE, FCC, IC, RCM'),
(22, 'Simcom Wireless Solutions', 'Chine', '-6487', 'www.simcom.com', 'CE, FCC, RoHS, PTCRB');

-- --------------------------------------------------------

--
-- Structure de la table `familles`
--

DROP TABLE IF EXISTS `familles`;
CREATE TABLE IF NOT EXISTS `familles` (
  `id_famille` int NOT NULL,
  `nom_famille` varchar(110) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(220) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_famille`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `familles`
--

INSERT INTO `familles` (`id_famille`, `nom_famille`, `description`) VALUES
(1, 'Électronique analogique', 'Elle permet de traiter des signaux comme le son ou la tension électrique de manière continue.'),
(2, 'Électronique numérique', 'Travaille avec des signaux binaires (0 et 1),Elle est utilisée dans tous les systèmes informatiques.'),
(3, 'Électronique de puissance', 'Gère les fortes puissances électriques,Elle contrôle l’énergie électrique à grande puissance.'),
(4, 'Électronique de communication', 'Permet la transmission de l’information,Elle transmet les données sur de longues distances.'),
(5, 'Systèmes embarqués', 'Systèmes électroniques intégrés dans des objets pour les contrôler'),
(6, 'Microélectronique', 'Elle s’occupe de la fabrication des composants miniatures.'),
(7, 'Électronique industrielle', 'Elle contrôle les machines dans les industries'),
(8, 'Électronique automobile', 'Spécialisée dans les systèmes électroniques des véhicules'),
(9, 'Électronique médicale', 'Utilisée dans les équipements médicaux.'),
(10, 'Électronique aéronautique', 'Utilisée dans les avions et systèmes de vol'),
(11, 'Électronique militaire', 'Spécialisée dans les systèmes de défense.'),
(12, 'Électronique domotique', 'Contrôle automatique des maisons.'),
(13, 'Électronique industrielle avancée', 'Utilisée dans les usines modernes automatisées'),
(14, 'Électronique de télécommunication spécialisée', 'Plus avancée que la communication simple.');

-- --------------------------------------------------------

--
-- Structure de la table `favoris`
--

DROP TABLE IF EXISTS `favoris`;
CREATE TABLE IF NOT EXISTS `favoris` (
  `id_favori` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_produit` int NOT NULL,
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_favori`),
  UNIQUE KEY `uniq_user_produit` (`id_utilisateur`,`id_produit`),
  KEY `idx_user` (`id_utilisateur`),
  KEY `idx_produit` (`id_produit`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `favoris`
--

INSERT INTO `favoris` (`id_favori`, `id_utilisateur`, `id_produit`, `date_ajout`) VALUES
(37, 2, 18, '2026-05-20 10:23:47'),
(38, 2, 17, '2026-05-20 10:23:49');

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

DROP TABLE IF EXISTS `fournisseurs`;
CREATE TABLE IF NOT EXISTS `fournisseurs` (
  `id_fournisseur` int NOT NULL,
  `nom_fournisseur` varchar(68) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(52) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_web` varchar(56) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `delai_livraison_j` int NOT NULL,
  `statut` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_fournisseur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id_fournisseur`, `nom_fournisseur`, `telephone`, `email`, `site_web`, `delai_livraison_j`, `statut`) VALUES
(1, 'Mouser Electronics', '+1-800-346-6873', NULL, 'www.mouser.com', 7, 'actif'),
(2, 'RS Components', '+44-1536-444000', NULL, 'www.rs-online.com', 5, 'actif'),
(3, 'Digi-Key', '+1-218-681-6674', NULL, 'www.digikey.com', 7, 'actif'),
(4, 'AliExpress', '+86-571-26888888', NULL, 'www.aliexpress.com', 21, 'actif'),
(5, 'Conrad Electronic', '+49-9604-40-0', NULL, 'www.conrad.com', 10, 'actif'),
(6, 'Farnell (Avnet)', '+44-113-263-6311', NULL, 'www.farnell.com', 6, 'actif'),
(7, 'Arrow Electronics', '1-303-824-4000', NULL, 'www.arrow.com', 8, 'actif'),
(8, 'TME (Transfer Multisort)', '48-42-645-55-55', NULL, 'www.tme.eu', 9, 'actif'),
(9, 'TE Connectivity', NULL, NULL, 'www.te.com', 12, 'actif'),
(10, 'Souq Mauritanie (local)', '222 22 55 44 33', NULL, 'www.souq-mr.com', 3, 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `livraisons`
--

DROP TABLE IF EXISTS `livraisons`;
CREATE TABLE IF NOT EXISTS `livraisons` (
  `id_livraison` int NOT NULL,
  `id_utilisateur` int DEFAULT NULL,
  `id_commande` int DEFAULT NULL,
  `adresse_livraison` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transporteur` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_suivi` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_expedition` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_livraison_prevue` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_livraison_reelle` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_livraison` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_livraison`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `marques`
--

DROP TABLE IF EXISTS `marques`;
CREATE TABLE IF NOT EXISTS `marques` (
  `id_marque` int NOT NULL,
  `nom_marque` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_fabricant` int NOT NULL,
  `site_web` varchar(72) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_marque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `marques`
--

INSERT INTO `marques` (`id_marque`, `nom_marque`, `id_fabricant`, `site_web`) VALUES
(1, 'Arduino', 1, 'www.arduino.cc'),
(2, 'Raspberry Pi Foundation', 2, 'www.raspberrypi.org'),
(3, 'Vishay Intertechnology', 4, 'www.vishay.com'),
(4, 'Nichicon', 3, 'www.nichicon.co.jp'),
(5, 'Elecfreaks', 5, 'www.elecfreaks.com'),
(6, 'STM / ST', 6, 'www.st.com'),
(7, 'Texas Instruments', 7, 'www.ti.com'),
(8, 'Espressif / ESP', 8, 'www.espressif.com'),
(9, 'Murata', 9, 'www.murata.com'),
(10, 'YAGEO', 10, 'www.yageo.com'),
(11, 'Infineon', 11, 'www.infineon.com'),
(12, 'NXP', 12, 'www.nxp.com'),
(13, 'Adafruit', 13, 'www.adafruit.com'),
(14, 'DFRobot', 14, 'www.dfrobot.com'),
(15, 'TDK', 15, 'www.tdk.com'),
(16, 'Microchip', 16, 'www.microchip.com'),
(17, 'onsemi', 17, 'www.onsemi.com'),
(18, 'Broadcom', 18, 'www.broadcom.com'),
(19, 'Samsung Electro-Mechanics', 19, 'www.samsungsem.com'),
(20, 'Panasonic', 20, 'www.industry.panasonic.com'),
(21, 'u-blox', 21, 'www.u-blox.com'),
(22, 'SIMCom', 22, 'www.simcom.com');

-- --------------------------------------------------------

--
-- Structure de la table `materiaux`
--

DROP TABLE IF EXISTS `materiaux`;
CREATE TABLE IF NOT EXISTS `materiaux` (
  `id_materiau` int NOT NULL,
  `nom_materiau` varchar(72) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(198) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_materiau`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `materiaux`
--

INSERT INTO `materiaux` (`id_materiau`, `nom_materiau`, `description`) VALUES
(1, 'ATmega328P', 'MCU 8-bit AVR 16MHz, 32Ko Flash, 2Ko SRAM — Microchip Technology (USA)'),
(2, 'PCB FR4', 'Stratifié époxy-verre ignifugé 1.6mm, cuivre 35µm, Tg 135°C — Taïwan/Chine'),
(3, 'BCM2711', 'SoC Broadcom quad-core Cortex-A72 1.5GHz, GPU VideoCore VI — Broadcom (USA)'),
(4, 'Électrolyte aluminium', 'Électrolyte liquide éthylène glycol stable 105°C — Nichicon série UWT (Japon)'),
(5, 'Couche résistive NiCr', 'Film nickel-chrome sur substrat alumine Al₂O₃, ±1% — Vishay Dale (USA)'),
(6, 'SoC ESP32-D0WD', 'Dual-core Xtensa LX6 240MHz, 520Ko SRAM intégré — Espressif Systems (Chine)'),
(7, 'InGaAlP LED chip', 'Puce LED InGaAlP rouge 625nm, Kingbright (Taïwan)'),
(8, 'Triac BTA16', 'Triac 16A 600V, isolation galvanique relais électromécanique'),
(9, 'Pilote SSD1306', 'Contrôleur OLED 128×64, I2C/SPI, 256 octets RAM affichage — Solomon Systech (HK)'),
(10, 'Bobine ferrite toroïdale', 'Noyau ferrite Mn-Zn torique, µ=2000, Irms=1A, inductance 100µH — TDK (Japon)'),
(11, 'Quartz 16MHz HC-49S', 'Résonateur quartz 16.000MHz, CL=18pF, tolérance ±30ppm — Abracon (USA)'),
(12, 'Régulateur LDO AMS1117-3.3', 'LDO 3.3V/1A, dropout 1.2V, protection thermique — Advanced Monolithic Systems (USA)'),
(13, 'Flash SPI W25Q32', 'Mémoire Flash NOR SPI 32Mbit (4Mo), 104MHz, 3.3V — Winbond (Taïwan)'),
(14, 'Transistor NPN BC547', 'Transistor BJT NPN TO-92, Vce=45V, Ic=100mA, hFE=110-800 — ON Semiconductor (USA)'),
(15, 'Diode Schottky 1N5819', 'Diode Schottky DO-41, Vr=40V, If=1A, Vf=0.6V — Vishay (USA)'),
(16, 'Connecteur USB-B femelle', 'Prise USB type B montage PCB, courant max 1.5A, 500V — Amphenol (USA)'),
(17, 'Optocoupleur PC817', 'Optocoupleur DIP-4, CTR 50-300%, Viso=5000Vrms — Sharp (Japon)'),
(18, 'Régulateur 7805 TO-220', 'Régulateur linéaire +5V 1A, Vin max 35V, dissipateur requis — STMicroelectronics (France)'),
(19, 'SoC RP2040', 'Dual-core Cortex-M0+ 133MHz, 264Ko SRAM, PIO — Raspberry Pi (UK/TSMC)'),
(20, 'ARM Cortex-M3 STM32F103', '72MHz, 64Ko Flash, 20Ko SRAM, USB/CAN — STMicroelectronics (France)'),
(21, 'Capteur CMOS DHT22', 'Condensateur capacitif humidité + thermorésistance — ASAIR (Chine)'),
(22, 'Capteur gaz SnO2 MQ-2', 'Couche sensible SnO2 sur céramique chauffante 0.9W — Hanwei (Chine)'),
(23, 'Récepteur GPS MT3329', 'Chipset GPS MT3329, 66 canaux, 1Hz à 10Hz, -165dBm — MediaTek (Taïwan)'),
(24, 'Modem GSM Simcom SIM800', 'SIM800, quadri-bande 850/900/1800/1900 MHz, GPRS Classe 10 — SIMCom (Chine)'),
(25, 'MLCC X7R 100nF', 'Condensateur céramique multicouche BaTiO3, X7R, 50V — Samsung Electro-Mechanics (Corée)');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE IF NOT EXISTS `paiements` (
  `id_paiement` int NOT NULL AUTO_INCREMENT,
  `id_commande` int DEFAULT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `montant_MRU` decimal(10,2) DEFAULT NULL,
  `methode_paiement` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'sur_place',
  `statut_paiement` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `reference_transaction` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capture_paiement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_paiement_soumis` datetime DEFAULT NULL,
  PRIMARY KEY (`id_paiement`),
  KEY `fk_paiements_commande` (`id_commande`),
  KEY `idx_statut_paiement` (`statut_paiement`),
  KEY `idx_methode_paiement` (`methode_paiement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

DROP TABLE IF EXISTS `parametres`;
CREATE TABLE IF NOT EXISTS `parametres` (
  `cle` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`cle`, `valeur`) VALUES
('nom_destinataire', 'ElectroComposants'),
('numero_bancaire', '22226545862'),
('numero_whatsapp', '22226545762');

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

DROP TABLE IF EXISTS `produit`;
CREATE TABLE IF NOT EXISTS `produit` (
  `id_produit` int NOT NULL,
  `nom_produit` varchar(102) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(214) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_categorie` int NOT NULL,
  `id_type_produit` int NOT NULL,
  `id_marque` int NOT NULL,
  `id_fournisseur` int NOT NULL,
  `prix_unitaire_MRU` decimal(10,2) NOT NULL,
  `stock_disponible` int NOT NULL,
  `seuil_alerte_stock` int NOT NULL,
  `reference_SKU` varchar(56) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conformite_rohs` varchar(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'default.jpg',
  PRIMARY KEY (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id_produit`, `nom_produit`, `description`, `id_categorie`, `id_type_produit`, `id_marque`, `id_fournisseur`, `prix_unitaire_MRU`, `stock_disponible`, `seuil_alerte_stock`, `reference_SKU`, `statut`, `conformite_rohs`, `image`) VALUES
(1, 'Arduino Uno R3', 'Microcontrôleur ATmega328P 16MHz, 32Ko Flash, 14 GPIO, 6 PWM, 6 entrées analogiques, USB-B', 1, 1, 1, 1, 250.00, 0, 10, 'A000066', 'disponible', 'non', '1.jpg'),
(2, 'Raspberry Pi 4 Model B 4Go', 'SBC BCM2711 quad-core Cortex-A72 1.5GHz, 4Go LPDDR4, WiFi 5, BT 5.0, 2×USB 3.0, 2×micro-HDMI 4K', 1, 2, 2, 2, 1850.00, 30, 5, 'SC0194', 'disponible', 'oui', '2.jpg'),
(3, 'Condensateur Nichicon UWT 100µF 25V', 'Condensateur électrolytique Al, 100µF/25V, 105°C, 2000h, pas 2.5mm', 2, 3, 4, 3, 2.50, 500, 50, 'UWT1E101MDD', 'disponible', 'oui', '3.jpg'),
(4, 'Résistance Vishay MRS25 10kΩ 1% (lot 100)', 'Film métal MRS25, 10kΩ ±1%, 0.25W, TK ±50ppm/°C, RoHS — lot 100 pièces', 2, 4, 3, 1, 28.00, 200, 20, 'MRS25000C1002FCT00', 'disponible', 'oui', '4.jpg'),
(5, 'Capteur ultrasonique HC-SR04', 'Mesure de distance par ultrasons 2–400cm, précision ±3mm, 5V TTL, 4 broches', 3, 5, 5, 4, 65.00, 120, 15, 'HC-SR04', 'disponible', 'oui', '5.jpg'),
(6, 'ESP32 DevKit V1', 'SoC Xtensa LX6 dual-core 240MHz, 520Ko SRAM, WiFi 2.4GHz 802.11b/g/n, BT 4.2, 34 GPIO, ADC 12-bit', 1, 6, 6, 4, 185.00, 60, 8, 'ESP32-DEVKIT-V1', 'disponible', 'oui', '6.jpg'),
(7, 'LED 5mm Rouge', 'LED 5mm InGaAlP rouge 625nm, 2V, 20mA, 1200mcd, angle 30°', 2, 7, 7, 4, 1.50, 1000, 100, 'KP-2012SRC-PRV', 'disponible', 'oui', '7.jpg'),
(8, 'Module relais 5V 2 canaux', 'Module 2 relais 5V, contacts 10A/250VAC, isolation optique, déclenchement niveau bas', 4, 8, 8, 4, 45.00, 75, 10, 'SRD-05VDC-SL-C-2CH', 'disponible', 'oui', '8.jpg'),
(9, 'Régulateur LM7805 TO-220', 'Régulateur linéaire +5V, 1.5A, boîtier TO-220, protection court-circuit et thermique', 5, 9, 9, 1, 12.00, 150, 20, 'LM7805CT', 'disponible', 'oui', '9.jpg'),
(10, 'Écran OLED 0.96\" I2C SSD1306', 'Afficheur OLED 128×64 px, interface I2C, contrôleur SSD1306, 3.3V–5V, angle vision 160°', 1, 10, 10, 4, 95.00, 45, 10, 'SSD1306-096-I2C', 'disponible', 'oui', '10.jpg'),
(11, 'Kit résistances 600 pièces', 'Assortiment 30 valeurs × 20 pcs, 1/4W ±5%, gamme 10Ω–1MΩ, boîte compartimentée', 2, 11, 3, 4, 55.00, 80, 10, 'KIT-RES-600-E12', 'disponible', 'oui', '11.jpg'),
(12, 'Transistor BC547 NPN (lot 20)', 'Transistor bipolaire NPN TO-92, Vceo 45V, Ic 100mA, hFE 110–800, ft 300MHz — lot 20', 2, 12, 12, 4, 18.00, 300, 30, 'BC547B-TO92-20', 'disponible', 'oui', '12.jpg'),
(13, 'Diode 1N4007 (lot 50)', 'Diode redresseuse 1A 1000V, boîtier DO-41, temps rétablissement 30µs — lot 50 pièces', 2, 13, 12, 3, 14.00, 400, 50, '1N4007-DO41-50', 'disponible', 'oui', '13.jpg'),
(14, 'Module Bluetooth HC-05', 'Module Bluetooth 2.0+EDR, UART 9600–115200 bds, portée 10m, configuration AT, 3.3V–5V', 1, 14, 14, 4, 120.00, 40, 8, 'HC-05-BT-UART', 'disponible', 'oui', '14.jpg'),
(15, 'Capteur température DS18B20', 'Capteur numérique 1-Wire, plage −55 à +125°C, précision ±0.5°C, boîtier TO-92, 3.0–5.5V', 3, 15, 13, 3, 35.00, 89, 15, 'DS18B20-TO92', 'disponible', 'oui', '15.jpg'),
(16, 'Module carte SD SPI', 'Interface SPI pour carte micro-SD, niveaux 3.3V/5V, CS/SCK/MOSI/MISO, bibliothèque Arduino', 1, 16, 10, 4, 28.00, 60, 10, 'SD-SPI-MODULE-V2', 'disponible', 'oui', '16.jpg'),
(17, 'Inductance 100µH 1A (lot 10)', 'Inductance traversante 100µH ±10%, courant 1A, DCR 0.5Ω, boîtier axial — lot 10 pièces', 2, 17, 15, 5, 22.00, 198, 25, 'LHL10NB101K-10', 'disponible', 'oui', '17.jpg'),
(18, 'Buzzer piézoélectrique 5V', 'Buzzer piézo actif 5V, fréquence 2.3kHz, SPL 85dB, diamètre 12mm, boîtier traversant', 4, 18, 10, 4, 8.00, 118, 20, 'BZ-12-5V-PIEZO', 'disponible', 'oui', '18.jpg'),
(19, 'Alimentation 5V 2A (adaptateur)', 'Adaptateur secteur 100–240VAC → 5VDC 2A, connecteur USB-A, protection surtension, CE RoHS', 5, 19, 10, 5, 85.00, 35, 5, 'PSU-5V2A-USB-A', 'disponible', 'oui', '19.jpg'),
(20, 'Multimètre numérique DT-830B', 'Multimètre portable 3½ digits, AC/DC tension, courant, résistance, test diode, buzzer continuité', 6, 20, 10, 4, 180.00, 20, 3, 'DT-830B-DMM', 'disponible', 'non', '20.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `produit_materiau`
--

DROP TABLE IF EXISTS `produit_materiau`;
CREATE TABLE IF NOT EXISTS `produit_materiau` (
  `id_produit` int NOT NULL,
  `id_materiau` int NOT NULL,
  `role_dans_produit` varchar(74) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_produit`,`id_materiau`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produit_materiau`
--

INSERT INTO `produit_materiau` (`id_produit`, `id_materiau`, `role_dans_produit`) VALUES
(1, 1, 'Microcontrôleur principal'),
(1, 2, 'Substrat circuit imprimé'),
(1, 16, 'Connecteur USB-B'),
(2, 2, 'Substrat circuit imprimé'),
(2, 3, 'Processeur / SoC principal'),
(3, 4, 'Électrolyte du condensateur'),
(4, 5, 'Élément résistif'),
(5, 2, 'Substrat circuit imprimé'),
(6, 2, 'Substrat circuit imprimé'),
(6, 6, 'SoC WiFi/BT principal'),
(6, 11, 'Quartz horloge 16MHz'),
(6, 13, 'Mémoire Flash programme'),
(7, 7, 'Source lumineuse LED'),
(8, 8, 'Composant de commutation'),
(8, 17, 'Isolation optique relais'),
(9, 18, 'Élément régulateur TO-220'),
(10, 2, 'Substrat de l\'afficheur'),
(10, 9, 'Contrôleur afficheur OLED'),
(11, 5, 'Élément résistif film métal'),
(12, 2, 'Substrat boîtier TO-92'),
(12, 14, 'Jonction transistor NPN'),
(13, 15, 'Jonction Schottky'),
(14, 2, 'Substrat circuit imprimé'),
(14, 6, 'Module RF Bluetooth'),
(15, 1, 'Capteur température (puce)'),
(17, 10, 'Noyau inductif toroïdal'),
(19, 12, 'Régulateur LDO sortie 3.3V');

-- --------------------------------------------------------

--
-- Structure de la table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
CREATE TABLE IF NOT EXISTS `promotions` (
  `id_promo` int NOT NULL,
  `code_promo` varchar(44) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(112) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_categorie` int DEFAULT NULL,
  `type_remise` varchar(56) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur_remise` int NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `min_commande_MRU` int NOT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `statut` varchar(34) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_promo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `types_produits`
--

DROP TABLE IF EXISTS `types_produits`;
CREATE TABLE IF NOT EXISTS `types_produits` (
  `id_type` int NOT NULL,
  `nom_type` varchar(78) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(122) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_categorie` int NOT NULL,
  PRIMARY KEY (`id_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `types_produits`
--

INSERT INTO `types_produits` (`id_type`, `nom_type`, `description`, `id_categorie`) VALUES
(1, 'Carte Microcontrôleur', 'Cartes à microcontrôleur programmable AVR, ARM, ESP', 1),
(2, 'Single Board Computer', 'Ordinateur mono-carte sous Linux ou Android', 1),
(3, 'Condensateur', 'Composant passif stockant l\'énergie électrique', 2),
(4, 'Résistance', 'Composant passif limitant le courant électrique', 2),
(5, 'Capteur Ultrasonique', 'Mesure de distance par réflexion d\'ultrasons', 3),
(6, 'Module Wi-Fi/BT SoC', 'SoC intégrant radio Wi-Fi+BT: ESP32, ESP8266', 8),
(7, 'LED traversante', 'LED à travers trou 3mm / 5mm', 2),
(8, 'Module relais', 'Isolation et commutation haute tension', 4),
(9, 'Régulateur linéaire', 'Régulateur à dissipation (7805, LM317)', 5),
(10, 'Écran OLED', 'Afficheur OLED I2C/SPI monochrome', 10),
(11, 'Kit résistances', 'Assortiments de résistances multi-valeurs', 9),
(12, 'Transistor BJT', 'Transistors bipolaires NPN/PNP', 1),
(13, 'Diode rectification', 'Diodes 1N4007, 1N5408 basse fréquence', 2),
(14, 'Module Bluetooth classique', 'Modules BT série: HC-05, HC-06', 8),
(15, 'Capteur température numérique', '1-Wire ou I2C: DS18B20, DHT22', 3),
(16, 'Module carte SD', 'Interface SPI pour stockage micro-SD', 11),
(17, 'Inductance traversante', 'Bobines axiales/radiales pour filtrage', 2),
(18, 'Buzzer', 'Buzzer piézo actif/passif', 4),
(19, 'Alimentation secteur', 'Adaptateurs AC→DC USB/Jack', 5),
(20, 'Multimètre', 'Appareils de mesure électrique portables', 6),
(21, 'Amplificateur opérationnel', 'AO: LM358, LM741, TL072', 7),
(22, 'Timer 555', 'Circuit intégré timer universel NE555', 7),
(23, 'Convertisseur DC-DC', 'Buck/Boost: LM2596, XL6009', 5),
(24, 'Capteur de luminosité', 'Photorésistances LDR, capteurs BH1750', 3),
(25, 'Capteur de gaz', 'MQ-2, MQ-135 pour détection fumée/CO2', 3),
(26, 'Servomoteur', 'Servo RC: SG90 (micro), MG996 (métal)', 4),
(27, 'Moteur pas-à-pas', 'Stepper NEMA17, 28BYJ-48', 4),
(28, 'LCD alphanumérique', 'Écrans 16×2 et 20×4 avec HD44780', 10),
(29, 'Module GPS', 'GPS NEO-6M/NEO-8M UART', 8),
(30, 'Module GSM/4G', 'SIM800L, SIM7600 pour IoT cellulaire', 8);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id_utilisateur` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mot_de_pass` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pays` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','client','vendeur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_inscription` datetime DEFAULT NULL,
  `statut` enum('actif','inactif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom`, `prenom`, `email`, `telephone`, `mot_de_pass`, `adresse`, `ville`, `pays`, `role`, `date_inscription`, `statut`) VALUES
(1, 'fatimetou', 'tacky', 'tackyfatimetou@gmail.com', '+22226545762', '$2y$10$4uXqRgEc1HWXFzA6Akx4cuESiNY/yqGOLq1VttElkD52ljIDz6Q/6', '', '', 'Mauritanie', 'admin', '2026-05-16 19:50:40', 'actif'),
(2, 'Admin', 'Principal', 'admin@electrocomposants.mr', '+22200000000', '$2y$10$6ywX1y4bMG8roD.vCfBNtOSX9PPiVV5FPj7unXP46mNfqoq0Buu0S', '', 'Nouakchott', 'Mauritanie', 'admin', '2026-05-17 21:37:23', 'actif'),
(3, 'user_', 'F', '25126@supnum.mr', '', '$2y$10$OpmasA5Piasnp5686iGkn.aRWZwwJGVU5ju9iuxHvgsbQ/PJCT6ua', '', '', 'Mauritanie', 'client', '2026-05-18 02:39:23', 'actif'),
(4, 'Mariem', 'Hadrami', 'mariem@gmail.com', '+222 44672289', '$2y$10$EicMMMlj3P3SlCny94sJEen3c0LddZqCkk5jSBpOa51vt95oAoY8O', '', '', 'Mauritanie', 'client', '2026-05-18 10:44:29', 'actif');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `fk_paiements_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
