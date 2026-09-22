<?php
/**
 * Werkstattbereich: Anmeldung, Übersicht, Auftragsseite, Aufkleber.
 */

declare(strict_types=1);

/** Gemeinsamer Start aller Werkstattseiten: Login prüfen + tägliche Bereinigung. */
function werkstatt_start(): void
{
    login_erforderlich();
    try {
        bereinigung_ausfuehren();
    } catch (Throwable $e) {
        log_schreiben('Bereinigung fehlgeschlagen: ' . $e->getMessage());
    }
}

function seite_login(): void
{
    if (einstellung('passwort_hash') === '') {
        fehlerseite(500, null, 'Noch nicht eingerichtet', 'Es wurde noch kein Passwort festgelegt. Bitte install.php aufrufen.');
    }
    $ziel = sicheres_ziel((string) ($_GET['zurueck'] ?? $_POST['zurueck'] ?? ''));
    if (ist_angemeldet()) {
        weiterleiten($ziel);
    }
    sitzung_starten();
    $fehler = '';
    $gesperrtBis = login_gesperrt_bis();

    if (ist_post() && $gesperrtBis === null) {
        csrf_pruefen();
        if (passwort_pruefen((string) ($_POST['passwort'] ?? ''))) {
            login_versuch_speichern(true);
            anmelden(!empty($_POST['merken']));
            weiterleiten($ziel);
        }
        login_versuch_speichern(false);
        $gesperrtBis = login_gesperrt_bis();
        if ($gesperrtBis === null) {
            $rest = verbleibende_versuche();
            $fehler = 'Das Passwort ist falsch.' . ($rest <= 3 ? ' Noch ' . $rest . ($rest === 1 ? ' Versuch.' : ' Versuche.') : '');
        }
    }
    if ($gesperrtBis !== null) {
        http_response_code(429);
        $fehler = 'Zu viele Fehlversuche. Die Anmeldung ist bis ' . date('H:i', $gesperrtBis) . ' Uhr gesperrt.';
    }

    ansicht('login', ['ziel' => $ziel, 'fehler' => $fehler, 'gesperrt' => $gesperrtBis !== null], 'Anmelden');
}

function seite_logout(): void
{
    abmelden();
    weiterleiten(url('login'));
}

function seite_uebersicht(): void
{
    werkstatt_start();
    $suche = trim((string) ($_GET['q'] ?? ''));
    $mitErledigten = !empty($_GET['erledigte']);
    ansicht('uebersicht', [
        'auftraege' => auftraege_suchen($suche, $mitErledigten),
        'zaehler' => auftrag_zaehler(),
        'suche' => $suche,
        'mitErledigten' => $mitErledigten,
        'warnungOffen' => einstellung_int('warnung_offen_tage', 14),
        'warnungAbholung' => einstellung_int('warnung_abholung_tage', 7),
        'baldGeloescht' => bald_geloeschte_anzahl(30),
    ], 'Übersicht', werkstatt: true);
}

/** Erlaubte Reparaturen für einen Auftrag: aktive Liste + bereits gespeicherte. */
function reparaturen_fuer_auftrag(array $auftrag): array
{
    $namen = array_column(reparatur_liste(), 'name');
    foreach ($auftrag['reparaturen'] as $name) {
        if (!in_array($name, $namen, true)) {
            $namen[] = $name;
        }
    }
    return $namen;
}

function seite_auftrag(int $nummer): void
{
    werkstatt_start();
    $auftrag = auftrag_laden($nummer);
    if (!$auftrag) {
        fehlerseite(404);
    }
    $reparaturen = reparaturen_fuer_auftrag($auftrag);
    $daten = $auftrag;
    $fehler = [];

    if (ist_post()) {
        csrf_pruefen();
        $aktion = (string) ($_POST['aktion'] ?? 'speichern');
        [$daten, $fehler] = auftrag_eingabe_pruefen($_POST, $reparaturen, werkstatt: true);
        if (!$fehler) {
            $meldung = 'Gespeichert.';
            if ($aktion === 'erledigt') {
                $daten['status'] = 'erledigt';
                $meldung = 'Auftrag ' . $nummer . ' ist erledigt – Instrument ausgehändigt.';
            } elseif ($aktion === 'rueckgaengig') {
                $daten['status'] = $auftrag['fertig_am'] ? 'fertig' : 'in_arbeit';
                $meldung = '„Erledigt“ wurde rückgängig gemacht.';
            }
            auftrag_aktualisieren($auftrag, $daten);
            if ($aktion === 'fertig') {
                weiterleiten(url($nummer . '/fertig'));
            }
            meldung($meldung);
            weiterleiten(url((string) $nummer));
        }
        $daten = array_merge($auftrag, $daten);
        meldung('Bitte prüfe die markierten Felder – es wurde noch nichts gespeichert.', 'fehler');
    }

    ansicht('auftrag', [
        'auftrag' => $auftrag,
        'daten' => $daten,
        'fehler' => $fehler,
        'reparaturen' => $reparaturen,
    ], 'Auftrag ' . $nummer, werkstatt: true, koerperKlasse: 'mit-aktionsleiste');
}

/** Bestätigung „Fertig – Kunde benachrichtigen“ mit Mail-Vorschau. */
function seite_fertig(int $nummer): void
{
    werkstatt_start();
    $auftrag = auftrag_laden($nummer);
    if (!$auftrag) {
        fehlerseite(404);
    }

    if (ist_post()) {
        csrf_pruefen();
        $aktion = (string) ($_POST['aktion'] ?? '');
        if ($aktion === 'senden' || $aktion === 'ohne_mail') {
            if ($auftrag['status'] !== 'fertig') {
                auftrag_status_setzen($nummer, 'fertig');
            }
            if ($aktion === 'senden') {
                [$betreff, $text] = vorlage_fuer_auftrag('abholbereit', auftrag_laden($nummer));
                $mailFehler = null;
                if (mail_senden([$auftrag['email']], $betreff, $text, null, $mailFehler)) {
                    auftrag_mail_gesendet($nummer);
                    meldung('Auftrag ist fertig. Die Abhol-Mail wurde an ' . $auftrag['email'] . ' gesendet.');
                } else {
                    meldung('Status ist „fertig“, aber die Mail konnte nicht gesendet werden – Kunde bitte anrufen: ' . $auftrag['telefon'], 'warnung');
                }
            } else {
                meldung('Auftrag als fertig markiert (ohne Mail).');
            }
        }
        weiterleiten(url((string) $nummer));
    }

    [$betreff, $text] = vorlage_fuer_auftrag('abholbereit', $auftrag);
    ansicht('fertig', ['auftrag' => $auftrag, 'betreff' => $betreff, 'text' => $text], 'Fertig melden – ' . $nummer, werkstatt: true);
}

function seite_loeschen(int $nummer): void
{
    werkstatt_start();
    $auftrag = auftrag_laden($nummer);
    if (!$auftrag) {
        weiterleiten(url('uebersicht'));
    }
    if (ist_post()) {
        csrf_pruefen();
        if (($_POST['bestaetigt'] ?? '') === 'ja') {
            auftrag_loeschen($nummer);
            meldung('Auftrag ' . $nummer . ' wurde endgültig gelöscht. Die Nummer ist wieder frei.');
            weiterleiten(url('uebersicht'));
        }
        weiterleiten(url((string) $nummer));
    }
    ansicht('loeschen', ['auftrag' => $auftrag], 'Auftrag löschen – ' . $nummer, werkstatt: true);
}

function seite_aufkleber(): void
{
    werkstatt_start();
    [$bereichVon, $bereichBis] = nummernbereich();
    $von = ctype_digit((string) ($_GET['von'] ?? '')) ? (int) $_GET['von'] : ($bereichVon ?? 1);
    $bis = ctype_digit((string) ($_GET['bis'] ?? '')) ? (int) $_GET['bis'] : $von + 11;
    $start = ctype_digit((string) ($_GET['start'] ?? '')) ? max(1, (int) $_GET['start']) : 1;
    $rahmen = !empty($_GET['rahmen']);
    $fehler = '';
    if ($von < 1 || $bis < $von) {
        $fehler = '„Bis“ muss größer oder gleich „von“ sein.';
    } elseif ($bis - $von >= 1000) {
        $fehler = 'Bitte höchstens 1000 Nummern auf einmal drucken.';
    }
    $ausserhalb = !$fehler && (!nummer_freigegeben($von) || !nummer_freigegeben($bis));
    $belegt = [];
    if (!$fehler) {
        $belegt = db_abfrage('SELECT nummer FROM auftraege WHERE nummer BETWEEN ? AND ? ORDER BY nummer', [$von, $bis])->fetchAll(PDO::FETCH_COLUMN);
    }
    ansicht('aufkleber', [
        'von' => $von, 'bis' => $bis, 'start' => $start, 'rahmen' => $rahmen,
        'fehler' => $fehler, 'ausserhalb' => $ausserhalb, 'belegt' => $belegt,
        'layout' => aufkleber_layout(),
    ], 'Aufkleber drucken', werkstatt: true, koerperKlasse: 'aufkleber-seite');
}
