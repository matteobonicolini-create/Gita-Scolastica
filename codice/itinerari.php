<?php
session_start();
require_once 'includes/functions.php';

// Cancellazione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['azione'] ?? '') === 'elimina') {
    $id = $_POST['id'] ?? '';
    if (ha_prenotazioni($id)) {
        redirect('itinerari.php', 'Impossibile eliminare: esistono prenotazioni attive per questo itinerario.', 'error');
    }
    $itinerari = leggi_json('itinerari.json');
    $itinerari = array_values(array_filter($itinerari, fn($i) => $i['id'] !== $id));
    scrivi_json('itinerari.json', $itinerari);
    redirect('itinerari.php', 'Itinerario eliminato.');
}

$itinerari = leggi_json('itinerari.json');
$destinazioni = leggi_json('destinazioni.json');

// Mappa id->nome destinazione
$map_dest = [];
foreach ($destinazioni as $d) $map_dest[$d['id']] = $d['nome'];
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Itinerari</title></head>
<body>
<a href="index.php">← Home</a>
<h1>Lista Itinerari</h1>
<?php flash(); ?>
<a href="itinerario_nuovo.php">+ Nuovo Itinerario</a>
<br><br>

<?php if (empty($itinerari)): ?>
    <p>Nessun itinerario presente.</p>
<?php else: ?>
<table border="1" cellpadding="5">
    <tr>
        <th>Destinazione</th>
        <th>Tipo</th>
        <th>Giorni</th>
        <th>Anno</th>
        <th>Partecipanti (min-max)</th>
        <th>Costo €</th>
        <th>Vitto</th>
        <th>Prenotazioni</th>
        <th>Azioni</th>
    </tr>
    <?php foreach ($itinerari as $it):
        $pren = ha_prenotazioni($it['id']);
    ?>
    <tr>
        <td><?= htmlspecialchars($map_dest[$it['id_destinazione']] ?? '?') ?></td>
        <td><?= htmlspecialchars($tipi_itinerario[$it['tipo']] ?? $it['tipo']) ?></td>
        <td><?= $it['giorni'] ?></td>
        <td><?= $it['anno_corso'] ?>°</td>
        <td><?= $it['min_partecipanti'] ?> - <?= $it['max_partecipanti'] ?></td>
        <td><?= number_format($it['costo'], 2) ?></td>
        <td><?= htmlspecialchars($optional_vitto[$it['optional_vitto']] ?? $it['optional_vitto']) ?></td>
        <td><?= $pren ? '<strong>Sì</strong>' : 'No' ?></td>
        <td>
            <?php if (!$pren): ?>
                <a href="itinerario_modifica.php?id=<?= $it['id'] ?>">Modifica</a> |
                <form method="POST" action="itinerari.php" style="display:inline">
                    <input type="hidden" name="azione" value="elimina">
                    <input type="hidden" name="id" value="<?= $it['id'] ?>">
                    <button type="submit" onclick="return confirm('Eliminare questo itinerario?')">Elimina</button>
                </form>
            <?php else: ?>
                <em>Non modificabile</em>
            <?php endif; ?>
            | <a href="report_programma.php?id=<?= $it['id'] ?>">Programma</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>
