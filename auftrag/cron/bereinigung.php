<?php
/**
 * Automatische Bereinigung – optional als IONOS-Cronjob (z. B. täglich 03:00).
 *
 * Löscht erledigte Aufträge, deren "erledigt_am" älter als die eingestellte
 * Aufbewahrungsdauer ist. Offene Aufträge werden nie gelöscht.
 * Ohne Cronjob läuft dieselbe Bereinigung automatisch einmal täglich beim
 * ersten Aufruf durch die Werkstatt.
 *
 * Aufruf: php /pfad/zu/auftrag/cron/bereinigung.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';
fehlerbehandlung_einrichten();

if (konfig_laden() === null) {
    fwrite(STDERR, "config.php fehlt – bitte zuerst install.php ausführen.\n");
    exit(1);
}

$anzahl = (int) bereinigung_ausfuehren(erzwingen: true);
echo date('Y-m-d H:i:s') . ' Bereinigung: ' . $anzahl . " erledigte Aufträge gelöscht.\n";
