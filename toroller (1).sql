-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 07, 2025 alle 13:30
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.0.30

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
-- Struttura della tabella `eventi`
--

CREATE TABLE `eventi` (
  `id` int(11) NOT NULL,
  `titolo` varchar(20) NOT NULL,
  `descrizione` text NOT NULL,
  `luogo` text NOT NULL,
  `data` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `eventi`
--

INSERT INTO `eventi` (`id`, `titolo`, `descrizione`, `luogo`, `data`) VALUES
(1, 'Critical-mass', 'Manifestazione di ciclisti ', 'Ogni primo giovedi del mese, partenza: Piazza Castello', '2025-04-01'),
(2, 'Bike-To-School', 'Accompagnamento a Scuola dei bambini tramite l\'utilizzo di mezzi sostenibili', 'Istituto Gobetti, Settimo-Torinese', '2025-04-06'),
(3, 'Educazione stradale', 'Corsi di educazione stradale per ciclisti ', 'Parco Pertini, Settimo-Torinese ', '2025-04-27'),
(4, 'Future Parade', 'Parata Toroller x Fridays For Future ', 'Torino', '2025-06-14'),
(5, 'Roller Dancing', 'Serata danzante sui pattini', 'Settimo-Torinese ', '2025-06-28');

-- --------------------------------------------------------

--
-- Struttura della tabella `prodotti`
--

CREATE TABLE `prodotti` (
  `id` int(11) NOT NULL,
  `tipologia` varchar(50) NOT NULL,
  `prezzo` int(11) NOT NULL,
  `quantità` int(11) NOT NULL,
  `colore` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `prodotti`
--

INSERT INTO `prodotti` (`id`, `tipologia`, `prezzo`, `quantità`, `colore`) VALUES
(1, 'maglietta', 20, 25, 'nero'),
(2, 'pantaloni', 35, 25, 'bianco'),
(3, 'maglietta', 20, 25, 'bianco'),
(4, 'pantaloni', 35, 25, 'nero'),
(5, 'berretto', 12, 25, 'nero'),
(6, 'berretto', 12, 25, 'bianco');

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
  `amministratore` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`nome`, `cognome`, `email`, `password`, `data_nascita`, `amministratore`) VALUES
('admin', 'null', 'null', 'TorollerCollectiveDIOCANE', '0000-00-00', 1),
('piermarco', 'pizzardi', 'piermarcopizzardi@outlook.com', '5hMU3EBd22', '0000-00-00', 0);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `comunity`
--
ALTER TABLE `comunity`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `eventi`
--
ALTER TABLE `eventi`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `prodotti`
--
ALTER TABLE `prodotti`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `comunity`
--
ALTER TABLE `comunity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `eventi`
--
ALTER TABLE `eventi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `prodotti`
--
ALTER TABLE `prodotti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
