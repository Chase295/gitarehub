<?php
/**
 * Gemeinsamer Start für alle Einstiegspunkte (index.php, install.php, cron).
 */

declare(strict_types=1);

define('APP_DIR', __DIR__);
define('WEB_DIR', dirname(__DIR__));
define('KONFIG_DATEI', APP_DIR . '/config.php');
define('MIN_PHP_VERSION', '8.2.0');

date_default_timezone_set('Europe/Berlin');
mb_internal_encoding('UTF-8');
ini_set('display_errors', '0');

require APP_DIR . '/lib/hilfen.php';
require APP_DIR . '/lib/fehler.php';
require APP_DIR . '/lib/db.php';
require APP_DIR . '/lib/einstellungen.php';
require APP_DIR . '/lib/sitzung.php';
require APP_DIR . '/lib/mail.php';
require APP_DIR . '/lib/auftraege.php';
require APP_DIR . '/lib/validierung.php';
require APP_DIR . '/lib/ansicht.php';

/** Lädt config.php. Gibt null zurück, wenn noch nicht installiert. */
function konfig_laden(): ?array
{
    if (!is_file(KONFIG_DATEI)) {
        return null;
    }
    $konfig = require KONFIG_DATEI;
    if (!is_array($konfig)) {
        throw new RuntimeException('config.php liefert kein Array zurück.');
    }
    $GLOBALS['KONFIG'] = $konfig;
    return $konfig;
}
