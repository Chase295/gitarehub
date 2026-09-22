<?php
/**
 * Datenzugriff für Aufträge und automatische Bereinigung.
 */

declare(strict_types=1);

const AUFTRAG_FELDER = [
    'vorname', 'nachname', 'strasse_hausnr', 'plz', 'ort', 'email', 'telefon',
    'instrument_typ', 'marke_modell', 'farbe', 'seriennummer', 'zubehoer', 'beschreibung',
];

function auftrag_aufbereiten(array $zeile): array
{
    $liste = json_decode((string) $zeile['reparaturen'], true);
    $zeile['reparaturen'] = is_array($liste) ? array_values(array_map('strval', $liste)) : [];
    $zeile['nummer'] = (int) $zeile['nummer'];
    return $zeile;
}

function auftrag_laden(int $nummer): ?array
{
    $zeile = db_abfrage('SELECT * FROM auftraege WHERE nummer = ?', [$nummer])->fetch();
    return $zeile ? auftrag_aufbereiten($zeile) : null;
}

function auftrag_existiert(int $nummer): bool
{
    return (bool) db_abfrage('SELECT 1 FROM auftraege WHERE nummer = ?', [$nummer])->fetchColumn();
}

/** Legt einen Auftrag an. Gibt false zurück, wenn die Nummer bereits vergeben ist. */
function auftrag_anlegen(int $nummer, array $daten): bool
{
    $jetzt = jetzt();
    $spalten = array_merge(['nummer', 'status'], AUFTRAG_FELDER, ['reparaturen', 'datenschutz_ok_am', 'erstellt_am', 'geaendert_am']);
    $werte = [$nummer, 'eingegangen'];
    foreach (AUFTRAG_FELDER as $feld) {
        $werte[] = ($daten[$feld] ?? '') === '' ? null : $daten[$feld];
    }
    array_push($werte, json_encode(array_values($daten['reparaturen']), JSON_UNESCAPED_UNICODE), $jetzt, $jetzt, $jetzt);
    try {
        db_abfrage(
            'INSERT INTO auftraege (' . implode(', ', $spalten) . ') VALUES (' . implode(', ', array_fill(0, count($spalten), '?')) . ')',
            $werte
        );
        return true;
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return false; // Nummer bereits vergeben (Doppel-Absendung)
        }
        throw $e;
    }
}

/**
 * Speichert Änderungen aus der Werkstatt inkl. Status. Zeitstempel
 * fertig_am/erledigt_am werden passend zum Status gepflegt.
 */
function auftrag_aktualisieren(array $alt, array $daten): void
{
    $status = $daten['status'] ?? $alt['status'];
    $fertigAm = $alt['fertig_am'];
    $erledigtAm = $alt['erledigt_am'];
    if (in_array($status, ['fertig', 'erledigt'], true) && !$fertigAm) {
        $fertigAm = jetzt();
    }
    if ($status === 'erledigt' && !$erledigtAm) {
        $erledigtAm = jetzt();
    }
    if ($status !== 'erledigt') {
        $erledigtAm = null;
    }
    if (in_array($status, ['eingegangen', 'in_arbeit'], true)) {
        $fertigAm = null;
    }

    $sets = [];
    $werte = [];
    foreach (AUFTRAG_FELDER as $feld) {
        $sets[] = $feld . ' = ?';
        $werte[] = ($daten[$feld] ?? '') === '' ? null : $daten[$feld];
    }
    $sets[] = 'reparaturen = ?';
    $werte[] = json_encode(array_values($daten['reparaturen']), JSON_UNESCAPED_UNICODE);
    array_push($sets, 'status = ?', 'notiz_intern = ?', 'preis = ?', 'fertig_am = ?', 'erledigt_am = ?', 'geaendert_am = ?');
    array_push($werte, $status, ($daten['notiz_intern'] ?? '') === '' ? null : $daten['notiz_intern'], $daten['preis'] ?? null, $fertigAm, $erledigtAm, jetzt());
    $werte[] = $alt['nummer'];
    db_abfrage('UPDATE auftraege SET ' . implode(', ', $sets) . ' WHERE nummer = ?', $werte);
}

function auftrag_status_setzen(int $nummer, string $status): void
{
    $alt = auftrag_laden($nummer);
    if (!$alt) {
        return;
    }
    $daten = $alt;
    $daten['status'] = $status;
    auftrag_aktualisieren($alt, $daten);
}

function auftrag_mail_gesendet(int $nummer): void
{
    db_abfrage('UPDATE auftraege SET mail_gesendet_am = ? WHERE nummer = ?', [jetzt(), $nummer]);
}

function auftrag_loeschen(int $nummer): void
{
    db_abfrage('DELETE FROM auftraege WHERE nummer = ?', [$nummer]);
}

/** Aufträge für die Übersicht (optional mit Suche und Erledigten). */
function auftraege_suchen(string $suche, bool $mitErledigten): array
{
    $bedingungen = [];
    $werte = [];
    if (!$mitErledigten) {
        $bedingungen[] = "status <> 'erledigt'";
    }
    $suche = trim($suche);
    if ($suche !== '') {
        $teil = [];
        if (ctype_digit($suche)) {
            $teil[] = 'nummer = ?';
            $werte[] = (int) $suche;
        }
        $muster = '%' . addcslashes($suche, '%_\\') . '%';
        foreach (['vorname', 'nachname', "CONCAT(vorname, ' ', nachname)", 'marke_modell', 'telefon', 'email'] as $spalte) {
            $teil[] = $spalte . ' LIKE ?';
            $werte[] = $muster;
        }
        // Telefonnummern auch ohne Leer-/Sonderzeichen finden
        $ziffern = preg_replace('/\D/', '', $suche);
        if (strlen((string) $ziffern) >= 4) {
            $teil[] = "REPLACE(REPLACE(REPLACE(REPLACE(telefon, ' ', ''), '/', ''), '-', ''), '+49', '0') LIKE ?";
            $werte[] = '%' . $ziffern . '%';
        }
        $bedingungen[] = '(' . implode(' OR ', $teil) . ')';
    }
    // Abholbereite zuerst, dann neue und in Arbeit (älteste oben), Erledigte zuletzt (neueste oben)
    $sql = 'SELECT * FROM auftraege'
        . ($bedingungen ? ' WHERE ' . implode(' AND ', $bedingungen) : '')
        . " ORDER BY FIELD(status, 'fertig', 'eingegangen', 'in_arbeit', 'erledigt'),"
        . " CASE WHEN status = 'erledigt' THEN NULL ELSE erstellt_am END ASC, erledigt_am DESC";
    return array_map('auftrag_aufbereiten', db_abfrage($sql, $werte)->fetchAll());
}

function auftrag_zaehler(): array
{
    $zaehler = ['eingegangen' => 0, 'in_arbeit' => 0, 'fertig' => 0, 'erledigt' => 0];
    foreach (db_abfrage('SELECT status, COUNT(*) AS anzahl FROM auftraege GROUP BY status') as $zeile) {
        $zaehler[$zeile['status']] = (int) $zeile['anzahl'];
    }
    return $zaehler;
}

/** Grenzdatum: erledigt vor diesem Zeitpunkt → wird gelöscht. null = nie löschen. */
function loeschgrenze(int $vorlaufTage = 0): ?string
{
    $jahre = einstellung_int('aufbewahrung_jahre', 2);
    if ($jahre <= 0) {
        return null;
    }
    $grenze = (new DateTimeImmutable())->modify('-' . $jahre . ' years');
    if ($vorlaufTage > 0) {
        $grenze = $grenze->modify('+' . $vorlaufTage . ' days');
    }
    return $grenze->format('Y-m-d H:i:s');
}

/** Anzahl erledigter Aufträge, die in den nächsten N Tagen gelöscht werden. */
function bald_geloeschte_anzahl(int $tage = 30): int
{
    $grenze = loeschgrenze($tage);
    if ($grenze === null) {
        return 0;
    }
    return (int) db_abfrage(
        "SELECT COUNT(*) FROM auftraege WHERE status = 'erledigt' AND erledigt_am IS NOT NULL AND erledigt_am < ?",
        [$grenze]
    )->fetchColumn();
}

/**
 * Löscht erledigte Aufträge, deren erledigt_am älter als die eingestellte
 * Aufbewahrungsdauer ist. Offene Aufträge werden nie gelöscht.
 * Ohne $erzwingen läuft die Bereinigung höchstens einmal pro Tag.
 * Gibt die Anzahl gelöschter Aufträge zurück (null = heute schon gelaufen).
 */
function bereinigung_ausfuehren(bool $erzwingen = false): ?int
{
    if (!$erzwingen && str_starts_with(einstellung('letzte_bereinigung_am'), date('Y-m-d'))) {
        return null;
    }
    $geloescht = 0;
    $grenze = loeschgrenze();
    if ($grenze !== null) {
        $geloescht = db_abfrage(
            "DELETE FROM auftraege WHERE status = 'erledigt' AND erledigt_am IS NOT NULL AND erledigt_am < ?",
            [$grenze]
        )->rowCount();
    }
    db_abfrage('DELETE FROM login_versuche WHERE zeitpunkt < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    db_abfrage('DELETE FROM formular_eingaenge WHERE zeitpunkt < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    einstellung_setzen('letzte_bereinigung_am', jetzt());
    einstellung_setzen('letzte_bereinigung_anzahl', (string) $geloescht);
    if ($geloescht > 0) {
        log_schreiben('Automatische Bereinigung: ' . $geloescht . ' erledigte Aufträge gelöscht.');
    }
    return $geloescht;
}
