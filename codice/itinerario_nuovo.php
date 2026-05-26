<?php
session_start();
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_dest = $_POST['tipo_destinazione'] ?? '';
    $id_destinazione = '';

    if ($tipo_dest === 'esistente') {
        $id_destinazione = $_POST['id_destinazione'] ?? '';
        if (!$id_destinazione) redirect('itinerario_nuovo.php', 'Seleziona una destinazione.', 'error');
    } elseif ($tipo_dest === 'nuova') {
        $nome_dest = trim($_POST['nuova_destinazione'] ?? '');
        if ($nome_dest === '') redirect('itinerario_nuovo.php', 'Inserisci il nome della nuova destinazione.', 'error');
        $destinazioni = leggi_json('destinazioni.json');
        // Controlla duplicati
        foreach ($destinazioni as $d) {
            if (strtolower($d['nome']) === strtolower($nome_dest)) {
                redirect('itinerario_nuovo.php', 'La destinazione esiste già. Selezionala dall\'elenco.', 'error');
            }
        }
        $id_destinazione = genera_id('dest_');
        $destinazioni[] = [
            'id'   => $id_destinazione,
            'nome' => $nome_dest,
            'note' => '',
        ];
        scrivi_json('destinazioni.json', $destinazioni);
    } else {
        redirect('itinerario_nuovo.php', 'Scegli il tipo di destinazione.', 'error');
    }

    // Validazioni base
    $giorni = intval($_POST['giorni'] ?? 0);
    $min_part = intval($_POST['min_partecipanti'] ?? 0);
    $max_part = intval($_POST['max_partecipanti'] ?? 0);
    $costo = floatval($_POST['costo'] ?? 0);

    if ($giorni <= 0 || $min_part <= 0 || $max_part <= 0 || $costo <= 0) {
        redirect('itinerario_nuovo.php', 'Giorni, partecipanti e costo devono essere maggiori di zero.', 'error');
    }
    if ($min_part > $max_part) {
        redirect('itinerario_nuovo.php', 'Il minimo partecipanti non può superare il massimo.', 'error');
    }

    $itinerari = leggi_json('itinerari.json');
    $itinerari[] = [
        'id'               => genera_id('itin_'),
        'id_destinazione'  => $id_destinazione,
        'giorni'           => $giorni,
        'tipo'             => $_POST['tipo'] ?? '',
        'descrizione'      => trim($_POST['descrizione'] ?? ''),
        'min_partecipanti' => $min_part,
        'max_partecipanti' => $max_part,
        'costo'            => $costo,
        'anno_corso'       => intval($_POST['anno_corso'] ?? 1),
        'optional_vitto'   => $_POST['optional_vitto'] ?? 'escluso',
        'data_creazione'   => date('Y-m-d'),
    ];
    scrivi_json('itinerari.json', $itinerari);
    redirect('itinerari.php', 'Itinerario aggiunto con successo.');
}

$destinazioni = leggi_json('destinazioni.json');
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Nuovo Itinerario</title></head>
<body>
<a href="index.php">← Home</a> | <a href="itinerari.php">Lista Itinerari</a>
<h1>Nuovo Itinerario</h1>
<?php flash(); ?>

<form method="POST" action="itinerario_nuovo.php">

    <h3>Destinazione</h3>
    <label>
        <input type="radio" name="tipo_destinazione" value="esistente" checked onchange="toggleDest(this.value)">
        Destinazione esistente
    </label>
    &nbsp;
    <label>
        <input type="radio" name="tipo_destinazione" value="nuova" onchange="toggleDest(this.value)">
        Nuova destinazione
    </label>
    <br><br>

    <div id="dest_esistente">
        <label>Seleziona destinazione:
            <select name="id_destinazione">
                <option value="">-- seleziona --</option>
                <?php foreach ($destinazioni as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div id="dest_nuova" style="display:none">
        <label>Nome nuova destinazione: <input type="text" name="nuova_destinazione"></label>
    </div>

    <hr>
    <h3>Dati Itinerario</h3>

    <label>Tipo:
        <select name="tipo" required>
            <?php foreach ($tipi_itinerario as $k => $v): ?>
            <option value="<?= $k ?>"><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Numero giorni: <input type="number" name="giorni" min="1" required></label><br><br>

    <label>Descrizione/Programma:<br>
        <textarea name="descrizione" rows="4" cols="50"></textarea>
    </label><br><br>

    <label>Anno di corso:
        <select name="anno_corso" required>
            <?php foreach ($anni_corso as $a): ?>
            <option value="<?= $a ?>"><?= $a ?>°</option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Minimo partecipanti: <input type="number" name="min_partecipanti" min="1" required></label><br><br>
    <label>Massimo partecipanti: <input type="number" name="max_partecipanti" min="1" required></label><br><br>

    <label>Costo per persona (€): <input type="number" name="costo" min="0.01" step="0.01" required></label><br><br>

    <label>Optional vitto:
        <select name="optional_vitto">
            <?php foreach ($optional_vitto as $k => $v): ?>
            <option value="<?= $k ?>"><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <button type="submit">Salva Itinerario</button>
    <a href="itinerari.php">Annulla</a>
</form>

<script>
function toggleDest(val) {
    document.getElementById('dest_esistente').style.display = val === 'esistente' ? '' : 'none';
    document.getElementById('dest_nuova').style.display = val === 'nuova' ? '' : 'none';
}
</script>
</body>
</html>
