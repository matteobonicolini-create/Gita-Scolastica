<?php
session_start();
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    // Annulla prenotazione (motivi gravi)
    if ($azione === 'annulla') {
        $id = $_POST['id'] ?? '';
        $motivo = $_POST['motivo'] ?? '';
        $motivo_dettaglio = trim($_POST['motivo_dettaglio'] ?? '');
        if (!$motivo) redirect('prenotazioni.php', 'Seleziona un motivo di annullamento.', 'error');

        $prenotazioni = leggi_json('prenotazioni.json');
        foreach ($prenotazioni as &$p) {
            if ($p['id'] === $id) {
                $p['stato'] = 'annullata';
                $p['motivo_annullamento'] = $motivo;
                $p['motivo_dettaglio'] = $motivo_dettaglio;
                $p['data_annullamento'] = date('Y-m-d');
                break;
            }
        }
        scrivi_json('prenotazioni.json', $prenotazioni);
        redirect('prenotazioni.php', 'Prenotazione annullata.');
    }

    // Segna autorizzazioni come raccolte
    if ($azione === 'autorizzazioni') {
        $id = $_POST['id'] ?? '';
        $prenotazioni = leggi_json('prenotazioni.json');
        foreach ($prenotazioni as &$p) {
            if ($p['id'] === $id) {
                $p['autorizzazioni_raccolte'] = true;
                break;
            }
        }
        scrivi_json('prenotazioni.json', $prenotazioni);
        redirect('prenotazioni.php', 'Autorizzazioni segnate come raccolte.');
    }
}

$prenotazioni = leggi_json('prenotazioni.json');
$destinazioni = leggi_json('destinazioni.json');
$classi = leggi_json('classi.json');
$itinerari = leggi_json('itinerari.json');

$map_dest = [];
foreach ($destinazioni as $d) $map_dest[$d['id']] = $d['nome'];
$map_cls = [];
foreach ($classi as $c) $map_cls[$c['id']] = $c['nome'];
$map_it = [];
foreach ($itinerari as $it) $map_it[$it['id']] = $it;

$annulla_id = $_GET['annulla'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head><title>Prenotazioni</title></head>
<body>
<a href="index.php">← Home</a>
<h1>Lista Prenotazioni</h1>
<?php flash(); ?>
<a href="prenotazione_nuova.php">+ Nuova Prenotazione</a>
<br><br>

<?php if (empty($prenotazioni)): ?>
    <p>Nessuna prenotazione presente.</p>
<?php else: ?>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Itinerario / Destinazione</th>
        <th>Classi</th>
        <th>Partecipanti</th>
        <th>Costo totale</th>
        <th>Acconto</th>
        <th>Saldo</th>
        <th>Stato</th>
        <th>Autorizzazioni</th>
        <th>Data</th>
        <th>Azioni</th>
    </tr>
    <?php foreach ($prenotazioni as $p):
        $it = $map_it[$p['id_itinerario']] ?? null;
        $dest_nome = $it ? ($map_dest[$it['id_destinazione']] ?? '?') : '?';
        $classi_nomi = array_map(fn($cid) => $map_cls[$cid] ?? '?', $p['classi']);
        $n_rinunc = count($p['alunni_rinuncianti'] ?? []);
    ?>
    <tr style="<?= $p['stato'] === 'annullata' ? 'color:gray;text-decoration:line-through' : '' ?>">
        <td><?= htmlspecialchars(substr($p['id'], 0, 12)) ?>...</td>
        <td><?= htmlspecialchars($dest_nome) ?> (<?= $it ? $it['giorni'] . 'gg' : '' ?>)</td>
        <td><?= htmlspecialchars(implode(', ', $classi_nomi)) ?></td>
        <td><?= $p['num_partecipanti'] ?> <?= $n_rinunc ? "(-$n_rinunc rinunc.)" : '' ?></td>
        <td>€<?= number_format($p['costo_totale'], 2) ?></td>
        <td>€<?= number_format($p['acconto'], 2) ?></td>
        <td>€<?= number_format($p['saldo'], 2) ?></td>
        <td><strong><?= htmlspecialchars($p['stato']) ?></strong></td>
        <td><?= $p['autorizzazioni_raccolte'] ? '✓ Sì' : '✗ No' ?></td>
        <td><?= $p['data_prenotazione'] ?></td>
        <td>
            <?php if ($p['stato'] !== 'annullata'): ?>
                <a href="prenotazioni.php?annulla=<?= $p['id'] ?>">Annulla</a> |
                <a href="rinuncia.php?id_prenotazione=<?= $p['id'] ?>">Rinuncia alunno</a>
                <?php if (!$p['autorizzazioni_raccolte']): ?>
                | <form method="POST" action="prenotazioni.php" style="display:inline">
                    <input type="hidden" name="azione" value="autorizzazioni">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit">Segna autorizzazioni</button>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <em>Motivo: <?= htmlspecialchars($motivi_annullamento[$p['motivo_annullamento']] ?? $p['motivo_annullamento'] ?? '') ?></em>
            <?php endif; ?>
        </td>
    </tr>

    <?php // Form annullamento inline
    if ($annulla_id === $p['id'] && $p['stato'] !== 'annullata'): ?>
    <tr>
        <td colspan="11" style="background:#fff3cd; padding:10px;">
            <strong>Annullamento prenotazione <?= htmlspecialchars(substr($p['id'], 0, 12)) ?>...</strong><br>
            <form method="POST" action="prenotazioni.php">
                <input type="hidden" name="azione" value="annulla">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <label>Motivo:
                    <select name="motivo" required>
                        <option value="">-- seleziona --</option>
                        <?php foreach ($motivi_annullamento as $k => $v): ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Dettaglio: <input type="text" name="motivo_dettaglio" size="40"></label>
                <button type="submit">Conferma Annullamento</button>
                <a href="prenotazioni.php">Annulla</a>
            </form>
        </td>
    </tr>
    <?php endif; ?>

    <?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>
