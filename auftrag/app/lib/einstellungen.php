<?php
/**
 * Zugriff auf die Tabelle "einstellungen" (Schlüssel/Wert).
 */

declare(strict_types=1);

function einstellungen_alle(bool $neuLaden = false): array
{
    static $cache = null;
    if ($cache === null || $neuLaden) {
        $cache = [];
        foreach (db_abfrage('SELECT schluessel, wert FROM einstellungen') as $zeile) {
            $cache[$zeile['schluessel']] = $zeile['wert'];
        }
    }
    return $cache;
}

function einstellung(string $schluessel, string $standard = ''): string
{
    return einstellungen_alle()[$schluessel] ?? $standard;
}

function einstellung_int(string $schluessel, int $standard = 0): int
{
    $wert = einstellung($schluessel, (string) $standard);
    return is_numeric($wert) ? (int) $wert : $standard;
}

function einstellung_setzen(string $schluessel, string $wert): void
{
    db_abfrage(
        'INSERT INTO einstellungen (schluessel, wert) VALUES (?, ?) ON DUPLICATE KEY UPDATE wert = VALUES(wert)',
        [$schluessel, $wert]
    );
    einstellungen_alle(true);
}

/**
 * Reparaturliste: [['id' => 'r01', 'name' => '…', 'aktiv' => true], …]
 */
function reparatur_liste(bool $nurAktive = true): array
{
    $liste = json_decode(einstellung('reparaturen', '[]'), true);
    if (!is_array($liste)) {
        return [];
    }
    $liste = array_values(array_filter($liste, fn ($r) => is_array($r) && isset($r['id'], $r['name'])));
    if ($nurAktive) {
        $liste = array_values(array_filter($liste, fn ($r) => !empty($r['aktiv'])));
    }
    return $liste;
}

/** Nummernbereich [von, bis] – jeweils null, wenn nicht begrenzt. */
function nummernbereich(): array
{
    $von = einstellung('nummer_von');
    $bis = einstellung('nummer_bis');
    return [
        ctype_digit($von) ? (int) $von : null,
        ctype_digit($bis) ? (int) $bis : null,
    ];
}

function nummer_freigegeben(int $nummer): bool
{
    [$von, $bis] = nummernbereich();
    return ($von === null || $nummer >= $von) && ($bis === null || $nummer <= $bis);
}

function aufkleber_layout(): array
{
    $standard = [
        'seite_breite' => 210, 'seite_hoehe' => 297,
        'rand_oben' => 13, 'rand_links' => 8,
        'spalten' => 3, 'zeilen' => 8,
        'etikett_breite' => 64, 'etikett_hoehe' => 34,
        'abstand_h' => 3, 'abstand_v' => 0,
        'pro_nummer' => 2,
    ];
    $gespeichert = json_decode(einstellung('aufkleber_layout', '{}'), true);
    return array_merge($standard, is_array($gespeichert) ? $gespeichert : []);
}

/** Satz zur Speicherdauer für den Datenschutzhinweis. */
function speicherdauer_satz(): string
{
    $jahre = einstellung_int('aufbewahrung_jahre', 2);
    if ($jahre <= 0) {
        return 'Danach werden sie gelöscht, sobald sie nicht mehr benötigt werden.';
    }
    return 'Danach werden sie ' . ($jahre === 1 ? 'ein Jahr' : $jahre . ' Jahre')
        . ' nach Erledigung des Auftrags automatisch gelöscht.';
}
