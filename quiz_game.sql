-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 26 mars 2026 à 17:53
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `quiz_game`
--

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--
-- Création : mer. 25 mars 2026 à 18:25
-- Dernière modification : jeu. 26 mars 2026 à 16:03

--
--#################### seulement pour montrer la structure de ma bd mais les vraies donnees sont 
   ailleiurs 

CREATE TABLE `clients` (
  `id_cli` int(11) NOT NULL,
  `pseudo` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mdp` varchar(255) NOT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('actif','banni') DEFAULT 'actif',
  `email_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONS POUR LA TABLE `clients`:
--

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id_cli`, `pseudo`, `email`, `mdp`, `date_inscription`, `last_login`, `status`, `email_verified`) VALUES
(1, 'admin2', 'zainirran932@gmail.com', '$2y$10$Dj9GeQkjPGJz8R3TdhKFlOciPESCsh4mk24SqfHDTD/SXmYWrTRkG', '2026-03-25 18:38:12', NULL, 'actif', 0),
(11, 'francklin', 'tadylan4@gmail.com', '$2y$10$PAQ3EjucCofHR6RBpFdsDuuQm1x3Y0qHFcpIPykTVt5sYUOI2JK7y', '2026-03-26 13:28:03', NULL, 'actif', 0),
(15, 'admin69', 'i001c.2425@gmail.com', '$2y$10$UdlWTG7OCmTgfuSM1lxm/eWNIVuyx7M0l/ViBoGByS5OONitVsZdm', '2026-03-26 14:25:24', '2026-03-26 16:03:20', 'actif', 1);

-- --------------------------------------------------------

--
-- Structure de la table `devices`
--
-- Création : mer. 25 mars 2026 à 18:25
--

CREATE TABLE `devices` (
  `id_device` int(11) NOT NULL,
  `id_cli` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `navigateur` varchar(100) DEFAULT NULL,
  `systeme` varchar(100) DEFAULT NULL,
  `type_appareil` enum('pc','mobile') DEFAULT 'pc',
  `date_connexion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONS POUR LA TABLE `devices`:
--   `id_cli`
--       `clients` -> `id_cli`
--

-- --------------------------------------------------------

--
-- Structure de la table `email_confirmations`
--
-- Création : mer. 25 mars 2026 à 18:34
-- Dernière modification : jeu. 26 mars 2026 à 14:26
--

CREATE TABLE `email_confirmations` (
  `id_confirmation` int(11) NOT NULL,
  `id_cli` int(11) NOT NULL,
  `code` char(6) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `est_valide` tinyint(1) DEFAULT 1,
  `expire` timestamp NOT NULL DEFAULT (current_timestamp() + interval 24 hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONS POUR LA TABLE `email_confirmations`:
--   `id_cli`
--       `clients` -> `id_cli`
--

--
-- Déchargement des données de la table `email_confirmations`
--

INSERT INTO `email_confirmations` (`id_confirmation`, `id_cli`, `code`, `date_creation`, `est_valide`, `expire`) VALUES
(1, 1, '781025', '2026-03-25 18:38:12', 1, '2026-03-26 18:38:12'),
(11, 11, '800152', '2026-03-26 13:28:03', 1, '2026-03-27 13:28:03'),
(15, 15, '948823', '2026-03-26 14:25:24', 0, '2026-03-27 14:25:24');

-- --------------------------------------------------------

--
-- Structure de la table `questions`
--
-- Création : jeu. 26 mars 2026 à 14:53
--

CREATE TABLE `questions` (
  `id_question` int(11) NOT NULL,
  `id_session` int(11) NOT NULL,
  `theme` varchar(50) NOT NULL,
  `difficulte` enum('facile','moyen','difficile') NOT NULL,
  `question` text NOT NULL,
  `reponse_correcte` text NOT NULL,
  `reponses_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reponses_options`)),
  `source` enum('manual','ia') NOT NULL DEFAULT 'ia',
  `ordre` int(11) NOT NULL,
  `temps_alloue` int(11) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONS POUR LA TABLE `questions`:
--

-- --------------------------------------------------------

--
-- Structure de la table `reponses`
--
-- Création : jeu. 26 mars 2026 à 14:54
--

CREATE TABLE `reponses` (
  `id_reponse` int(11) NOT NULL,
  `id_question` int(11) NOT NULL,
  `id_cli` int(11) NOT NULL,
  `reponse` text NOT NULL,
  `est_correct` tinyint(1) DEFAULT 0,
  `temps_pris` int(11) DEFAULT 0,
  `date_reponse` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONS POUR LA TABLE `reponses`:
--   `id_question`
--       `questions` -> `id_question`
--   `id_cli`
--       `clients` -> `id_cli`
--

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--
-- Création : jeu. 26 mars 2026 à 14:54
--

CREATE TABLE `sessions` (
  `id_session` int(11) NOT NULL,
  `id_cli` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `nb_questions` int(11) NOT NULL,
  `difficulte` enum('facile','moyen','difficile') DEFAULT 'facile',
  `temps_jeu` int(11) DEFAULT 0,
  `date_session` timestamp NOT NULL DEFAULT current_timestamp(),
  `temps_par_question` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONS POUR LA TABLE `sessions`:
--   `id_cli`
--       `clients` -> `id_cli`
--

-- --------------------------------------------------------

--
-- Structure de la table `stats_globales`
--
-- Création : mer. 25 mars 2026 à 18:25
--

CREATE TABLE `stats_globales` (
  `id_stat` int(11) NOT NULL,
  `id_cli` int(11) DEFAULT NULL,
  `total_parties` int(11) DEFAULT 0,
  `total_questions` int(11) DEFAULT 0,
  `temps_total` int(11) DEFAULT 0,
  `score_moyen` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONS POUR LA TABLE `stats_globales`:
--   `id_cli`
--       `clients` -> `id_cli`
--

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id_cli`),
  ADD UNIQUE KEY `pseudo` (`pseudo`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id_device`),
  ADD KEY `idx_devices_cli` (`id_cli`);

--
-- Index pour la table `email_confirmations`
--
ALTER TABLE `email_confirmations`
  ADD PRIMARY KEY (`id_confirmation`),
  ADD KEY `idx_email_confirmations_cli` (`id_cli`);

--
-- Index pour la table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id_question`);

--
-- Index pour la table `reponses`
--
ALTER TABLE `reponses`
  ADD PRIMARY KEY (`id_reponse`),
  ADD KEY `id_question` (`id_question`),
  ADD KEY `id_cli` (`id_cli`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id_session`),
  ADD KEY `idx_sessions_cli` (`id_cli`);

--
-- Index pour la table `stats_globales`
--
ALTER TABLE `stats_globales`
  ADD PRIMARY KEY (`id_stat`),
  ADD UNIQUE KEY `id_cli` (`id_cli`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id_cli` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `devices`
--
ALTER TABLE `devices`
  MODIFY `id_device` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `email_confirmations`
--
ALTER TABLE `email_confirmations`
  MODIFY `id_confirmation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `questions`
--
ALTER TABLE `questions`
  MODIFY `id_question` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reponses`
--
ALTER TABLE `reponses`
  MODIFY `id_reponse` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id_session` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `stats_globales`
--
ALTER TABLE `stats_globales`
  MODIFY `id_stat` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`id_cli`) REFERENCES `clients` (`id_cli`) ON DELETE CASCADE;

--
-- Contraintes pour la table `email_confirmations`
--
ALTER TABLE `email_confirmations`
  ADD CONSTRAINT `email_confirmations_ibfk_1` FOREIGN KEY (`id_cli`) REFERENCES `clients` (`id_cli`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reponses`
--
ALTER TABLE `reponses`
  ADD CONSTRAINT `reponses_ibfk_1` FOREIGN KEY (`id_question`) REFERENCES `questions` (`id_question`) ON DELETE CASCADE,
  ADD CONSTRAINT `reponses_ibfk_2` FOREIGN KEY (`id_cli`) REFERENCES `clients` (`id_cli`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`id_cli`) REFERENCES `clients` (`id_cli`) ON DELETE CASCADE;

--
-- Contraintes pour la table `stats_globales`
--
ALTER TABLE `stats_globales`
  ADD CONSTRAINT `stats_globales_ibfk_1` FOREIGN KEY (`id_cli`) REFERENCES `clients` (`id_cli`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
