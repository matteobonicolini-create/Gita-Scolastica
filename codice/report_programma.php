<?php
session_start();
require_once 'includes/functions.php';

$itinerari = leggi_json('itinerari.json');
$destinazioni = leggi_json('destinazioni.json');
$map_dest = [];
foreach ($destinazioni as $d) $map_dest[$d['id']] = $d['nome'];

$filtro_dest = $_GET['id_destinazione'] ?? '';
$filtro_it = $_GET['id'] ?? '';

$itinerari_dest = [];
if ($filtro_dest) {
    $itinerari_dest = array_filter($itinerari, fn($i) => $i['id_destinazione'] === $filtro_dest);
}
$itinerario_sel = $filtro_it ? get_itinerario($filtro_it) : null;
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Programma Itinerario</title></head>
<body>
<a href="index.php">← Home</a>
<h1>Programma di un Itinerario per Destinazione</h1>

<h2>1. Seleziona destinazione</h2>
<form method="GET" action="report_programma.php">
    <label>Destinazione:
        <select name="id_destinazione" onchange="this.form.submit()">
            <option value="">-- seleziona --</option>
            <?php foreach ($destinazioni as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $d['id'] === $filtro_dest ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['nome']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<?php if ($filtro_dest && !empty($itinerari_dest)): ?>
<h2>2. Itinerari per <?= htmlspecialchars($map_dest[$filtro_dest] ?? '') ?></h2>
<form method="GET" action="report_programma.php">
    <input type="hidden" name="id_destinazione" value="<?= $filtro_dest ?>">
    <label>Itinerario:
        <select name="id" onchange="this.form.submit()">
            <option value="">-- seleziona --</option>
            <?php foreach ($itinerari_dest as $it): ?>
            <option value="<?= $it['id'] ?>" <?= $it['id'] === $filtro_it ? 'selected' : '' ?>>
                <?= $it['giorni'] ?> giorni — <?= $it['anno_corso'] ?>° anno — €<?= number_format($it['costo'], 2) ?>
                — <?= htmlspecialchars($tipi_itinerario[$it['tipo']] ?? $it['tipo']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>
</form>
<?php elseif ($filtro_dest): ?>
<p>Nessun itinerario per questa destinazione.</p>
<?php endif; ?>

<?php if ($itinerario_sel): ?>
<hr>
<h2>Programma</h2>
<table border="1" cellpadding="8">
    <tr><th>Destinazione</th><td><?= htmlspecialchars($map_dest[$itinerario_sel['id_destinazione']] ?? '?') ?></td></tr>
    <tr><th>Tipo</th><td><?= htmlspecialchars($tipi_itinerario[$itinerario_sel['tipo']] ?? $itinerario_sel['tipo']) ?></td></tr>
    <tr><th>Durata</th><td><?= $itinerario_sel['giorni'] ?> giorni</td></tr>
    <tr><th>Anno di corso</th><td><?= $itinerario_sel['anno_corso'] ?>°</td></tr>
    <tr><th>Partecipanti</th><td>min <?= $itinerario_sel['min_partecipanti'] ?> — max <?= $itinerario_sel['max_partecipanti'] ?></td></tr>
    <tr><th>Costo per persona</th><td>€<?= number_format($itinerario_sel['costo'], 2) ?></td></tr>
    <tr><th>Vitto</th><td><?= htmlspecialchars($optional_vitto[$itinerario_sel['optional_vitto']] ?? $itinerario_sel['optional_vitto']) ?></td></tr>
    <tr><th>Descrizione / Programma</th><td style="white-space:pre-wrap"><?= htmlspecialchars($itinerario_sel['descrizione'] ?: '—') ?></td></tr>
    <tr><th>Data inserimento</th><td><?= $itinerario_sel['data_creazione'] ?></td></tr>
</table>

<?php
// Mostra prenotazioni per questo itinerario
$prenotazioni = leggi_json('prenotazioni.json');
$classi = leggi_json('classi.json');
$map_cls = [];
foreach ($classi as $c) $map_cls[$c['id']] = $c['nome'];
$pren_it = array_filter($prenotazioni, fn($p) => $p['id_itinerario'] === $itinerario_sel['id']);
if (!empty($pren_it)):
?>
<h3>Prenotazioni</h3>
<table border="1" cellpadding="5">
    <tr><th>Classi</th><th>Partecipanti</th><th>Stato</th><th>Data</th></tr>
    <?php foreach ($pren_it as $p): ?>
    <tr>
        <td><?= htmlspecialchars(implode(', ', array_map(fn($cid) => $map_cls[$cid] ?? '?', $p['classi']))) ?></td>
        <td><?= $p['num_partecipanti'] ?></td>
        <td><?= $p['stato'] ?></td>
        <td><?= $p['data_prenotazione'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php endif; ?>
</body>
</html>
