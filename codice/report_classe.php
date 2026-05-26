<?php
session_start();
require_once 'includes/functions.php';

$filtro_classe = $_GET['id_classe'] ?? '';
$classi = leggi_json('classi.json');
$prenotazioni = leggi_json('prenotazioni.json');
$itinerari = leggi_json('itinerari.json');
$destinazioni = leggi_json('destinazioni.json');

$map_dest = [];
foreach ($destinazioni as $d) $map_dest[$d['id']] = $d['nome'];
$map_it = [];
foreach ($itinerari as $it) $map_it[$it['id']] = $it;

$risultati = [];
if ($filtro_classe) {
    foreach ($prenotazioni as $p) {
        if (in_array($filtro_classe, $p['classi'] ?? []) && $p['stato'] !== 'annullata') {
            $it = $map_it[$p['id_itinerario']] ?? null;
            if ($it) {
                $risultati[] = [
                    'prenotazione' => $p,
                    'itinerario'   => $it,
                    'dest_nome'    => $map_dest[$it['id_destinazione']] ?? '?',
                ];
            }
        }
    }
    // Ordina per anno corso
    usort($risultati, fn($a, $b) => $a['itinerario']['anno_corso'] <=> $b['itinerario']['anno_corso']);
}

$classe_sel = null;
if ($filtro_classe) {
    foreach ($classi as $c) {
        if ($c['id'] === $filtro_classe) { $classe_sel = $c; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Escursioni per Classe</title></head>
<body>
<a href="index.php">← Home</a>
<h1>Escursioni Effettuate da una Classe</h1>

<form method="GET" action="report_classe.php">
    <label>Seleziona classe:
        <select name="id_classe" onchange="this.form.submit()">
            <option value="">-- seleziona --</option>
            <?php foreach ($classi as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id'] === $filtro_classe ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nome']) ?> (<?= $c['anno_corso'] ?>° anno)
            </option>
            <?php endforeach; ?>
        </select>
    </label>
</form>
<br>

<?php if ($filtro_classe && $classe_sel): ?>
<h2>Classe: <?= htmlspecialchars($classe_sel['nome']) ?></h2>

<?php if (empty($risultati)): ?>
<p>Nessuna escursione registrata per questa classe.</p>
<?php else: ?>
<table border="1" cellpadding="5">
    <tr>
        <th>Anno corso gita</th>
        <th>Destinazione</th>
        <th>Tipo</th>
        <th>Giorni</th>
        <th>Costo/persona €</th>
        <th>Partecipanti</th>
        <th>Costo totale €</th>
        <th>Data prenotazione</th>
        <th>Autorizzazioni</th>
        <th>Rinuncianti</th>
    </tr>
    <?php foreach ($risultati as $r):
        $p = $r['prenotazione'];
        $it = $r['itinerario'];
        $n_rinunc = count($p['alunni_rinuncianti'] ?? []);
    ?>
    <tr>
        <td><?= $it['anno_corso'] ?>°</td>
        <td><?= htmlspecialchars($r['dest_nome']) ?></td>
        <td><?= htmlspecialchars($tipi_itinerario[$it['tipo']] ?? $it['tipo']) ?></td>
        <td><?= $it['giorni'] ?></td>
        <td><?= number_format($it['costo'], 2) ?></td>
        <td><?= $p['num_partecipanti'] ?></td>
        <td><?= number_format($p['costo_totale'], 2) ?></td>
        <td><?= $p['data_prenotazione'] ?></td>
        <td><?= $p['autorizzazioni_raccolte'] ? '✓ Sì' : '✗ No' ?></td>
        <td><?= $n_rinunc > 0 ? $n_rinunc : '—' ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<br>
<strong>Totale escursioni effettuate:</strong> <?= count($risultati) ?>
<?php endif; ?>
<?php endif; ?>
</body>
</html>
