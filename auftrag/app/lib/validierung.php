<?php
/**
 * Serverseitige Prüfung der Auftragsdaten.
 */

declare(strict_types=1);

/**
 * Prüft die Formulardaten eines Auftrags.
 *
 * @param array $erlaubteReparaturen Namen, die angehakt werden dürfen
 * @param bool  $werkstatt           zusätzlich Werkstattfelder (Status, Notiz, Preis) prüfen
 * @return array{0: array, 1: array<string,string>} [Daten, Fehler je Feld]
 */
function auftrag_eingabe_pruefen(array $eingabe, array $erlaubteReparaturen, bool $werkstatt = false): array
{
    $text = fn (string $feld, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/u', '', (string) ($eingabe[$feld] ?? '')) ?? ''), 0, $max);
    $einzeilig = fn (string $feld, int $max) => preg_replace('/\s+/u', ' ', $text($feld, $max)) ?? '';

    $daten = [
        'vorname' => $einzeilig('vorname', 100),
        'nachname' => $einzeilig('nachname', 100),
        'strasse_hausnr' => $einzeilig('strasse_hausnr', 150),
        'plz' => $einzeilig('plz', 10),
        'ort' => $einzeilig('ort', 100),
        'email' => mb_strtolower($einzeilig('email', 200)),
        'telefon' => $einzeilig('telefon', 40),
        'instrument_typ' => $einzeilig('instrument_typ', 30),
        'marke_modell' => $einzeilig('marke_modell', 150),
        'farbe' => $einzeilig('farbe', 50),
        'seriennummer' => $einzeilig('seriennummer', 100),
        'zubehoer' => $einzeilig('zubehoer', 255),
        'beschreibung' => $text('beschreibung', 5000),
        'reparaturen' => [],
    ];
    $fehler = [];

    foreach (['vorname' => 'Vorname', 'nachname' => 'Nachname', 'strasse_hausnr' => 'Straße und Hausnummer', 'ort' => 'Ort'] as $feld => $name) {
        if ($daten[$feld] === '') {
            $fehler[$feld] = 'Bitte ' . $name . ' angeben.';
        }
    }
    if (!preg_match('/^\d{4,5}$/', $daten['plz'])) {
        $fehler['plz'] = 'Bitte eine gültige Postleitzahl (4–5 Ziffern) angeben.';
    }
    if (!ist_email($daten['email'])) {
        $fehler['email'] = 'Bitte eine gültige E-Mail-Adresse angeben.';
    }
    if (!preg_match('/^\+?[0-9 ()\/.\-]{5,39}$/', $daten['telefon']) || strlen((string) preg_replace('/\D/', '', $daten['telefon'])) < 5) {
        $fehler['telefon'] = 'Bitte eine gültige Telefonnummer angeben.';
    }
    if (!array_key_exists($daten['instrument_typ'], instrument_typen())) {
        $fehler['instrument_typ'] = 'Bitte das Instrument auswählen.';
    }

    $gewaehlt = $eingabe['reparaturen'] ?? [];
    if (is_array($gewaehlt)) {
        foreach ($gewaehlt as $name) {
            if (is_string($name) && in_array($name, $erlaubteReparaturen, true)) {
                $daten['reparaturen'][] = $name;
            }
        }
    }
    $daten['reparaturen'] = array_values(array_unique($daten['reparaturen']));
    if (!$daten['reparaturen']) {
        $fehler['reparaturen'] = 'Bitte mindestens eine Reparatur auswählen.';
    }

    if ($werkstatt) {
        $daten['status'] = (string) ($eingabe['status'] ?? '');
        if (!array_key_exists($daten['status'], status_liste())) {
            $fehler['status'] = 'Ungültiger Status.';
        }
        $daten['notiz_intern'] = $text('notiz_intern', 5000);
        $preis = preis_einlesen((string) ($eingabe['preis'] ?? ''));
        if ($preis === false) {
            $fehler['preis'] = 'Bitte einen gültigen Betrag eingeben, z. B. 45,50.';
            $daten['preis'] = null;
            $daten['preis_eingabe'] = (string) ($eingabe['preis'] ?? '');
        } else {
            $daten['preis'] = $preis;
        }
    }

    return [$daten, $fehler];
}
