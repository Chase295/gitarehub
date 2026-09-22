<?php
/**
 * Fehlerbehandlung und Logdatei.
 *
 * Die Logdatei heißt bewusst "fehler-log.php" und beginnt mit einer
 * exit-Anweisung: Selbst wenn der .htaccess-Schutz einmal nicht greifen
 * sollte, kann sie nicht über den Browser gelesen werden.
 */

declare(strict_types=1);

function log_schreiben(string $nachricht): void
{
    try {
        $verzeichnis = daten_verzeichnis() . '/log';
        if (!is_dir($verzeichnis)) {
            @mkdir($verzeichnis, 0750, true);
        }
        $datei = $verzeichnis . '/fehler-log.php';
        if (!is_file($datei) || @filesize($datei) === 0) {
            @file_put_contents($datei, "<?php http_response_code(404); exit; ?>\n");
        }
        // Log klein halten: ab 2 MB neu beginnen
        if (@filesize($datei) > 2 * 1024 * 1024) {
            @rename($datei, $verzeichnis . '/fehler-log-alt.php');
            @file_put_contents($datei, "<?php http_response_code(404); exit; ?>\n");
        }
        $zeile = '[' . date('Y-m-d H:i:s') . '] ' . str_replace(["\r", "\n"], ' ', $nachricht) . "\n";
        @file_put_contents($datei, $zeile, FILE_APPEND | LOCK_EX);
    } catch (Throwable) {
        error_log($nachricht);
    }
}

function fehlerbehandlung_einrichten(): void
{
    error_reporting(E_ALL);
    set_error_handler(function (int $nr, string $text, string $datei, int $zeile): bool {
        if (!(error_reporting() & $nr)) {
            return false;
        }
        throw new ErrorException($text, 0, $nr, $datei, $zeile);
    });
    set_exception_handler(function (Throwable $fehler): void {
        log_schreiben(get_class($fehler) . ': ' . $fehler->getMessage() . ' in ' . $fehler->getFile() . ':' . $fehler->getLine());
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Fehler: ' . $fehler->getMessage() . "\n");
            exit(1);
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        fehlerseite(500, $fehler);
    });
}

/** Zeigt eine Fehlerseite an und beendet die Anfrage. */
function fehlerseite(int $code, ?Throwable $fehler = null, string $titel = '', string $text = ''): never
{
    if (!headers_sent()) {
        http_response_code($code);
    }
    [$standardTitel, $standardText] = match ($code) {
        404 => ['Seite nicht gefunden', 'Diese Adresse gibt es nicht. Bitte scanne den QR-Code auf deinem Aufkleber noch einmal.'],
        403 => ['Kein Zugriff', 'Diese Seite ist gesperrt.'],
        429 => ['Zu viele Anfragen', 'Bitte versuche es später noch einmal.'],
        default => ['Da ist etwas schiefgelaufen', 'Ein technischer Fehler ist aufgetreten. Bitte versuche es in ein paar Minuten noch einmal oder ruf uns kurz an.'],
    };
    $debug = $fehler && konfig('debug') === true ? get_class($fehler) . ': ' . $fehler->getMessage() . "\n" . $fehler->getFile() . ':' . $fehler->getLine() : '';
    try {
        ansicht('fehler', [
            'titel' => $titel ?: $standardTitel,
            'text' => $text ?: $standardText,
            'code' => $code,
            'debug' => $debug,
        ], $titel ?: $standardTitel, werkstatt: false);
    } catch (Throwable $renderFehler) {
        // Letzte Rückfallebene ohne Vorlage
        log_schreiben('Fehlerseite konnte nicht angezeigt werden: ' . $renderFehler->getMessage());
        echo '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex, nofollow"><title>Fehler</title><p>'
            . e($titel ?: $standardTitel) . '</p>';
    }
    exit;
}
