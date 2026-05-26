<?php
session_start();
require_once 'includes/functions.php';

$itinerari = leggi_json('itinerari.json');
$prenotazioni = leggi_json('prenotazioni.json');
$classi = leggi_json('classi.json');
$alunni = leggi_json('alunni.json');
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Gestione Gite Scolastiche</title>
</head>
<body>
<h1>Gestione Gite Scolastiche</h1>

<?php flash(); ?>

<h2>Riepilogo</h2>
<ul>
    <li>Itinerari totali: <?= count($itinerari) ?></li>
    <li>Prenotazioni attive: <?= count(array_filter($prenotazioni, fn($p) => $p['stato'] !== 'annullata')) ?></li>
    <li>Classi registrate: <?= count($classi) ?></li>
    <li>Alunni registrati: <?= count($alunni) ?></li>
</ul>

<hr>
<h2>Menu principale</h2>

<h3>Destinazioni e Itinerari</h3>
<ul>
    <li><a href="destinazioni.php">Gestione Destinazioni</a></li>
    <li><a href="itinerari.php">Lista Itinerari</a></li>
    <li><a href="itinerario_nuovo.php">Nuovo Itinerario</a></li>
</ul>

<h3>Classi e Alunni</h3>
<ul>
    <li><a href="classi.php">Gestione Classi</a></li>
    <li><a href="alunni.php">Gestione Alunni</a></li>
</ul>

<h3>Prenotazioni</h3>
<ul>
    <li><a href="prenotazioni.php">Lista Prenotazioni</a></li>
    <li><a href="prenotazione_nuova.php">Nuova Prenotazione</a></li>
</ul>

<h3>Stampe e Report</h3>
<ul>
    <li><a href="report_programma.php">Programma di un Itinerario per Destinazione</a></li>
    <li><a href="report_anno.php">Escursioni per Anno di Corso</a></li>
    <li><a href="report_classe.php">Escursioni effettuate da una Classe</a></li>
</ul>

</body>
</html>
