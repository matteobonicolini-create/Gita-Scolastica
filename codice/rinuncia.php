<?php
session_start();
require_once 'includes/functions.php';

$id_prenotazione = $_GET['id_prenotazione'] ?? $_POST['id_prenotazione'] ?? '';
if (!$id_prenotazione) redirect('prenotazioni.php', 'ID prenotazione mancante.', 'error');

$pren = get_prenotazione($id_prenotazione);
if (!$pren) redirect('prenotazioni.php', 'Prenotazione non trovata.', 'error');
if ($pren['stato'] === 'annullata') redirect('prenotazioni.php', 'Prenotazione già annullata.', 'error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_alunno = $_POST['id_alunno'] ?? '';
    $doc_medica = ($_POST['doc_medica'] ?? '') === '1';
    $motivo = trim($_POST['motivo'] ?? '');

    if (!$id_alunno) redirect("rinuncia.php?id_prenotazione=$id_prenotazione", 'Seleziona un alunno.', 'error');
    if (!$doc_medica) redirect("rinuncia.php?id_prenotazione=$id_prenotazione", 'La documentazione medica è obbligatoria per la rinuncia.', 'error');

    // Controlla che non sia già rinunciante
    $gia_rinunciante = array_filter($pren['alunni_rinuncianti'] ?? [], fn($r) => $r['id_alunno'] === $id_alunno);
    if (!empty($gia_rinunciante)) {
        redirect("rinuncia.php?id_prenotazione=$id_prenotazione", 'Questo alunno ha già rinunciato.', 'error');
    }

    // Calcola nuovo costo ripartito
    $num_attuali = $pren['num_partecipanti'] - count($pren['alunni_rinuncianti'] ?? []);
    $num_dopo = $num_attuali - 1;

    if ($num_dopo <= 0) {
        redirect('prenotazioni.php', 'Non è possibile rimuovere tutti i partecipanti.', 'error');
    }

    // Il costo del rinunciante viene spalmato sul resto
    $costo_totale_originale = $pren['costo_totale'];
    // Costo unitario aggiornato: divido il totale per i rimanenti
    $nuovo_costo_unitario = round($costo_totale_originale / $num_dopo, 2);
    // Acconto rimane uguale (già versato), ricalcolo il saldo
    $nuovo_saldo = round($costo_totale_originale - $pren['acconto'], 2);

    $prenotazioni = leggi_json('prenotazioni.json');
    foreach ($prenotazioni as &$p) {
        if ($p['id'] === $id_prenotazione) {
            $p['alunni_rinuncianti'][] = [
                'id_alunno'     => $id_alunno,
                'data_rinuncia' => date('Y-m-d'),
                'motivo'        => $motivo,
                'doc_medica'    => true,
            ];
            $p['costo_unitario_aggiornato'] = $nuovo_costo_unitario;
            $p['saldo'] = $nuovo_saldo;
            $p['nota_ripartizione'] = "Costo ripartito su $num_dopo partecipanti: €" . number_format($nuovo_costo_unitario, 2) . " a testa";
            break;
        }
    }
    scrivi_json('prenotazioni.json', $prenotazioni);
    redirect('prenotazioni.php', "Rinuncia registrata. Nuovo costo a persona: €" . number_format($nuovo_costo_unitario, 2) . " (ripartito su $num_dopo partecipanti).");
}

// Recupera alunni delle classi della prenotazione
$alunni = leggi_json('alunni.json');
$alunni_prenotazione = array_filter($alunni, fn($a) => in_array($a['id_classe'], $pren['classi']));
$id_rinuncianti = array_column($pren['alunni_rinuncianti'] ?? [], 'id_alunno');
$alunni_disponibili = array_filter($alunni_prenotazione, fn($a) => !in_array($a['id'], $id_rinuncianti));

$it = get_itinerario($pren['id_itinerario']);
$destinazioni = leggi_json('destinazioni.json');
$map_dest = [];
foreach ($destinazioni as $d) $map_dest[$d['id']] = $d['nome'];
$dest_nome = $it ? ($map_dest[$it['id_destinazione']] ?? '?') : '?';

$num_attuali = $pren['num_partecipanti'] - count($id_rinuncianti);
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Rinuncia Alunno</title></head>
<body>
<a href="prenotazioni.php">← Lista Prenotazioni</a>
<h1>Rinuncia Alunno</h1>
<?php flash(); ?>

<h3>Prenotazione: <?= htmlspecialchars($dest_nome) ?></h3>
<p>
    Partecipanti attivi: <strong><?= $num_attuali ?></strong> |
    Costo totale: <strong>€<?= number_format($pren['costo_totale'], 2) ?></strong>
</p>

<?php if (!empty($pren['alunni_rinuncianti'])): ?>
<p><strong>Rinuncianti già registrati:</strong></p>
<ul>
    <?php foreach ($pren['alunni_rinuncianti'] as $r):
        $a = get_alunno($r['id_alunno']);
        $nome_a = $a ? "{$a['cognome']} {$a['nome']}" : $r['id_alunno'];
    ?>
    <li><?= htmlspecialchars($nome_a) ?> — <?= $r['data_rinuncia'] ?></li>
    <?php endforeach; ?>
</ul>
<?php if (!empty($pren['nota_ripartizione'])): ?>
<p><em><?= htmlspecialchars($pren['nota_ripartizione']) ?></em></p>
<?php endif; ?>
<?php endif; ?>

<hr>
<h3>Registra nuova rinuncia</h3>

<?php if (empty($alunni_disponibili)): ?>
    <p>Nessun alunno disponibile (tutti hanno già rinunciato o non ci sono alunni nelle classi coinvolte).</p>
<?php else: ?>
<form method="POST" action="rinuncia.php">
    <input type="hidden" name="id_prenotazione" value="<?= $id_prenotazione ?>">

    <label>Alunno rinunciante:
        <select name="id_alunno" required>
            <option value="">-- seleziona --</option>
            <?php foreach ($alunni_disponibili as $a): ?>
            <option value="<?= $a['id'] ?>"><?= htmlspecialchars("{$a['cognome']} {$a['nome']}") ?></option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>
        <input type="checkbox" name="doc_medica" value="1" required>
        Documentazione medica presentata e verificata
    </label><br><br>

    <label>Motivo della rinuncia:<br>
        <textarea name="motivo" rows="3" cols="40"></textarea>
    </label><br><br>

    <?php if ($num_attuali > 1): ?>
    <p style="color:orange;">
        <strong>Nota:</strong> Rimuovendo questo alunno, il costo totale di €<?= number_format($pren['costo_totale'], 2) ?>
        verrà ripartito sui <?= $num_attuali - 1 ?> partecipanti rimanenti:
        <strong>€<?= number_format($pren['costo_totale'] / ($num_attuali - 1), 2) ?> a testa</strong>.
    </p>
    <?php else: ?>
    <p style="color:red;">Attenzione: questo è l'ultimo partecipante.</p>
    <?php endif; ?>

    <button type="submit">Registra Rinuncia</button>
    <a href="prenotazioni.php">Annulla</a>
</form>
<?php endif; ?>
</body>
</html>
