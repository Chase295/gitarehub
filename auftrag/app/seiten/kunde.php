<?php
/**
 * Öffentliche Seiten: Erfassungsformular, Bestätigung, Rechtliches.
 */

declare(strict_types=1);

/** /auftrag/{nummer}: Formular für freie Nummern, sonst Werkstatt-Ansicht (Login). */
function seite_nummer(int $nummer): void
{
    if (auftrag_existiert($nummer)) {
        if (ist_post() && isset($_POST['formular_token']) && !ist_angemeldet()) {
            // Doppelt abgeschickt oder Seite neu geladen: keine Daten zeigen
            ansicht('bereits_erfasst', ['nummer' => $nummer], 'Auftrag ' . $nummer);
            return;
        }
        login_erforderlich();
        seite_auftrag($nummer);
        return;
    }
    if (!nummer_freigegeben($nummer)) {
        fehlerseite(404, null, 'Nummer nicht freigegeben', 'Die Nummer ' . $nummer . ' ist nicht freigegeben. Bitte wende dich direkt an das Gitarrenstudio.');
    }
    seite_erfassung($nummer);
}

function seite_erfassung(int $nummer): void
{
    $reparaturen = array_column(reparatur_liste(), 'name');
    $daten = [];
    $fehler = [];
    $hinweis = '';

    if (ist_post()) {
        $tokenFehler = formular_token_pruefen($nummer, (string) ($_POST['formular_token'] ?? ''));
        if ((string) ($_POST['webseite'] ?? '') !== '') {
            // Honeypot ausgefüllt → vermutlich Bot
            log_schreiben('Honeypot ausgelöst bei Nummer ' . $nummer);
            fehlerseite(400, null, 'Anfrage abgelehnt', 'Deine Angaben konnten nicht gespeichert werden. Bitte wende dich direkt an das Gitarrenstudio.');
        }
        if (formular_limit_erreicht()) {
            fehlerseite(429, null, 'Zu viele Aufträge', 'Von deinem Anschluss wurden in der letzten Stunde bereits mehrere Aufträge erfasst. Bitte versuche es später noch einmal oder wende dich direkt an das Gitarrenstudio.');
        }
        [$daten, $fehler] = auftrag_eingabe_pruefen($_POST, $reparaturen);
        if (empty($_POST['datenschutz'])) {
            $fehler['datenschutz'] = 'Bitte bestätige, dass du den Datenschutzhinweis gelesen hast.';
        }
        if ($tokenFehler !== null) {
            $hinweis = $tokenFehler;
        }
        if (!$fehler && $tokenFehler === null) {
            if (!auftrag_anlegen($nummer, $daten)) {
                ansicht('bereits_erfasst', ['nummer' => $nummer], 'Auftrag ' . $nummer);
                return;
            }
            formular_eingang_speichern();
            $auftrag = auftrag_laden($nummer);

            [$betreff, $text] = vorlage_fuer_auftrag('eingang', $auftrag);
            $kundenMailOk = mail_senden([$auftrag['email']], $betreff, $text);

            [$betreff, $text] = vorlage_fuer_auftrag('werkstatt', $auftrag);
            mail_senden(adressliste(einstellung('benachrichtigung_empfaenger')), $betreff, $text, $auftrag['email']);

            ansicht('bestaetigung', ['auftrag' => $auftrag, 'kundenMailOk' => $kundenMailOk], 'Danke! Auftrag ' . $nummer);
            return;
        }
        if ($fehler && $hinweis === '') {
            $hinweis = 'Bitte prüfe die markierten Felder.';
        }
    }

    ansicht('erfassung', [
        'nummer' => $nummer,
        'daten' => $daten,
        'fehler' => $fehler,
        'hinweis' => $hinweis,
        'reparaturen' => $reparaturen,
        'token' => formular_token($nummer),
        'datenschutzOk' => !empty($_POST['datenschutz']),
    ], 'Reparaturauftrag ' . $nummer);
}

function seite_start(): void
{
    if (ist_angemeldet()) {
        weiterleiten(url('uebersicht'), 302);
    }
    ansicht('start', [], 'Auftragsverwaltung');
}

function seite_rechtliches(): void
{
    $betreiber = einstellung('betreiber');
    $text = strtr(einstellung('datenschutz_text'), [
        '{betreiber}' => $betreiber,
        '{speicherdauer}' => speicherdauer_satz(),
        '{aufbewahrung_jahre}' => (string) einstellung_int('aufbewahrung_jahre', 2),
    ]);
    ansicht('rechtliches', [
        'betreiber' => $betreiber,
        'datenschutz' => $text,
    ], 'Impressum & Datenschutz', werkstatt: ist_angemeldet());
}
