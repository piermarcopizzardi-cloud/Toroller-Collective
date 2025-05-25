-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Mag 25, 2025 alle 13:25
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

-- --------------------------------------------------------

--
-- Struttura della tabella `servizi`
--

CREATE TABLE `servizi` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `descrizione` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `servizi`
--

INSERT INTO `servizi` (`id`, `nome`, `categoria`, `descrizione`) VALUES
(3, 'Consulenza Web Design', 'Web', 'Offriamo consulenza specializzata per la progettazione e il restyling del tuo sito web, focalizzandoci su usabilità e impatto visivo.'),
(4, 'Sviluppo E-commerce Completo', 'Web', 'Realizziamo piattaforme e-commerce personalizzate, integrate con sistemi di pagamento sicuri e ottimizzate per la conversione.'),
(5, 'Ottimizzazione SEO Avanzata', 'Marketing', 'Miglioriamo il posizionamento del tuo sito sui motori di ricerca attraverso strategie SEO on-page e off-page mirate.'),
(6, 'Gestione Campagne Social Media', 'Marketing', 'Curiamo la tua presenza sui social media, dalla creazione di contenuti ingaggianti alla gestione di campagne pubblicitarie.'),
(7, 'Creazione Loghi e Identità Visiva', 'Design', 'Progettiamo loghi memorabili e sviluppiamo un\'identità visiva coordinata che rappresenti al meglio il tuo brand.'),
(8, 'Sviluppo Applicazioni Mobili Native', 'Mobile', 'Sviluppiamo applicazioni mobili native per iOS e Android, offrendo performance elevate e un\'esperienza utente fluida.'),
(9, 'Servizio di Manutenzione Web Proattiva', 'Web', 'Garantiamo il corretto funzionamento e la sicurezza del tuo sito web con piani di manutenzione proattiva e supporto tecnico.'),
(10, 'Redazione Contenuti Web Ottimizzati', 'Marketing', 'Creiamo contenuti testuali di alta qualità, ottimizzati per i motori di ricerca e capaci di attrarre e convertire il tuo target.'),
(11, 'Progettazione UI/UX Centrata sull\'Utente', 'Design', 'Disegniamo interfacce utente intuitive e esperienze utente coinvolgenti, basate su analisi e test approfonditi.'),
(12, 'Corsi di Formazione IT Personalizzati', 'Formazione', 'Offriamo corsi di formazione su misura per aziende e privati, coprendo una vasta gamma di software e linguaggi di programmazione.');

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
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`email`, `username`, `nome`, `cognome`, `password`, `amministratore`) VALUES
('g@gmail.com', 'g', 'g', 'g', '$2y$10$vgzsYCIwa3JgSId0WGnYIOPomwm4FGVS2VNcGZZrcPtgfNkEA5SaC', 1),
('test@gmail.com', 'test', 'test', 'test', '$2y$10$uHd2FPac8U661aRPr/JymuaF.PDcQ1UplnFRJgXR4bDp.7jsZBqLy', 0);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `servizi`
--
ALTER TABLE `servizi`
  ADD PRIMARY KEY (`id`);

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
ALTER TABLE `servizi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
