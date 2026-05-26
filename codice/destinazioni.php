<?php
session_start();
require_once 'includes/functions.php';

// Aggiunta nuova destinazione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'])) {
    if ($_POST['azione'] === 'aggiungi') {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            redirect('destinazioni.php', 'Il nome della destinazione è obbligatorio.', 'error');
        }
        $destinazioni = leggi_json('destinazioni.json');
        // Verifica duplicati
        foreach ($destinazioni as $d) {
            if (strtolower($d['nome']) === strtolower($nome)) {
                redirect('destinazioni.php', 'Destinazione già esistente.', 'error');
            }
        }
        $destinazioni[] = [
            'id'   => genera_id('dest_'),
            'nome' => $nome,
            'note' => trim($_POST['note'] ?? ''),
        ];
        scrivi_json('destinazioni.json', $destinazioni);
        redirect('destinazioni.php', 'Destinazione aggiunta con successo.');
    }

    if ($_POST['azione'] === 'elimina') {
        $id = $_POST['id'] ?? '';
        // Controlla se ci sono itinerari collegati
        $itinerari = leggi_json('itinerari.json');
        foreach ($itinerari as $it) {
            if ($it['id_destinazione'] === $id) {
                redirect('destinazioni.php', 'Impossibile eliminare: esistono itinerari collegati a questa destinazione.', 'error');
            }
        }
        $destinazioni = leggi_json('destinazioni.json');
        $destinazioni = array_values(array_filter($destinazioni, fn($d) => $d['id'] !== $id));
        scrivi_json('destinazioni.json', $destinazioni);
        redirect('destinazioni.php', 'Destinazione eliminata.');
    }
}

$destinazioni = leggi_json('destinazioni.json');
$itinerari = leggi_json('itinerari.json');
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Destinazioni</title></head>
<body>
<a href="index.php">← Home</a>
<h1>Gestione Destinazioni</h1>
<?php flash(); ?>

<h2>Aggiungi Destinazione</h2>
<form method="POST" action="destinazioni.php">
    <input type="hidden" name="azione" value="aggiungi">
    <label>Nome destinazione: <input type="text" name="nome" required></label><br><br>
    <label>Note: <input type="text" name="note"></label><br><br>
    <button type="submit">Aggiungi</button>
</form>

<hr>
<h2>Destinazioni registrate</h2>
<?php if (empty($destinazioni)): ?>
    <p>Nessuna destinazione presente.</p>
<?php else: ?>
    <table border="1" cellpadding="5">
        <tr>
            <th>Nome</th>
            <th>Note</th>
            <th>N° Itinerari</th>
            <th>Azioni</th>
        </tr>
        <?php foreach ($destinazioni as $d):
            $n_it = count(array_filter($itinerari, fn($i) => $i['id_destinazione'] === $d['id']));
        ?>
        <tr>
            <td><?= htmlspecialchars($d['nome']) ?></td>
            <td><?= htmlspecialchars($d['note']) ?></td>
            <td><?= $n_it ?></td>
            <td>
                <?php if ($n_it === 0): ?>
                <form method="POST" action="destinazioni.php" style="display:inline">
                    <input type="hidden" name="azione" value="elimina">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <button type="submit" onclick="return confirm('Eliminare questa destinazione?')">Elimina</button>
                </form>
                <?php else: ?>
                    <em>Ha itinerari</em>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</body>
</html>
