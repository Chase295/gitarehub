<?php
/**
 * Auftragsverwaltung Pichi's Gitarrenstudio – zentraler Einstiegspunkt.
 * Alle Anfragen unter /auftrag/ werden per .htaccess hierher geleitet.
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require APP_DIR . '/seiten/kunde.php';
require APP_DIR . '/seiten/werkstatt.php';
require APP_DIR . '/seiten/einstellungen.php';

fehlerbehandlung_einrichten();
sicherheits_header_senden();

if (konfig_laden() === null) {
    if (is_file(__DIR__ . '/install.php')) {
        weiterleiten(url('install.php'), 302);
    }
    fehlerseite(500, null, 'Noch nicht eingerichtet', 'Die Anwendung ist noch nicht installiert (config.php fehlt).');
}

// Pfad relativ zur Anwendung ermitteln, z. B. "1234" oder "uebersicht"
$uriPfad = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$pfad = rawurldecode($uriPfad);
$basis = basis_pfad();
if ($basis !== '' && str_starts_with($pfad, $basis)) {
    $pfad = substr($pfad, strlen($basis));
}
$pfad = trim($pfad, '/');
if ($pfad === 'index.php') {
    $pfad = '';
}

// "übersicht" (auch in zerlegter Unicode-Schreibweise) → "uebersicht"
if (in_array(mb_strtolower($pfad), ["\u{00FC}bersicht", "u\u{0308}bersicht"], true)) {
    weiterleiten(url('uebersicht'), 301);
}

// Auftragsnummern: /auftrag/1234, /auftrag/1234/fertig, /auftrag/1234/loeschen
if (preg_match('~^(\d{1,12})(?:/(fertig|loeschen))?$~', $pfad, $treffer)) {
    $normalisiert = ltrim($treffer[1], '0');
    if ($normalisiert === '' || strlen($normalisiert) > 9) {
        fehlerseite(404, null, 'Ungültige Nummer', 'Diese Auftragsnummer ist ungültig. Bitte scanne den QR-Code auf deinem Aufkleber noch einmal.');
    }
    if ($normalisiert !== $treffer[1]) {
        weiterleiten(url($normalisiert . (isset($treffer[2]) ? '/' . $treffer[2] : '')), 301);
    }
    $nummer = (int) $normalisiert;
    match ($treffer[2] ?? '') {
        'fertig' => seite_fertig($nummer),
        'loeschen' => seite_loeschen($nummer),
        default => seite_nummer($nummer),
    };
    exit;
}

match ($pfad) {
    '' => seite_start(),
    'uebersicht' => seite_uebersicht(),
    'einstellungen' => seite_einstellungen(),
    'aufkleber' => seite_aufkleber(),
    'login' => seite_login(),
    'logout' => seite_logout(),
    'rechtliches' => seite_rechtliches(),
    default => fehlerseite(404),
};
