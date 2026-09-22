<?php
/**
 * Anmeldung, Sitzung, "Angemeldet bleiben", CSRF und Brute-Force-Schutz.
 *
 * Kunden bekommen keine Cookies: Eine Sitzung wird nur gestartet, wenn
 * bereits ein Werkstatt-Cookie existiert oder jemand sich anmeldet.
 */

declare(strict_types=1);

const SITZUNG_NAME = 'pichi_werkstatt';
const MERKEN_COOKIE = 'pichi_merken';
const MERKEN_TAGE = 30;
const LOGIN_MAX_FEHLVERSUCHE = 5;
const LOGIN_SPERRE_MINUTEN = 15;
const FORMULAR_MAX_PRO_STUNDE = 5;
const FORMULAR_MIN_SEKUNDEN = 3;

function cookie_optionen(int $ablauf): array
{
    return [
        'expires' => $ablauf,
        'path' => basis_pfad() . '/',
        'secure' => ist_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function sitzung_starten(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $verzeichnis = daten_verzeichnis() . '/sitzungen';
    if (!is_dir($verzeichnis)) {
        @mkdir($verzeichnis, 0750, true);
    }
    if (is_dir($verzeichnis) && is_writable($verzeichnis)) {
        session_save_path($verzeichnis);
        // Eigenes Verzeichnis → eigene Aufräumregel (12 Stunden)
        ini_set('session.gc_maxlifetime', '43200');
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name(SITZUNG_NAME);
    $optionen = cookie_optionen(0);
    unset($optionen['expires']);
    $optionen['lifetime'] = 0;
    session_set_cookie_params($optionen);
    session_start();
}

/** Fingerabdruck des aktuellen Sitzungs-Geheimnisses (ändert sich bei "Alle Geräte abmelden"). */
function sitzungs_fingerabdruck(): string
{
    return hash_hmac('sha256', 'sitzung', app_geheimnis() . einstellung('sitzungs_geheimnis'));
}

function merken_token_erzeugen(): string
{
    $ablauf = time() + MERKEN_TAGE * 86400;
    $signatur = hash_hmac('sha256', 'merken|' . $ablauf, app_geheimnis() . einstellung('sitzungs_geheimnis'));
    return $ablauf . '.' . $signatur;
}

function merken_token_gueltig(string $token): bool
{
    if (!preg_match('/^(\d{10,})\.([a-f0-9]{64})$/', $token, $teile)) {
        return false;
    }
    if ((int) $teile[1] < time()) {
        return false;
    }
    $erwartet = hash_hmac('sha256', 'merken|' . $teile[1], app_geheimnis() . einstellung('sitzungs_geheimnis'));
    return hash_equals($erwartet, $teile[2]);
}

function ist_angemeldet(): bool
{
    static $ergebnis = null;
    if ($ergebnis !== null) {
        return $ergebnis;
    }
    if (!isset($_COOKIE[SITZUNG_NAME]) && !isset($_COOKIE[MERKEN_COOKIE])) {
        return $ergebnis = false;
    }
    sitzung_starten();
    if (!empty($_SESSION['angemeldet']) && hash_equals(sitzungs_fingerabdruck(), (string) ($_SESSION['fingerabdruck'] ?? ''))) {
        return $ergebnis = true;
    }
    unset($_SESSION['angemeldet'], $_SESSION['fingerabdruck']);

    // Automatische Anmeldung über "Angemeldet bleiben"
    $token = (string) ($_COOKIE[MERKEN_COOKIE] ?? '');
    if ($token !== '') {
        if (merken_token_gueltig($token)) {
            session_regenerate_id(true);
            $_SESSION['angemeldet'] = true;
            $_SESSION['fingerabdruck'] = sitzungs_fingerabdruck();
            $_SESSION['gemerkt'] = true;
            return $ergebnis = true;
        }
        setcookie(MERKEN_COOKIE, '', cookie_optionen(time() - 3600));
    }
    return $ergebnis = false;
}

function anmelden(bool $merken): void
{
    sitzung_starten();
    session_regenerate_id(true);
    $_SESSION['angemeldet'] = true;
    $_SESSION['fingerabdruck'] = sitzungs_fingerabdruck();
    $_SESSION['gemerkt'] = $merken;
    if ($merken) {
        setcookie(MERKEN_COOKIE, merken_token_erzeugen(), cookie_optionen(time() + MERKEN_TAGE * 86400));
    }
}

function abmelden(): void
{
    sitzung_starten();
    $_SESSION = [];
    session_destroy();
    setcookie(SITZUNG_NAME, '', cookie_optionen(time() - 3600));
    setcookie(MERKEN_COOKIE, '', cookie_optionen(time() - 3600));
}

/**
 * Macht alle Anmeldungen und "Angemeldet bleiben"-Cookies ungültig.
 * Das aktuelle Gerät bleibt auf Wunsch angemeldet.
 */
function alle_geraete_abmelden(bool $diesesGeraetBehalten): void
{
    einstellung_setzen('sitzungs_geheimnis', bin2hex(random_bytes(32)));
    if ($diesesGeraetBehalten && session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['angemeldet'] = true;
        $_SESSION['fingerabdruck'] = sitzungs_fingerabdruck();
        if (!empty($_SESSION['gemerkt'])) {
            setcookie(MERKEN_COOKIE, merken_token_erzeugen(), cookie_optionen(time() + MERKEN_TAGE * 86400));
        }
    }
}

/** Leitet zur Anmeldung um, falls nicht angemeldet (mit Rücksprung). */
function login_erforderlich(): void
{
    if (ist_angemeldet()) {
        return;
    }
    $ziel = (string) ($_SERVER['REQUEST_URI'] ?? url('uebersicht'));
    weiterleiten(url('login') . '?zurueck=' . rawurlencode($ziel));
}

/** Prüft ein Rücksprungziel: nur Pfade innerhalb der Anwendung. */
function sicheres_ziel(string $ziel): string
{
    $basis = basis_pfad() . '/';
    if ($ziel !== '' && str_starts_with($ziel, $basis) && !str_contains($ziel, '//') && !str_contains($ziel, '\\')
        && !preg_match('/[\x00-\x1f]/', $ziel) && !str_contains($ziel, '/login') && !str_contains($ziel, '/logout')) {
        return $ziel;
    }
    return url('uebersicht');
}

// ---------------------------------------------------------------------------
// Passwort & Brute-Force-Schutz
// ---------------------------------------------------------------------------

function passwort_hash_erzeugen(string $passwort): string
{
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    return password_hash($passwort, $algo);
}

function passwort_pruefen(string $passwort): bool
{
    $hash = einstellung('passwort_hash');
    if ($hash === '' || !password_verify($passwort, $hash)) {
        return false;
    }
    if (password_needs_rehash($hash, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT)) {
        einstellung_setzen('passwort_hash', passwort_hash_erzeugen($passwort));
    }
    return true;
}

/** Fehlversuche der aktuellen IP seit dem letzten Erfolg innerhalb der Sperrzeit (neueste zuerst). */
function login_fehlversuche(): array
{
    $letzterErfolg = (int) db_abfrage(
        'SELECT COALESCE(MAX(id), 0) FROM login_versuche WHERE ip_hash = ? AND erfolgreich = 1',
        [ip_hash()]
    )->fetchColumn();
    return db_abfrage(
        'SELECT zeitpunkt FROM login_versuche WHERE ip_hash = ? AND erfolgreich = 0 AND id > ? AND zeitpunkt > ? ORDER BY id DESC',
        [ip_hash(), $letzterErfolg, date('Y-m-d H:i:s', time() - LOGIN_SPERRE_MINUTEN * 60)]
    )->fetchAll(PDO::FETCH_COLUMN);
}

/** Zeitpunkt (Unix), bis zu dem die aktuelle IP gesperrt ist, sonst null. */
function login_gesperrt_bis(): ?int
{
    db_abfrage('DELETE FROM login_versuche WHERE zeitpunkt < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    $fehlversuche = login_fehlversuche();
    if (count($fehlversuche) >= LOGIN_MAX_FEHLVERSUCHE) {
        return strtotime($fehlversuche[0]) + LOGIN_SPERRE_MINUTEN * 60;
    }
    return null;
}

function login_versuch_speichern(bool $erfolgreich): void
{
    db_abfrage(
        'INSERT INTO login_versuche (ip_hash, zeitpunkt, erfolgreich) VALUES (?, ?, ?)',
        [ip_hash(), jetzt(), $erfolgreich ? 1 : 0]
    );
}

function verbleibende_versuche(): int
{
    return max(0, LOGIN_MAX_FEHLVERSUCHE - count(login_fehlversuche()));
}

// ---------------------------------------------------------------------------
// CSRF (Werkstattbereich, sitzungsbasiert)
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    sitzung_starten();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_feld(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_pruefen(): void
{
    sitzung_starten();
    $gesendet = (string) ($_POST['csrf'] ?? '');
    if ($gesendet === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $gesendet)) {
        fehlerseite(403, null, 'Sitzung abgelaufen', 'Das Formular war zu lange geöffnet oder die Sitzung ist abgelaufen. Bitte lade die Seite neu und versuche es noch einmal.');
    }
}

// ---------------------------------------------------------------------------
// Kurzmitteilungen ("Gespeichert" usw.)
// ---------------------------------------------------------------------------

function meldung(string $text, string $art = 'erfolg'): void
{
    sitzung_starten();
    $_SESSION['meldungen'][] = ['text' => $text, 'art' => $art];
}

function meldungen_abholen(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }
    $liste = $_SESSION['meldungen'] ?? [];
    unset($_SESSION['meldungen']);
    return $liste;
}

// ---------------------------------------------------------------------------
// Öffentliches Formular: signiertes Token ohne Cookie (CSRF + Mindest-Ausfüllzeit)
// ---------------------------------------------------------------------------

function formular_token(int $nummer): string
{
    $zeit = time();
    return $zeit . '.' . hash_hmac('sha256', 'formular|' . $nummer . '|' . $zeit, app_geheimnis());
}

/** Gibt null zurück, wenn gültig, sonst einen Fehlertext. */
function formular_token_pruefen(int $nummer, string $token): ?string
{
    if (!preg_match('/^(\d{10,})\.([a-f0-9]{64})$/', $token, $teile)) {
        return 'Das Formular ist ungültig. Bitte lade die Seite neu.';
    }
    $erwartet = hash_hmac('sha256', 'formular|' . $nummer . '|' . $teile[1], app_geheimnis());
    if (!hash_equals($erwartet, $teile[2])) {
        return 'Das Formular ist ungültig. Bitte lade die Seite neu.';
    }
    $alter = time() - (int) $teile[1];
    if ($alter < FORMULAR_MIN_SEKUNDEN) {
        return 'Das ging aber schnell! Bitte prüfe deine Angaben und sende das Formular noch einmal ab.';
    }
    if ($alter > 86400) {
        return 'Das Formular war zu lange geöffnet. Bitte prüfe deine Angaben und sende es noch einmal ab.';
    }
    return null;
}

function formular_limit_erreicht(): bool
{
    db_abfrage('DELETE FROM formular_eingaenge WHERE zeitpunkt < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    $anzahl = (int) db_abfrage(
        'SELECT COUNT(*) FROM formular_eingaenge WHERE ip_hash = ? AND zeitpunkt > ?',
        [ip_hash(), date('Y-m-d H:i:s', time() - 3600)]
    )->fetchColumn();
    return $anzahl >= FORMULAR_MAX_PRO_STUNDE;
}

function formular_eingang_speichern(): void
{
    db_abfrage('INSERT INTO formular_eingaenge (ip_hash, zeitpunkt) VALUES (?, ?)', [ip_hash(), jetzt()]);
}
