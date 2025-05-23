-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Mag 23, 2025
-- Versione del server: 10.4.28-MariaDB
-- Versione PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toroller_semplificato`
--
CREATE DATABASE IF NOT EXISTS `toroller_semplificato` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `toroller_semplificato`;

-- --------------------------------------------------------

--
-- Struttura della tabella `servizi` (precedentemente `prodotti`)
--
CREATE TABLE `servizi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `servizi` (esempio, da adattare o rimuovere)
--
INSERT INTO `servizi` (`id`, `nome`, `categoria`, `descrizione`) VALUES
(1, 'Consulenza Base', 'Consulenza', 'Servizio di consulenza base per nuovi utenti.'),
(2, 'Lezione di Pattinaggio', 'Lezioni', 'Lezione individuale di pattinaggio freestyle.');

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--
CREATE TABLE `utente` (
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `amministratore` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente` (esempio, da adattare)
--
INSERT INTO `utente` (`email`, `username`, `nome`, `cognome`, `password`, `amministratore`) VALUES
('admin@example.com', 'admin', 'Admin', 'User', '$2y$10$QLLaKB3zBu8OXszr8.K9QuhEfb7zHY91iN/KMSK2Gr42I7PDPUNgC', 1),
('user@example.com', 'testuser', 'Test', 'User', '$2y$10$PlRoa1ssw28WTZxCx9MvSeizMU9GOQlIlUj8tksEF8DmkkgtkPuDa', 0);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `servizi`
--
-- ALTER TABLE `servizi` ADD PRIMARY KEY (`id`); (Già definito nella CREATE TABLE)

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `servizi`
--
-- ALTER TABLE `servizi` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3; (Già definito nella CREATE TABLE)

--
-- Non ci sono più foreign key constraints da definire con solo queste due tabelle.
--

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
