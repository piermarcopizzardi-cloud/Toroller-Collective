-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Mag 21, 2025 alle 16:58
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
-- Database: `toroller`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `carrello`
--

CREATE TABLE `carrello` (
  `id` int(11) NOT NULL,
  `email_utente` varchar(100) NOT NULL,
  `id_prodotto` int(11) NOT NULL,
  `quantita` int(11) NOT NULL DEFAULT 1,
  `data_aggiunta` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `carrello`
--

INSERT INTO `carrello` (`id`, `email_utente`, `id_prodotto`, `quantita`, `data_aggiunta`) VALUES
(32, 'ammok@gmail.com', 10, 2, '2025-05-21 14:01:34');

-- --------------------------------------------------------

--
-- Struttura della tabella `comunity`
--

CREATE TABLE `comunity` (
  `id` int(11) NOT NULL,
  `Titolo` varchar(50) NOT NULL,
  `Testo` text NOT NULL,
  `Tema` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `comunity`
--

INSERT INTO `comunity` (`id`, `Titolo`, `Testo`, `Tema`) VALUES
(1, 'Un Bagno al Meisino ', 'UN BAGNO AL MEISINO 🚽🌳\r\n\r\nSpecchio dei tempi Feat. Chat GPT \r\n\r\nUn tentativo dal sapore avant garde di proporre un sentimento “dal basso” rispetto allo stato del Parco del Meisino, creando un mischione attraverso la versione chat GPT della signora Giovanna. Se questa non è ambizione non sappiamo come altro definirla. Il futuro è già qui ed è tangibile.\r\n\r\nC’è praticamente tutto: \r\nla passeggiata della domenica ✔\r\nla sintetica descrizione del parco ✔\r\nIl desiderio di valorizzare l’area naturalistica ✔\r\nIl tiepido accenno alla biodiversità attraverso l’erba alta✔\r\nla descrizione del degrado ( panchine rotte, muri imbrattati) ✔\r\nla mancanza di strutture giochi per bambini ✔\r\n\r\nE poi arriva il colpo da maestro: l’assenza di gabinetto 🚽 🎩\r\n\r\nPer fortuna la versione chat GPT della signora Giovanna “ha letto” di un promettente progetto finanziato con i fondi pnrr meglio spesi di sempre. \r\nConosce addirittura l’arzigogolata nuova denominazione del parco ( la versione greenwashing) ma i soliti riottosi NO QUALCOSA pretendono lo stato di degrado assoluto…\r\nEmerge altresì la curiosa analogia con la definizione “sempre gli stessi”, utilizzata più volte dagli assessori per denigrare il comitato che si oppone al progetto. \r\n\r\nPossiamo dire, senza timore di essere CHATGPT- smentiti, che questa “ ricostruzione” ( impalcatura ) sia un originale crossover tra una puntata di Black Mirror e uno sketch demenziale di Maccio Capatonda: ed è così che il grottesco si fa strada laddove l’ignoranza non trova altri sentieri per veicolare il proprio pensiero. \r\nRimane quella sensazione di meraviglia per l’impeccabile scelta editoriale della letterina formato IA. \r\n\r\nIl piano va veloce CONTROL ALT CANC \r\n\r\nSalviamo IL Meisino\r\n#greenwashing \r\n#meisino \r\n#fake \r\n#satira', 'Ambiente'),
(2, 'Trash Express', 'Con Trash Express e alcuni  volontari sui roller e longboard  🛹abbiamo estratto dai sampietrini del Ponte Vecchio  di Comune di San Mauro Torinese ogni singolo mozzicone. Siamo ampiamente sopra il migliaio di sigarette raccolte. Un lavoro minuzioso portato a termine con non poca fatica. Durerà poco...entro qualche ora, se non prima, alcuni incivili si sentiranno autorizzati a buttare le cicche per terra: sembra impossibile ma molti scambiano le nostre strade per un  posacenere a cielo aperto.\r\nGrazie alla segnalazione di un signore abbiamo recuperato decine  di bottiglie di vetro e latta  (birra, vodka e mais) proprio sulla sponda  del fiume Po, adiacente a piazza Gramsci. Questa cosa ci ha creato un po\' di sconforto e speriamo vivamente che non si arrivi ad avere simili segnalazioni in futuro. \r\n\r\n Abbiamo lasciato dei piccoli flyer sui vasoni lungo il ponte Vittorio Emanuele III, nella speranza che il messaggio duri più di qualche ora. A fine PLOGGING abbiamo approfittato della ciclabile per un giro per la splendida ciclabile.\r\n\r\n#plogging \r\n#ciclabile \r\n#sanmaurotorinese \r\n\r\nLegambiente Metropolitano Torino e area metropolitana', 'Educazione ambientale'),
(3, 'Kidical Mass', '🐥KIDICAL MASS 🚲🛼🛹\r\n\r\nI preparativi del primo Bike to School di Settimo torinese. \r\nOggi abbiamo organizzato con tanti ragazzi, docenti e assessori del Comune di Settimo una serie di pedalate per arrivare a scuola ( e tornare a casa post lezioni ) in modalità sostenibile. \r\nUn momento di riappropriazione delle strade che dia finalmente alle persone la possibilità di vivere la propria città attraverso una ciclo-festa itinerante. \r\nLe canzoni le mettono i ragazzi. La voglia di cambiamento anche. \r\nIl percorso urbano ci darà la possibilità di “ravvivare” le strade di una Città che crede e investe su un’idea di mobilità alternativa, con l’obiettivo di mettere gli studenti al centro di una rivoluzione dolce. \r\nEd è così che i più giovani possono diventare un esempio per la comunità e, più nello specifico, per tutte quelle persone che per fare 500 metri prevedono il sistematico spostamento con un’autovettura. \r\nQuesto è un esperimento e non ho idea di come possa evolvere ma l’intenzione è quella di raccontare una storia che possa connettersi a tante altre realtà. \r\nAl di là di tutte gli argomenti più o meno tecnico-retorici sulla mobilità mi preme trasmettere la gioia di una pedalata collettiva, l’idea della pianificazione ( indispensabile per girare in sicurezza ) e vedere i ragazzi ambire a diventare “veterani” del bike to school Gobetti. \r\nStanno nascendo tantissime idee, faremo il possibile per tradurle 🚲🛼🛹e renderle fruibili. \r\nPer alcuni suonerà come una follia eppure questo è semplicemente l’ultimo mattoncino di un percorso di sperimentazioni che ci ha dato la propulsione per affrontare questa sfida. Sento quel piacevole brivido che ti tiene vivo anche quando ci si trascina collettivamente a stento, in un momento storico non proprio edificante in cui sembra stia andando tutto a rotoli. \r\nTutto sommato L’idea che basti poco per essere felici si sposa magnificamente bene con la bicicletta. \r\n\r\nCon Massimo Tocci Toroller Collective Piermarco Pizzardi Fridays For Future Torino Future Parade Torino', 'Educazione stradale');

-- --------------------------------------------------------

--
-- Struttura della tabella `dettagli_ordine`
--

CREATE TABLE `dettagli_ordine` (
  `id` int(11) NOT NULL,
  `id_ordine` int(11) NOT NULL,
  `id_prodotto` int(11) NOT NULL,
  `quantita` int(11) NOT NULL,
  `prezzo_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `eventi`
--

CREATE TABLE `eventi` (
  `id` int(11) NOT NULL,
  `titolo` varchar(20) NOT NULL,
  `descrizione` text NOT NULL,
  `luogo` text NOT NULL,
  `data` date NOT NULL,
  `immagine` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `eventi`
--

INSERT INTO `eventi` (`id`, `titolo`, `descrizione`, `luogo`, `data`, `immagine`) VALUES
(1, 'Critical-mass', 'Manifestazione di ciclisti ', 'Ogni primo giovedi del mese, partenza: Piazza Castello', '2025-04-01', NULL),
(2, 'Bike-To-School', 'Accompagnamento a Scuola dei bambini tramite l\'utilizzo di mezzi sostenibili', 'Istituto Gobetti, Settimo-Torinese', '2025-04-06', NULL),
(3, 'Educazione stradale', 'Corsi di educazione stradale per ciclisti ', 'Parco Pertini, Settimo-Torinese ', '2025-04-27', NULL),
(4, 'Future Parade', 'Parata Toroller x Fridays For Future ', 'Torino', '2025-06-14', NULL),
(5, 'Roller Dancing', 'Serata danzante sui pattini', 'Settimo-Torinese ', '2025-06-28', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `forum_categories`
--

CREATE TABLE `forum_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `forum_categories`
--

INSERT INTO `forum_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Generale', 'Discussioni generali sulla community', '2025-05-13 18:44:49'),
(2, 'Eventi', 'Discussioni sugli eventi passati e futuri', '2025-05-13 18:44:49'),
(3, 'Prodotti', 'Discussioni tecniche e consigli', '2025-05-13 18:44:49');

-- --------------------------------------------------------

--
-- Struttura della tabella `forum_replies`
--

CREATE TABLE `forum_replies` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `forum_replies`
--

INSERT INTO `forum_replies` (`id`, `topic_id`, `user_email`, `content`, `created_at`) VALUES
(1, 1, 'marco@gmail.com', 'bo frate , sono divertenti tanto per cominciare ', '2025-05-13 18:45:33'),
(2, 1, 'ammok@gmail.com', 'dhdjghfhj', '2025-05-13 18:53:03'),
(3, 2, 'ammok@gmail.com', 'hgfghfhj', '2025-05-13 18:53:43'),
(4, 1, 'ammok@gmail.com', 'loool\r\n', '2025-05-19 18:06:55'),
(5, 1, 'piermarcopizzardi@outlook.com', 'servono ad allenare gambe glutei e addome', '2025-05-20 14:48:27');

-- --------------------------------------------------------

--
-- Struttura della tabella `forum_topics`
--

CREATE TABLE `forum_topics` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `forum_topics`
--

INSERT INTO `forum_topics` (`id`, `category_id`, `user_email`, `title`, `content`, `created_at`) VALUES
(1, 2, 'marco@gmail.com', 'pattini', 'a che cazzo servono i pattini ?', '2025-05-13 18:45:17'),
(2, 1, 'ammok@gmail.com', 'bici da corsa o elettrica ?', 'non so cosa fare aiutooo', '2025-05-13 18:53:36'),
(3, 1, 'piermarcopizzardi@outlook.com', 'marca di pattini migliore', 'secondo voi su quali pattini dovrei orientarmi ? considerando di non voler spendere piu di 200£?', '2025-05-20 12:34:50');

-- --------------------------------------------------------

--
-- Struttura della tabella `ordini`
--

CREATE TABLE `ordini` (
  `id` int(11) NOT NULL,
  `email_utente` varchar(100) NOT NULL,
  `data_ordine` timestamp NOT NULL DEFAULT current_timestamp(),
  `totale` decimal(10,2) NOT NULL,
  `stato` enum('in_elaborazione','confermato','spedito','consegnato') NOT NULL DEFAULT 'in_elaborazione'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `ordini`
--

INSERT INTO `ordini` (`id`, `email_utente`, `data_ordine`, `totale`, `stato`) VALUES
(1, 'giulia@gmail.com', '2025-05-13 14:22:26', 99.98, 'in_elaborazione');

-- --------------------------------------------------------

--
-- Struttura della tabella `prodotti`
--

CREATE TABLE `prodotti` (
  `id` int(11) NOT NULL,
  `tipologia` varchar(50) NOT NULL,
  `prezzo` decimal(10,2) NOT NULL,
  `quantita` int(11) NOT NULL,
  `colore` varchar(50) NOT NULL,
  `immagine` varchar(255) DEFAULT NULL,
  `descrizione` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `prodotti`
--

INSERT INTO `prodotti` (`id`, `tipologia`, `prezzo`, `quantita`, `colore`, `immagine`, `descrizione`) VALUES
(10, 'LOL', 22.00, 1, 'NERO', '682dda109bbf1.png', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `data_nascita` date NOT NULL,
  `amministratore` tinyint(1) NOT NULL DEFAULT 0,
  `google_id` varchar(255) DEFAULT NULL,
  `facebook_id` varchar(255) DEFAULT NULL,
  `apple_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`nome`, `cognome`, `email`, `password`, `data_nascita`, `amministratore`, `google_id`, `facebook_id`, `apple_id`) VALUES
('admin1', 'admin1', 'admin1@gmail.com', 'sucasuca', '2024-10-16', 1, NULL, NULL, NULL),
('ammok', 'ammok', 'ammok@gmail.com', '$2y$10$QLLaKB3zBu8OXszr8.K9QuhEfb7zHY91iN/KMSK2Gr42I7PDPUNgC', '2025-05-17', 1, NULL, NULL, NULL),
('bastardo', 'fillip di puttana', 'bastardoadmin@gmail.com', 'bastardodiunadmin', '2025-05-20', 1, NULL, NULL, NULL),
('elisa', 'poujol', 'elisa.poujol@gmail.com', '$2y$10$35VUBLNp5GvCNUfEWdybGeRizpSejqMeZdSzDDkS.FiwiIg7EO8Yi', '1988-02-17', 0, NULL, NULL, NULL),
('giulia', 'cammarata', 'giulia@gmail.com', '$2y$10$ntfp5E8ep7tucoavNyZYXekk5RJiiPPwGuTZ06D3dfd/BF0tCYtkS', '2000-02-03', 0, NULL, NULL, NULL),
('marco', 'marco', 'marco@gmail.com', '$2y$10$PlRoa1ssw28WTZxCx9MvSeizMU9GOQlIlUj8tksEF8DmkkgtkPuDa', '2025-05-15', 0, NULL, NULL, NULL),
('piermarco', 'pizzardi', 'piermarcopizzardi@outlook.com', '$2y$10$R6exAyljlz31owMzfdKyE.qjgADuCZG6NYrVpaHMjhOR1xqFg/Z..', '2002-12-03', 0, NULL, NULL, NULL);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `carrello`
--
ALTER TABLE `carrello`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_utente` (`email_utente`),
  ADD KEY `id_prodotto` (`id_prodotto`);

--
-- Indici per le tabelle `comunity`
--
ALTER TABLE `comunity`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `dettagli_ordine`
--
ALTER TABLE `dettagli_ordine`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ordine` (`id_ordine`),
  ADD KEY `id_prodotto` (`id_prodotto`);

--
-- Indici per le tabelle `eventi`
--
ALTER TABLE `eventi`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `forum_categories`
--
ALTER TABLE `forum_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `user_email` (`user_email`);

--
-- Indici per le tabelle `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_email` (`user_email`);

--
-- Indici per le tabelle `ordini`
--
ALTER TABLE `ordini`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_utente` (`email_utente`);

--
-- Indici per le tabelle `prodotti`
--
ALTER TABLE `prodotti`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`email`),
  ADD KEY `idx_google_id` (`google_id`),
  ADD KEY `idx_facebook_id` (`facebook_id`),
  ADD KEY `idx_apple_id` (`apple_id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `carrello`
--
ALTER TABLE `carrello`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT per la tabella `comunity`
--
ALTER TABLE `comunity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `dettagli_ordine`
--
ALTER TABLE `dettagli_ordine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `eventi`
--
ALTER TABLE `eventi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `forum_categories`
--
ALTER TABLE `forum_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `forum_topics`
--
ALTER TABLE `forum_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `ordini`
--
ALTER TABLE `ordini`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `prodotti`
--
ALTER TABLE `prodotti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `carrello`
--
ALTER TABLE `carrello`
  ADD CONSTRAINT `carrello_ibfk_1` FOREIGN KEY (`email_utente`) REFERENCES `utente` (`email`),
  ADD CONSTRAINT `carrello_ibfk_2` FOREIGN KEY (`id_prodotto`) REFERENCES `prodotti` (`id`);

--
-- Limiti per la tabella `dettagli_ordine`
--
ALTER TABLE `dettagli_ordine`
  ADD CONSTRAINT `dettagli_ordine_ibfk_1` FOREIGN KEY (`id_ordine`) REFERENCES `ordini` (`id`),
  ADD CONSTRAINT `dettagli_ordine_ibfk_2` FOREIGN KEY (`id_prodotto`) REFERENCES `prodotti` (`id`);

--
-- Limiti per la tabella `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD CONSTRAINT `forum_replies_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`),
  ADD CONSTRAINT `forum_replies_ibfk_2` FOREIGN KEY (`user_email`) REFERENCES `utente` (`email`);

--
-- Limiti per la tabella `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD CONSTRAINT `forum_topics_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`),
  ADD CONSTRAINT `forum_topics_ibfk_2` FOREIGN KEY (`user_email`) REFERENCES `utente` (`email`);

--
-- Limiti per la tabella `ordini`
--
ALTER TABLE `ordini`
  ADD CONSTRAINT `ordini_ibfk_1` FOREIGN KEY (`email_utente`) REFERENCES `utente` (`email`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
