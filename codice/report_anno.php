<?php
session_start();
require_once 'includes/functions.php';

$filtro_anno = $_GET['anno'] ?? '';
$itinerari = leggi_json('itinerari.json');
$prenotazioni = leggi_json('prenotazioni.json');
$destinazioni = leggi_json('destinazioni.json');
$classi = leggi_json('classi.json');

$map_dest = [];
foreach ($destinazioni as $d) $map_dest[$d['id']] = $d['nome'];
$map_cls = [];
foreach ($classi as $c) $map_cls[$c['id']] = $c['nome'];

// Filtra itinerari per anno
$itinerari_filtrati = $filtro_anno
    ? array_filter($itinerari, fn($i) => $i['anno_corso'] == $filtro_anno)
    : $itinerari;

// Per ogni itinerario, trova le prenotazioni attive
function prenotazioni_per_itinerario($id_it, $prenotazioni) {
    return array_filter($prenotazioni, fn($p) => $p['id_itinerario'] === $id_it && $p['stato'] !== 'annullata');
}
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Escursioni per Anno di Corso</title></head>
<body>
<a href="index.php">← Home</a>
<h1>Elenco Escursioni per Anno di Corso</h1>

<form method="GET" action="report_anno.php">
    <label>Anno di corso:
        <select name="anno" onchange="this.form.submit()">
            <option value="">-- Tutti gli anni --</option>
            <?php foreach ($anni_corso as $a): ?>
            <option value="<?= $a ?>" <?= $filtro_anno == $a ? 'selected' : '' ?>><?= $a ?>° anno</option>
            <?php endforeach; ?>
        </select>
    </label>
</form>
<br>

<?php if (empty($itinerari_filtrati)): ?>
    <p>Nessun itinerario trovato<?= $filtro_anno ? " per il $filtro_anno° anno" : '' ?>.</p>
<?php else: ?>

<?php if ($filtro_anno): ?>
<h2>Escursioni per il <?= $filtro_anno ?>° anno di corso</h2>
<?php else: ?>
<h2>Tutte le escursioni</h2>
<?php endif; ?>

<table border="1" cellpadding="5">
    <tr>
        <th>Anno corso</th>
        <th>Destinazione</th>
        <th>Tipo</th>
        <th>Giorni</th>
        <th>Costo €</th>
        <th>Vitto</th>
        <th>Classi prenotate</th>
        <th>Stato</th>
    </tr>
    <?php foreach ($itinerari_filtrati as $it):
        $pren_it = prenotazioni_per_itinerario($it['id'], $prenotazioni);
        $classi_pren = [];
        foreach ($pren_it as $p) {
            foreach ($p['classi'] as $cid) $classi_pren[$cid] = $map_cls[$cid] ?? '?';
        }
    ?>
    <tr>
        <td><?= $it['anno_corso'] ?>°</td>
        <td><?= htmlspecialchars($map_dest[$it['id_destinazione']] ?? '?') ?></td>
        <td><?= htmlspecialchars($tipi_itinerario[$it['tipo']] ?? $it['tipo']) ?></td>
        <td><?= $it['giorni'] ?></td>
        <td><?= number_format($it['costo'], 2) ?></td>
        <td><?= htmlspecialchars($optional_vitto[$it['optional_vitto']] ?? $it['optional_vitto']) ?></td>
        <td><?= htmlspecialchars(implode(', ', array_values($classi_pren)) ?: '—') ?></td>
        <td><?= empty($pren_it) ? 'Non prenotato' : 'Prenotato' ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>
