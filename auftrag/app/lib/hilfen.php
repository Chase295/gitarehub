<?php
/**
 * Allgemeine Hilfsfunktionen.
 */

declare(strict_types=1);

/** Liest einen Wert aus config.php, z. B. konfig('smtp.host'). */
function konfig(string $schluessel, mixed $standard = null): mixed
{
    $wert = $GLOBALS['KONFIG'] ?? [];
    foreach (explode('.', $schluessel) as $teil) {
        if (!is_array($wert) || !array_key_exists($teil, $wert)) {
            return $standard;
        }
        $wert = $wert[$teil];
    }
    return $wert;
}

function app_geheimnis(): string
{
    $geheimnis = (string) konfig('app_geheimnis', '');
    if (strlen($geheimnis) < 32) {
        throw new RuntimeException('app_geheimnis in config.php fehlt oder ist zu kurz.');
    }
    return $geheimnis;
}

/** Verzeichnis für Logdatei und Sitzungen. */
function daten_verzeichnis(): string
{
    $verzeichnis = konfig('daten_verzeichnis') ?: APP_DIR . '/daten';
    return rtrim((string) $verzeichnis, '/');
}

/** HTML-Escaping für alle Ausgaben. */
function e(mixed $wert): string
{
    return htmlspecialchars((string) $wert, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL-Pfad der Anwendung, z. B. "/auftrag" (ohne abschließenden Schrägstrich). */
function basis_pfad(): string
{
    static $pfad = null;
    if ($pfad === null) {
        if (PHP_SAPI === 'cli') {
            $pfad = rtrim((string) parse_url((string) konfig('basis_url', ''), PHP_URL_PATH), '/');
        } else {
            $pfad = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        }
    }
    return $pfad;
}

/** Relativer Link innerhalb der Anwendung. */
function url(string $pfad = ''): string
{
    return basis_pfad() . '/' . ltrim($pfad, '/');
}

/** Absoluter Link (für E-Mails und QR-Codes). */
function absolute_url(string $pfad = ''): string
{
    return rtrim((string) konfig('basis_url', ''), '/') . '/' . ltrim($pfad, '/');
}

function asset(string $datei): string
{
    $pfad = WEB_DIR . '/assets/' . $datei;
    $version = is_file($pfad) ? (string) filemtime($pfad) : '1';
    return url('assets/' . $datei) . '?v=' . $version;
}

function weiterleiten(string $ziel, int $code = 303): never
{
    header('Location: ' . $ziel, true, $code);
    exit;
}

function ist_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function ist_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $feld, string $standard = ''): string
{
    $wert = $_POST[$feld] ?? $standard;
    return is_string($wert) ? trim($wert) : $standard;
}

function jetzt(): string
{
    return date('Y-m-d H:i:s');
}

function datum(?string $datumZeit, bool $mitUhrzeit = false): string
{
    if (!$datumZeit) {
        return '–';
    }
    $ts = strtotime($datumZeit);
    return $ts ? date($mitUhrzeit ? 'd.m.Y, H:i' : 'd.m.Y', $ts) . ($mitUhrzeit ? ' Uhr' : '') : '–';
}

/** Ganze Tage seit einem Zeitpunkt (Kalendertage). */
function tage_seit(?string $datumZeit): int
{
    if (!$datumZeit) {
        return 0;
    }
    $start = new DateTimeImmutable(substr($datumZeit, 0, 10));
    $heute = new DateTimeImmutable(date('Y-m-d'));
    return max(0, (int) $start->diff($heute)->days);
}

function tage_text(int $tage): string
{
    return match ($tage) {
        0 => 'heute',
        1 => 'seit 1 Tag',
        default => 'seit ' . $tage . ' Tagen',
    };
}

function preis_format(?string $preis): string
{
    if ($preis === null || $preis === '') {
        return '';
    }
    return number_format((float) $preis, 2, ',', '.') . ' €';
}

/** "45,50" / "45.50" / "1.234,50" → "45.50" bzw. null bei leerer Eingabe, false bei Unsinn. */
function preis_einlesen(string $eingabe): string|null|false
{
    $eingabe = trim(str_replace(['€', ' '], '', $eingabe));
    if ($eingabe === '') {
        return null;
    }
    if (str_contains($eingabe, ',')) {
        $eingabe = str_replace(['.', ','], ['', '.'], $eingabe);
    }
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $eingabe)) {
        return false;
    }
    return number_format((float) $eingabe, 2, '.', '');
}

function instrument_typen(): array
{
    return [
        'gitarre'   => 'Gitarre (akustisch/klassisch)',
        'e_gitarre' => 'E-Gitarre',
        'bass'      => 'Bass (akustisch)',
        'e_bass'    => 'E-Bass',
    ];
}

function instrument_name(string $typ): string
{
    return instrument_typen()[$typ] ?? $typ;
}

function status_liste(): array
{
    return [
        'eingegangen' => 'Eingegangen',
        'in_arbeit'   => 'In Arbeit',
        'fertig'      => 'Fertig / abholbereit',
        'erledigt'    => 'Erledigt (ausgehändigt)',
    ];
}

function status_kurz(string $status): string
{
    return [
        'eingegangen' => 'Neu',
        'in_arbeit'   => 'In Arbeit',
        'fertig'      => 'Abholbereit',
        'erledigt'    => 'Erledigt',
    ][$status] ?? $status;
}

/** Gesalzener Hash der Client-IP (es wird nie eine Klar-IP gespeichert). */
function ip_hash(): string
{
    return hash_hmac('sha256', 'ip|' . ($_SERVER['REMOTE_ADDR'] ?? 'unbekannt'), app_geheimnis());
}

/** Liste von E-Mail-Adressen aus kommagetrennter Eingabe. */
function adressliste(string $eingabe): array
{
    $adressen = preg_split('/[\s,;]+/', $eingabe, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return array_values(array_unique($adressen));
}

function ist_email(string $adresse): bool
{
    return filter_var($adresse, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sehr einfaches Textformat für Rechtstexte:
 * "## Überschrift", "- Listenpunkt", Leerzeile = neuer Absatz.
 * Die Eingabe wird vollständig escaped.
 */
function einfach_formatieren(string $text): string
{
    $html = '';
    $bloecke = preg_split("/\n\s*\n/", str_replace("\r", '', trim($text))) ?: [];
    foreach ($bloecke as $block) {
        $zeilen = explode("\n", trim($block));
        if (str_starts_with($zeilen[0], '## ')) {
            $html .= '<h2>' . e(substr(array_shift($zeilen), 3)) . '</h2>';
            if (!$zeilen) {
                continue;
            }
        }
        $alleListe = array_reduce($zeilen, fn ($ok, $z) => $ok && str_starts_with(ltrim($z), '- '), true);
        if ($alleListe) {
            $html .= '<ul>' . implode('', array_map(fn ($z) => '<li>' . e(substr(ltrim($z), 2)) . '</li>', $zeilen)) . '</ul>';
        } else {
            $html .= '<p>' . implode('<br>', array_map('e', $zeilen)) . '</p>';
        }
    }
    return $html;
}
