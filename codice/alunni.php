<?php
session_start();
require_once 'includes/functions.php';

$filtro_classe = $_GET['id_classe'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'aggiungi') {
        $cognome = trim($_POST['cognome'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $id_classe = $_POST['id_classe'] ?? '';
        $data_nascita = $_POST['data_nascita'] ?? '';
        if (!$cognome || !$nome || !$id_classe) {
            redirect('alunni.php', 'Cognome, nome e classe sono obbligatori.', 'error');
        }
        $alunni = leggi_json('alunni.json');
        $alunni[] = [
            'id'           => genera_id('alunno_'),
            'cognome'      => $cognome,
            'nome'         => $nome,
            'id_classe'    => $id_classe,
            'data_nascita' => $data_nascita,
            'email_genitore' => trim($_POST['email_genitore'] ?? ''),
        ];
        scrivi_json('alunni.json', $alunni);
        redirect('alunni.php?id_classe=' . $id_classe, 'Alunno aggiunto.');
    }

    if ($azione === 'elimina') {
        $id = $_POST['id'] ?? '';
        $alunni = leggi_json('alunni.json');
        $alunni = array_values(array_filter($alunni, fn($a) => $a['id'] !== $id));
        scrivi_json('alunni.json', $alunni);
        redirect('alunni.php', 'Alunno eliminato.');
    }
}

$alunni = leggi_json('alunni.json');
$classi = leggi_json('classi.json');
$map_cls = [];
foreach ($classi as $c) $map_cls[$c['id']] = $c['nome'];

$alunni_filtrati = $filtro_classe
    ? array_filter($alunni, fn($a) => $a['id_classe'] === $filtro_classe)
    : $alunni;

$classe_corrente = $filtro_classe ? ($map_cls[$filtro_classe] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Alunni</title></head>
<body>
<a href="index.php">← Home</a> | <a href="classi.php">← Classi</a>
<h1>Gestione Alunni<?= $classe_corrente ? " — $classe_corrente" : '' ?></h1>
<?php flash(); ?>

<h2>Aggiungi Alunno</h2>
<form method="POST" action="alunni.php">
    <input type="hidden" name="azione" value="aggiungi">
    <label>Cognome: <input type="text" name="cognome" required></label><br><br>
    <label>Nome: <input type="text" name="nome" required></label><br><br>
    <label>Data di nascita: <input type="date" name="data_nascita"></label><br><br>
    <label>Classe:
        <select name="id_classe" required>
            <option value="">-- seleziona --</option>
            <?php foreach ($classi as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id'] === $filtro_classe ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nome']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label><br><br>
    <label>Email genitore: <input type="email" name="email_genitore"></label><br><br>
    <button type="submit">Aggiungi</button>
</form>

<hr>
<h2>Alunni <?= $filtro_classe ? 'della classe' : 'totali' ?></h2>
<?php if ($filtro_classe): ?>
<a href="alunni.php">Mostra tutti</a><br><br>
<?php else: ?>
Filtra per classe:
<?php foreach ($classi as $c): ?>
<a href="alunni.php?id_classe=<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></a> |
<?php endforeach; ?>
<br><br>
<?php endif; ?>

<?php if (empty($alunni_filtrati)): ?>
    <p>Nessun alunno trovato.</p>
<?php else: ?>
<table border="1" cellpadding="5">
    <tr><th>Cognome</th><th>Nome</th><th>Data nascita</th><th>Classe</th><th>Email genitore</th><th>Azioni</th></tr>
    <?php foreach ($alunni_filtrati as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a['cognome']) ?></td>
        <td><?= htmlspecialchars($a['nome']) ?></td>
        <td><?= htmlspecialchars($a['data_nascita'] ?? '') ?></td>
        <td><?= htmlspecialchars($map_cls[$a['id_classe']] ?? '?') ?></td>
        <td><?= htmlspecialchars($a['email_genitore'] ?? '') ?></td>
        <td>
            <form method="POST" action="alunni.php" style="display:inline">
                <input type="hidden" name="azione" value="elimina">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button type="submit" onclick="return confirm('Eliminare questo alunno?')">Elimina</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>
