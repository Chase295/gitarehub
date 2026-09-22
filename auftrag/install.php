<?php
/**
 * Einmalige Installation der Auftragsverwaltung.
 *
 * - prüft PHP-Version und Erweiterungen
 * - schreibt app/config.php (falls noch nicht vorhanden)
 * - legt die Tabellen an und spielt die Standard-Einstellungen ein
 * - setzt das Werkstatt-Passwort
 * - sperrt sich danach selbst (app/daten/install.lock + gesetztes Passwort)
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

fehlerbehandlung_einrichten();
sicherheits_header_senden();

$konfig = konfig_laden();

/** Ist die Installation bereits abgeschlossen? */
function installation_gesperrt(?array $konfig): bool
{
    if (is_file(daten_verzeichnis() . '/install.lock')) {
        return true;
    }
    if ($konfig === null) {
        return false;
    }
    try {
        return einstellung('passwort_hash') !== '';
    } catch (Throwable) {
        return false; // Tabellen existieren noch nicht
    }
}

function install_sperren(): void
{
    $verzeichnis = daten_verzeichnis();
    if (!is_dir($verzeichnis)) {
        @mkdir($verzeichnis, 0750, true);
    }
    @file_put_contents($verzeichnis . '/install.lock', 'Installiert am ' . date('Y-m-d H:i:s') . "\n");
}

function konfig_datei_inhalt(array $konfig): string
{
    return "<?php\n/**\n * Konfiguration der Auftragsverwaltung – erzeugt von install.php am " . date('d.m.Y H:i') . ".\n"
        . " * Enthält Zugangsdaten: nicht weitergeben! Erklärung aller Werte: config.example.php\n */\n\n"
        . "defined('APP_DIR') || exit;\n\nreturn " . var_export($konfig, true) . ";\n";
}

if (installation_gesperrt($konfig)) {
    fehlerseite(403, null, 'Installation gesperrt', 'Die Anwendung ist bereits installiert. Aus Sicherheitsgründen kann die Installation nicht erneut ausgeführt werden. Du kannst install.php jetzt löschen.');
}

// Systemprüfung
$pruefungen = [
    'PHP-Version ' . PHP_VERSION . ' (mind. ' . MIN_PHP_VERSION . ')' => version_compare(PHP_VERSION, MIN_PHP_VERSION, '>='),
    'Erweiterung pdo_mysql' => extension_loaded('pdo_mysql'),
    'Erweiterung mbstring' => extension_loaded('mbstring'),
    'Erweiterung openssl (für SMTP mit TLS)' => extension_loaded('openssl'),
    'Erweiterung json' => extension_loaded('json'),
    'Erweiterung session' => extension_loaded('session'),
    'Verzeichnis app/daten beschreibbar' => (is_dir(daten_verzeichnis()) || @mkdir(daten_verzeichnis(), 0750, true)) && is_writable(daten_verzeichnis()),
];
$systemOk = !in_array(false, $pruefungen, true);

$schemeHost = (ist_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.pichi.de');
$eingabe = [
    'basis_url' => $schemeHost . basis_pfad(),
    'db_host' => '', 'db_port' => '3306', 'db_name' => '', 'db_benutzer' => '', 'db_passwort' => '',
    'smtp_host' => 'smtp.ionos.de', 'smtp_port' => '587', 'smtp_verschluesselung' => 'tls',
    'smtp_benutzer' => '', 'smtp_passwort' => '', 'smtp_absender_name' => "Pichi's Gitarrenstudio",
    'empfaenger' => '',
];
$fehler = [];
$ergebnis = null;

if (ist_post() && $systemOk) {
    foreach ($eingabe as $feld => $standard) {
        $eingabe[$feld] = post($feld, $feld === 'db_passwort' || $feld === 'smtp_passwort' ? '' : $standard);
    }
    $eingabe['db_passwort'] = (string) ($_POST['db_passwort'] ?? '');
    $eingabe['smtp_passwort'] = (string) ($_POST['smtp_passwort'] ?? '');
    $passwort = (string) ($_POST['passwort'] ?? '');
    $passwortWdh = (string) ($_POST['passwort_wdh'] ?? '');

    if (mb_strlen($passwort) < 8) {
        $fehler['passwort'] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    } elseif ($passwort !== $passwortWdh) {
        $fehler['passwort_wdh'] = 'Die Passwörter stimmen nicht überein.';
    }

    $neueKonfig = null;
    if ($konfig === null) {
        if (!preg_match('~^https?://[^/\s]+(/[^\s]*)?$~', $eingabe['basis_url'])) {
            $fehler['basis_url'] = 'Bitte die vollständige Adresse angeben, z. B. https://www.pichi.de/auftrag';
        }
        foreach (['db_host' => 'Datenbank-Host', 'db_name' => 'Datenbankname', 'db_benutzer' => 'Datenbank-Benutzer', 'smtp_host' => 'SMTP-Server', 'smtp_benutzer' => 'Postfach'] as $feld => $name) {
            if ($eingabe[$feld] === '') {
                $fehler[$feld] = $name . ' fehlt.';
            }
        }
        if ($eingabe['smtp_benutzer'] !== '' && !ist_email($eingabe['smtp_benutzer'])) {
            $fehler['smtp_benutzer'] = 'Bitte die vollständige E-Mail-Adresse des Postfachs angeben.';
        }
        $neueKonfig = [
            'basis_url' => rtrim($eingabe['basis_url'], '/'),
            'db' => [
                'host' => $eingabe['db_host'],
                'port' => (int) $eingabe['db_port'] ?: 3306,
                'name' => $eingabe['db_name'],
                'benutzer' => $eingabe['db_benutzer'],
                'passwort' => $eingabe['db_passwort'],
            ],
            'smtp' => [
                'host' => $eingabe['smtp_host'],
                'port' => (int) $eingabe['smtp_port'] ?: 587,
                'verschluesselung' => $eingabe['smtp_verschluesselung'] === 'ssl' ? 'ssl' : 'tls',
                'benutzer' => $eingabe['smtp_benutzer'],
                'passwort' => $eingabe['smtp_passwort'],
                'absender' => $eingabe['smtp_benutzer'],
                'absender_name' => $eingabe['smtp_absender_name'],
            ],
            'app_geheimnis' => bin2hex(random_bytes(32)),
            'daten_verzeichnis' => null,
            'debug' => false,
        ];
    }

    if (!$fehler) {
        try {
            if ($neueKonfig !== null) {
                // Verbindung mit den eingegebenen Daten testen
                db_verbinden($neueKonfig['db']);
                $GLOBALS['KONFIG'] = $neueKonfig;
            }
            foreach (sql_anweisungen((string) file_get_contents(APP_DIR . '/schema.sql')) as $anweisung) {
                db()->exec($anweisung);
            }
            einstellungen_alle(true);
            einstellung_setzen('passwort_hash', passwort_hash_erzeugen($passwort));
            einstellung_setzen('sitzungs_geheimnis', bin2hex(random_bytes(32)));
            einstellung_setzen('installiert_am', jetzt());

            $postfach = (string) konfig('smtp.absender', '');
            $empfaenger = adressliste($eingabe['empfaenger']);
            if ($empfaenger && !array_filter($empfaenger, fn ($a) => !ist_email($a))) {
                einstellung_setzen('benachrichtigung_empfaenger', implode(', ', $empfaenger));
                einstellung_setzen('testmail_an', $empfaenger[0]);
            } elseif ($postfach !== '' && $neueKonfig !== null) {
                einstellung_setzen('benachrichtigung_empfaenger', $postfach);
                einstellung_setzen('testmail_an', $postfach);
            }
            // Postfach-Adresse in den Betreiberdaten eintragen
            if ($postfach !== '') {
                einstellung_setzen('betreiber', str_replace('werkstatt@pichi.de', $postfach, einstellung('betreiber')));
            }

            $konfigGeschrieben = true;
            $konfigInhalt = '';
            if ($neueKonfig !== null) {
                $konfigInhalt = konfig_datei_inhalt($neueKonfig);
                $konfigGeschrieben = @file_put_contents(KONFIG_DATEI, $konfigInhalt) !== false;
                if ($konfigGeschrieben) {
                    @chmod(KONFIG_DATEI, 0640);
                }
            }

            $mailFehler = null;
            $mailOk = mail_senden(
                [einstellung('testmail_an') ?: $postfach],
                'Auftragsverwaltung installiert',
                "Hallo,\n\ndie Auftragsverwaltung wurde erfolgreich installiert.\n\nAnmeldung: " . absolute_url('login') . "\n\nDiese Mail bestätigt, dass der Mailversand funktioniert.",
                null,
                $mailFehler
            );

            install_sperren();
            $ergebnis = compact('konfigGeschrieben', 'konfigInhalt', 'mailOk', 'mailFehler');
        } catch (PDOException $e) {
            log_schreiben('Installation: Datenbankfehler: ' . $e->getMessage());
            $fehler['db'] = 'Keine Verbindung zur Datenbank bzw. Fehler beim Anlegen der Tabellen: ' . $e->getMessage();
        }
    }
}

ansicht('installation', [
    'pruefungen' => $pruefungen,
    'systemOk' => $systemOk,
    'konfigVorhanden' => $konfig !== null,
    'eingabe' => $eingabe,
    'fehler' => $fehler,
    'ergebnis' => $ergebnis,
], 'Installation');
