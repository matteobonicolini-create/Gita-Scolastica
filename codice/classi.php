<?php
session_start();
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'aggiungi') {
        $nome = trim($_POST['nome'] ?? '');
        $anno = intval($_POST['anno_corso'] ?? 1);
        $sezione = trim($_POST['sezione'] ?? '');
        if (!$nome || !$sezione) redirect('classi.php', 'Nome e sezione sono obbligatori.', 'error');

        $classi = leggi_json('classi.json');
        $classi[] = [
            'id'         => genera_id('cls_'),
            'nome'       => $nome,
            'anno_corso' => $anno,
            'sezione'    => strtoupper($sezione),
        ];
        scrivi_json('classi.json', $classi);
        redirect('classi.php', 'Classe aggiunta.');
    }

    if ($azione === 'elimina') {
        $id = $_POST['id'] ?? '';
        // Controlla se la classe ha prenotazioni
        $prenotazioni = leggi_json('prenotazioni.json');
        foreach ($prenotazioni as $p) {
            if (in_array($id, $p['classi'] ?? []) && $p['stato'] !== 'annullata') {
                redirect('classi.php', 'Impossibile eliminare: la classe ha prenotazioni attive.', 'error');
            }
        }
        $classi = leggi_json('classi.json');
        $classi = array_values(array_filter($classi, fn($c) => $c['id'] !== $id));
        scrivi_json('classi.json', $classi);
        redirect('classi.php', 'Classe eliminata.');
    }
}

$classi = leggi_json('classi.json');
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Classi</title></head>
<body>
<a href="index.php">← Home</a>
<h1>Gestione Classi</h1>
<?php flash(); ?>

<h2>Aggiungi Classe</h2>
<form method="POST" action="classi.php">
    <input type="hidden" name="azione" value="aggiungi">
    <label>Anno di corso:
        <select name="anno_corso">
            <?php foreach ($anni_corso as $a): ?>
            <option value="<?= $a ?>"><?= $a ?>°</option>
            <?php endforeach; ?>
        </select>
    </label><br><br>
    <label>Sezione: <input type="text" name="sezione" maxlength="2" placeholder="A" required></label><br><br>
    <label>Nome esteso (es. 3A Liceo Scientifico): <input type="text" name="nome" required></label><br><br>
    <button type="submit">Aggiungi</button>
</form>

<hr>
<h2>Classi registrate</h2>
<?php if (empty($classi)): ?>
    <p>Nessuna classe presente.</p>
<?php else: ?>
<table border="1" cellpadding="5">
    <tr><th>Anno</th><th>Sezione</th><th>Nome</th><th>Alunni</th><th>Azioni</th></tr>
    <?php
    $alunni = leggi_json('alunni.json');
    foreach ($classi as $c):
        $n_alunni = count(array_filter($alunni, fn($a) => $a['id_classe'] === $c['id']));
    ?>
    <tr>
        <td><?= $c['anno_corso'] ?>°</td>
        <td><?= htmlspecialchars($c['sezione']) ?></td>
        <td><?= htmlspecialchars($c['nome']) ?></td>
        <td><?= $n_alunni ?></td>
        <td>
            <a href="alunni.php?id_classe=<?= $c['id'] ?>">Vedi alunni</a>
            <form method="POST" action="classi.php" style="display:inline">
                <input type="hidden" name="azione" value="elimina">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" onclick="return confirm('Eliminare questa classe?')">Elimina</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>
