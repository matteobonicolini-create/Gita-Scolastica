<?php
session_start();
require_once 'includes/functions.php';

$id = $_GET['id'] ?? $_POST['id'] ?? '';
if (!$id) redirect('itinerari.php', 'ID itinerario mancante.', 'error');

if (ha_prenotazioni($id)) {
    redirect('itinerari.php', 'Impossibile modificare: esistono prenotazioni attive per questo itinerario.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giorni = intval($_POST['giorni'] ?? 0);
    $min_part = intval($_POST['min_partecipanti'] ?? 0);
    $max_part = intval($_POST['max_partecipanti'] ?? 0);
    $costo = floatval($_POST['costo'] ?? 0);

    if ($giorni <= 0 || $min_part <= 0 || $max_part <= 0 || $costo <= 0) {
        redirect("itinerario_modifica.php?id=$id", 'Valori non validi.', 'error');
    }
    if ($min_part > $max_part) {
        redirect("itinerario_modifica.php?id=$id", 'Min partecipanti > max partecipanti.', 'error');
    }

    $itinerari = leggi_json('itinerari.json');
    foreach ($itinerari as &$it) {
        if ($it['id'] === $id) {
            $it['tipo']             = $_POST['tipo'] ?? $it['tipo'];
            $it['giorni']           = $giorni;
            $it['descrizione']      = trim($_POST['descrizione'] ?? '');
            $it['anno_corso']       = intval($_POST['anno_corso'] ?? 1);
            $it['min_partecipanti'] = $min_part;
            $it['max_partecipanti'] = $max_part;
            $it['costo']            = $costo;
            $it['optional_vitto']   = $_POST['optional_vitto'] ?? 'escluso';
            break;
        }
    }
    scrivi_json('itinerari.json', $itinerari);
    redirect('itinerari.php', 'Itinerario modificato con successo.');
}

$it = get_itinerario($id);
if (!$it) redirect('itinerari.php', 'Itinerario non trovato.', 'error');

$destinazioni = leggi_json('destinazioni.json');
$map_dest = [];
foreach ($destinazioni as $d) $map_dest[$d['id']] = $d['nome'];
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Modifica Itinerario</title></head>
<body>
<a href="itinerari.php">← Lista Itinerari</a>
<h1>Modifica Itinerario</h1>
<strong>Destinazione:</strong> <?= htmlspecialchars($map_dest[$it['id_destinazione']] ?? '?') ?> (non modificabile)<br><br>
<?php flash(); ?>

<form method="POST" action="itinerario_modifica.php">
    <input type="hidden" name="id" value="<?= $it['id'] ?>">

    <label>Tipo:
        <select name="tipo" required>
            <?php foreach ($tipi_itinerario as $k => $v): ?>
            <option value="<?= $k ?>" <?= $it['tipo'] === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Giorni: <input type="number" name="giorni" min="1" value="<?= $it['giorni'] ?>" required></label><br><br>

    <label>Descrizione:<br>
        <textarea name="descrizione" rows="4" cols="50"><?= htmlspecialchars($it['descrizione']) ?></textarea>
    </label><br><br>

    <label>Anno di corso:
        <select name="anno_corso" required>
            <?php foreach ($anni_corso as $a): ?>
            <option value="<?= $a ?>" <?= $it['anno_corso'] == $a ? 'selected' : '' ?>><?= $a ?>°</option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Minimo partecipanti: <input type="number" name="min_partecipanti" min="1" value="<?= $it['min_partecipanti'] ?>" required></label><br><br>
    <label>Massimo partecipanti: <input type="number" name="max_partecipanti" min="1" value="<?= $it['max_partecipanti'] ?>" required></label><br><br>

    <label>Costo per persona (€): <input type="number" name="costo" min="0.01" step="0.01" value="<?= $it['costo'] ?>" required></label><br><br>

    <label>Optional vitto:
        <select name="optional_vitto">
            <?php foreach ($optional_vitto as $k => $v): ?>
            <option value="<?= $k ?>" <?= $it['optional_vitto'] === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <button type="submit">Salva Modifiche</button>
    <a href="itinerari.php">Annulla</a>
</form>
</body>
</html>
