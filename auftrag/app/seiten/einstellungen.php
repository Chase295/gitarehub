<?php
/**
 * Einstellungsseite der Werkstatt.
 */

declare(strict_types=1);

function seite_einstellungen(): void
{
    werkstatt_start();
    $fehler = [];
    $werte = einstellungen_alle();

    if (ist_post()) {
        csrf_pruefen();
        $aktion = (string) ($_POST['aktion'] ?? 'speichern');

        if ($aktion === 'passwort') {
            einstellungen_passwort_aendern();
            weiterleiten(url('einstellungen') . '#passwort');
        }
        if ($aktion === 'alle_abmelden') {
            alle_geraete_abmelden(diesesGeraetBehalten: true);
            meldung('Alle anderen Geräte wurden abgemeldet.');
            weiterleiten(url('einstellungen') . '#sicherheit');
        }
        if ($aktion === 'bereinigung') {
            $anzahl = (int) bereinigung_ausfuehren(erzwingen: true);
            meldung('Bereinigung ausgeführt: ' . $anzahl . ' ' . ($anzahl === 1 ? 'Auftrag' : 'Aufträge') . ' gelöscht.');
            weiterleiten(url('einstellungen') . '#aufbewahrung');
        }

        // Speichern (auch vor einer Test-Mail)
        [$neu, $fehler] = einstellungen_pruefen($_POST);
        if (!$fehler) {
            foreach ($neu as $schluessel => $wert) {
                einstellung_setzen($schluessel, $wert);
            }
            if (str_starts_with($aktion, 'test_')) {
                einstellungen_testmail(substr($aktion, 5));
                weiterleiten(url('einstellungen') . '#mails');
            }
            meldung('Einstellungen gespeichert.');
            weiterleiten(url('einstellungen'));
        }
        $werte = array_merge($werte, $neu);
        meldung('Bitte prüfe die markierten Felder – es wurde noch nichts gespeichert.', 'fehler');
    }

    ansicht('einstellungen', [
        'werte' => $werte,
        'fehler' => $fehler,
        'reparaturen' => isset($neu['reparaturen']) ? (json_decode($neu['reparaturen'], true) ?: []) : reparatur_liste(false),
        'layout' => isset($neu['aufkleber_layout']) ? json_decode($neu['aufkleber_layout'], true) : aufkleber_layout(),
    ], 'Einstellungen', werkstatt: true, koerperKlasse: 'mit-aktionsleiste');
}

/** Prüft und normalisiert die Formularwerte. Gibt [werte, fehler] zurück. */
function einstellungen_pruefen(array $p): array
{
    $neu = [];
    $fehler = [];
    $t = fn (string $f) => trim(str_replace("\r", '', (string) ($p[$f] ?? '')));

    // Empfänger
    $adressen = adressliste($t('benachrichtigung_empfaenger'));
    foreach ($adressen as $adresse) {
        if (!ist_email($adresse)) {
            $fehler['benachrichtigung_empfaenger'] = 'Ungültige Adresse: ' . $adresse;
        }
    }
    if (!$adressen) {
        $fehler['benachrichtigung_empfaenger'] = 'Bitte mindestens eine Adresse angeben.';
    }
    $neu['benachrichtigung_empfaenger'] = implode(', ', $adressen);

    // Nummernbereich
    foreach (['nummer_von', 'nummer_bis'] as $feld) {
        $wert = ltrim($t($feld), '0');
        if ($wert !== '' && (!ctype_digit($wert) || strlen($wert) > 9)) {
            $fehler[$feld] = 'Bitte eine positive ganze Zahl eingeben oder leer lassen.';
        }
        $neu[$feld] = $wert;
    }
    if (!isset($fehler['nummer_von']) && !isset($fehler['nummer_bis']) && $neu['nummer_von'] !== '' && $neu['nummer_bis'] !== ''
        && (int) $neu['nummer_von'] > (int) $neu['nummer_bis']) {
        $fehler['nummer_bis'] = '„Bis“ muss größer oder gleich „von“ sein.';
    }

    // Zahlen
    foreach (['aufbewahrung_jahre' => [0, 30], 'warnung_offen_tage' => [1, 365], 'warnung_abholung_tage' => [1, 365]] as $feld => [$min, $max]) {
        $wert = $t($feld);
        if (!ctype_digit($wert) || (int) $wert < $min || (int) $wert > $max) {
            $fehler[$feld] = 'Bitte eine Zahl von ' . $min . ' bis ' . $max . ' eingeben.';
        }
        $neu[$feld] = $wert;
    }

    // Reparaturliste
    $liste = [];
    $zeilen = is_array($p['rep'] ?? null) ? $p['rep'] : [];
    $i = 0;
    foreach ($zeilen as $zeile) {
        $i++;
        if (!is_array($zeile)) {
            continue;
        }
        $name = trim(preg_replace('/\s+/u', ' ', (string) ($zeile['name'] ?? '')) ?? '');
        if ($name === '') {
            continue;
        }
        $id = (string) ($zeile['id'] ?? '');
        $liste[] = [
            'id' => preg_match('/^r[a-z0-9]{1,12}$/', $id) ? $id : 'r' . bin2hex(random_bytes(4)),
            'name' => mb_substr($name, 0, 120),
            'aktiv' => !empty($zeile['aktiv']),
            'pos' => is_numeric($zeile['pos'] ?? null) ? (float) $zeile['pos'] : 1000 + $i,
        ];
    }
    usort($liste, fn ($a, $b) => $a['pos'] <=> $b['pos']);
    $liste = array_map(fn ($r) => ['id' => $r['id'], 'name' => $r['name'], 'aktiv' => $r['aktiv']], $liste);
    if (count(array_unique(array_map('mb_strtolower', array_column($liste, 'name')))) !== count($liste)) {
        $fehler['reparaturen'] = 'Jede Reparatur darf nur einmal vorkommen.';
    }
    if (!array_filter($liste, fn ($r) => $r['aktiv'])) {
        $fehler['reparaturen'] = 'Mindestens eine Reparatur muss aktiv sein.';
    }
    $neu['reparaturen'] = json_encode($liste, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // Texte
    $neu['oeffnungszeiten'] = mb_substr($t('oeffnungszeiten'), 0, 500);
    foreach (array_keys(mail_vorlagen()) as $vorlage) {
        foreach (['betreff' => 200, 'text' => 10000] as $teil => $max) {
            $feld = 'mail_' . $vorlage . '_' . $teil;
            $neu[$feld] = mb_substr($t($feld), 0, $max);
            if ($neu[$feld] === '') {
                $fehler[$feld] = 'Darf nicht leer sein.';
            }
        }
    }
    $neu['testmail_an'] = $t('testmail_an');
    if ($neu['testmail_an'] !== '' && !ist_email($neu['testmail_an'])) {
        $fehler['testmail_an'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
    }
    $neu['betreiber'] = mb_substr($t('betreiber'), 0, 2000);
    $neu['datenschutz_text'] = mb_substr($t('datenschutz_text'), 0, 20000);
    if ($neu['betreiber'] === '') {
        $fehler['betreiber'] = 'Die Betreiberdaten sind Pflicht (Impressum).';
    }
    if ($neu['datenschutz_text'] === '') {
        $fehler['datenschutz_text'] = 'Der Datenschutzhinweis darf nicht leer sein.';
    }

    // Aufkleber-Layout
    $layout = [];
    $grenzen = [
        'seite_breite' => [50, 500], 'seite_hoehe' => [50, 500],
        'rand_oben' => [0, 100], 'rand_links' => [0, 100],
        'spalten' => [1, 20], 'zeilen' => [1, 40],
        'etikett_breite' => [10, 300], 'etikett_hoehe' => [10, 300],
        'abstand_h' => [0, 50], 'abstand_v' => [0, 50],
        'pro_nummer' => [1, 2],
    ];
    foreach ($grenzen as $feld => [$min, $max]) {
        $wert = str_replace(',', '.', $t('layout_' . $feld));
        if (!is_numeric($wert) || (float) $wert < $min || (float) $wert > $max) {
            $fehler['layout_' . $feld] = $min . '–' . $max;
            $layout[$feld] = $wert;
            continue;
        }
        $layout[$feld] = in_array($feld, ['spalten', 'zeilen', 'pro_nummer'], true) ? (int) $wert : round((float) $wert, 1);
    }
    $neu['aufkleber_layout'] = json_encode($layout);

    return [$neu, $fehler];
}

function einstellungen_testmail(string $vorlage): void
{
    if (!array_key_exists($vorlage, mail_vorlagen())) {
        return;
    }
    $an = einstellung('testmail_an') ?: (adressliste(einstellung('benachrichtigung_empfaenger'))[0] ?? '');
    if ($an === '') {
        meldung('Bitte zuerst eine Adresse für Test-Mails eintragen.', 'fehler');
        return;
    }
    [$betreff, $text] = vorlage_fuer_auftrag($vorlage, beispiel_auftrag());
    $fehler = null;
    if (mail_senden([$an], '[TEST] ' . $betreff, $text, null, $fehler)) {
        meldung('Einstellungen gespeichert. Test-Mail „' . mail_vorlagen()[$vorlage] . '“ wurde an ' . $an . ' gesendet.');
    } else {
        meldung('Die Test-Mail konnte nicht gesendet werden: ' . $fehler . ' – bitte SMTP-Zugangsdaten in config.php prüfen.', 'fehler');
    }
}

function einstellungen_passwort_aendern(): void
{
    $alt = (string) ($_POST['passwort_alt'] ?? '');
    $neu = (string) ($_POST['passwort_neu'] ?? '');
    $wdh = (string) ($_POST['passwort_wdh'] ?? '');
    if (!passwort_pruefen($alt)) {
        meldung('Das bisherige Passwort ist falsch. Das Passwort wurde nicht geändert.', 'fehler');
        return;
    }
    if (mb_strlen($neu) < 8) {
        meldung('Das neue Passwort muss mindestens 8 Zeichen lang sein.', 'fehler');
        return;
    }
    if ($neu !== $wdh) {
        meldung('Die beiden neuen Passwörter stimmen nicht überein.', 'fehler');
        return;
    }
    einstellung_setzen('passwort_hash', passwort_hash_erzeugen($neu));
    alle_geraete_abmelden(diesesGeraetBehalten: true);
    meldung('Passwort geändert. Alle anderen Geräte wurden abgemeldet.');
}
