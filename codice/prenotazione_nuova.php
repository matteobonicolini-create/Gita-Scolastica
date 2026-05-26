<?php
session_start();
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_itinerario = $_POST['id_itinerario'] ?? '';
    $classi_sel = $_POST['classi'] ?? [];
    $num_partecipanti = intval($_POST['num_partecipanti'] ?? 0);

    if (!$id_itinerario || empty($classi_sel) || $num_partecipanti <= 0) {
        redirect('prenotazione_nuova.php', 'Compila tutti i campi obbligatori.', 'error');
    }

    $it = get_itinerario($id_itinerario);
    if (!$it) redirect('prenotazione_nuova.php', 'Itinerario non trovato.', 'error');

    if ($num_partecipanti < $it['min_partecipanti'] || $num_partecipanti > $it['max_partecipanti']) {
        redirect('prenotazione_nuova.php',
            "Partecipanti fuori range: min {$it['min_partecipanti']}, max {$it['max_partecipanti']}.", 'error');
    }

    $costo_totale = $it['costo'] * $num_partecipanti;
    $acconto = round($costo_totale * 0.10, 2);

    $prenotazioni = leggi_json('prenotazioni.json');
    $id_pren = genera_id('pren_');
    $prenotazioni[] = [
        'id'               => $id_pren,
        'id_itinerario'    => $id_itinerario,
        'classi'           => $classi_sel,
        'num_partecipanti' => $num_partecipanti,
        'costo_unitario'   => $it['costo'],
        'costo_totale'     => $costo_totale,
        'acconto'          => $acconto,
        'saldo'            => round($costo_totale - $acconto, 2),
        'stato'            => 'confermata',
        'data_prenotazione' => date('Y-m-d'),
        'autorizzazioni_raccolte' => false,
        'alunni_rinuncianti' => [],
        'note'             => trim($_POST['note'] ?? ''),
    ];
    scrivi_json('prenotazioni.json', $prenotazioni);
    redirect('prenotazioni.php', "Prenotazione $id_pren confermata. Acconto dovuto: €" . number_format($acconto, 2));
}

$itinerari = leggi_json('itinerari.json');
$classi = leggi_json('classi.json');
$destinazioni = leggi_json('destinazioni.json');
$map_dest = [];
foreach ($destinazioni as $d) $map_dest[$d['id']] = $d['nome'];
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Nuova Prenotazione</title></head>
<body>
<a href="index.php">← Home</a> | <a href="prenotazioni.php">Lista Prenotazioni</a>
<h1>Nuova Prenotazione</h1>
<?php flash(); ?>

<?php if (empty($itinerari)): ?>
<p>Nessun itinerario disponibile. <a href="itinerario_nuovo.php">Creane uno</a>.</p>
<?php elseif (empty($classi)): ?>
<p>Nessuna classe disponibile. <a href="classi.php">Creane una</a>.</p>
<?php else: ?>
<form method="POST" action="prenotazione_nuova.php" id="formPren">

    <label>Itinerario:
        <select name="id_itinerario" required onchange="aggiornaInfo(this)">
            <option value="">-- seleziona --</option>
            <?php foreach ($itinerari as $it): ?>
            <option value="<?= $it['id'] ?>"
                data-min="<?= $it['min_partecipanti'] ?>"
                data-max="<?= $it['max_partecipanti'] ?>"
                data-costo="<?= $it['costo'] ?>"
                data-anno="<?= $it['anno_corso'] ?>">
                <?= htmlspecialchars($map_dest[$it['id_destinazione']] ?? '?') ?>
                — <?= $it['giorni'] ?> giorni
                — <?= $it['anno_corso'] ?>° anno
                — €<?= number_format($it['costo'], 2) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>

    <div id="info_itinerario" style="display:none; margin:10px 0; padding:5px; border:1px solid #ccc;">
        Partecipanti: <span id="info_min"></span> - <span id="info_max"></span> |
        Costo/persona: €<span id="info_costo"></span>
    </div>

    <br>
    <strong>Classi partecipanti</strong> (seleziona una o più):<br>
    <?php foreach ($classi as $c): ?>
    <label>
        <input type="checkbox" name="classi[]" value="<?= $c['id'] ?>">
        <?= htmlspecialchars($c['nome']) ?> (<?= $c['anno_corso'] ?>° anno)
    </label><br>
    <?php endforeach; ?>

    <br>
    <label>Numero partecipanti totali: <input type="number" name="num_partecipanti" id="num_part" min="1" required onchange="calcolaAcconto()"></label>
    <br><br>

    <div id="riepilogo_costi" style="display:none; padding:5px; border:1px solid green;">
        <strong>Costo totale:</strong> €<span id="tot_costo"></span><br>
        <strong>Acconto (10%):</strong> €<span id="tot_acconto"></span><br>
        <strong>Saldo rimanente:</strong> €<span id="tot_saldo"></span>
    </div>
    <br>

    <label>Note aggiuntive:<br>
        <textarea name="note" rows="3" cols="40"></textarea>
    </label><br><br>

    <p><em>Nota: le autorizzazioni dei genitori devono essere raccolte prima della partenza.</em></p>

    <button type="submit">Conferma Prenotazione</button>
    <a href="prenotazioni.php">Annulla</a>
</form>
<?php endif; ?>

<script>
let costoUnitario = 0;

function aggiornaInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) {
        document.getElementById('info_itinerario').style.display = 'none';
        return;
    }
    document.getElementById('info_min').textContent = opt.dataset.min;
    document.getElementById('info_max').textContent = opt.dataset.max;
    document.getElementById('info_costo').textContent = parseFloat(opt.dataset.costo).toFixed(2);
    costoUnitario = parseFloat(opt.dataset.costo);
    document.getElementById('info_itinerario').style.display = '';
    calcolaAcconto();
}

function calcolaAcconto() {
    const n = parseInt(document.getElementById('num_part').value) || 0;
    if (!costoUnitario || n <= 0) {
        document.getElementById('riepilogo_costi').style.display = 'none';
        return;
    }
    const tot = costoUnitario * n;
    const acconto = tot * 0.10;
    document.getElementById('tot_costo').textContent = tot.toFixed(2);
    document.getElementById('tot_acconto').textContent = acconto.toFixed(2);
    document.getElementById('tot_saldo').textContent = (tot - acconto).toFixed(2);
    document.getElementById('riepilogo_costi').style.display = '';
}
</script>
</body>
</html>
