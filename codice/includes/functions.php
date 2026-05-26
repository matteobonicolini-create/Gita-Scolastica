<?php
define('DATA_DIR', __DIR__ . '/../data/');

function leggi_json($file) {
    $path = DATA_DIR . $file;
    if (!file_exists($path)) return [];
    $contenuto = file_get_contents($path);
    return json_decode($contenuto, true) ?? [];
}

function scrivi_json($file, $dati) {
    $path = DATA_DIR . $file;
    file_put_contents($path, json_encode($dati, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function genera_id($prefisso = '') {
    return $prefisso . uniqid();
}

function redirect($url, $msg = '', $tipo = 'success') {
    if ($msg) {
        $_SESSION['flash_msg'] = $msg;
        $_SESSION['flash_tipo'] = $tipo;
    }
    header("Location: $url");
    exit;
}

function flash() {
    if (!empty($_SESSION['flash_msg'])) {
        $m = $_SESSION['flash_msg'];
        $t = $_SESSION['flash_tipo'] ?? 'success';
        unset($_SESSION['flash_msg'], $_SESSION['flash_tipo']);
        $colore = $t === 'success' ? 'green' : 'red';
        echo "<p style='color:$colore; font-weight:bold;'>$m</p>";
    }
}

function ha_prenotazioni($id_itinerario) {
    $prenotazioni = leggi_json('prenotazioni.json');
    foreach ($prenotazioni as $p) {
        if ($p['id_itinerario'] === $id_itinerario && $p['stato'] !== 'annullata') {
            return true;
        }
    }
    return false;
}

function get_itinerario($id) {
    $itinerari = leggi_json('itinerari.json');
    foreach ($itinerari as $i) {
        if ($i['id'] === $id) return $i;
    }
    return null;
}

function get_classe($id) {
    $classi = leggi_json('classi.json');
    foreach ($classi as $c) {
        if ($c['id'] === $id) return $c;
    }
    return null;
}

function get_prenotazione($id) {
    $prenotazioni = leggi_json('prenotazioni.json');
    foreach ($prenotazioni as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

function get_alunno($id) {
    $alunni = leggi_json('alunni.json');
    foreach ($alunni as $a) {
        if ($a['id'] === $id) return $a;
    }
    return null;
}

$tipi_itinerario = [
    'regionale'      => 'Regionale',
    'nazionale'      => 'Nazionale',
    'internazionale' => 'Internazionale',
];

$optional_vitto = [
    'incluso'      => 'Vitto incluso',
    'escluso'      => 'Vitto escluso',
    'solo_cena'    => 'Solo cena',
    'solo_pranzo'  => 'Solo pranzo',
    'mezza_pensione' => 'Mezza pensione',
    'pensione_completa' => 'Pensione completa',
];

$anni_corso = [1, 2, 3, 4, 5];

$motivi_annullamento = [
    'atmosferico' => 'Evento atmosferico',
    'bellico'     => 'Evento bellico',
    'didattico'   => 'Motivi didattici',
    'sanitario'   => 'Emergenza sanitaria',
    'altro'       => 'Altro',
];
?>
