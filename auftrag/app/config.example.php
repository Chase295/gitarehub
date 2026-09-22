<?php
/**
 * Konfiguration der Auftragsverwaltung.
 *
 * Normalerweise wird diese Datei von install.php als "config.php" erzeugt.
 * Wer lieber von Hand konfiguriert: Datei nach "config.php" kopieren und
 * die Werte eintragen. Alles andere (Empfänger, Nummernbereich, Texte …)
 * wird in der Einstellungsseite der Anwendung gepflegt.
 *
 * Diese Datei ist per .htaccess gesperrt und erzeugt bei direktem Aufruf
 * keinerlei Ausgabe.
 */

defined('APP_DIR') || exit;

return [
    // Öffentliche Adresse der Anwendung (ohne abschließenden Schrägstrich).
    // Wird für QR-Codes und Links in E-Mails verwendet.
    'basis_url' => 'https://www.pichi.de/auftrag',

    // Datenbank (IONOS-Kundenbereich → Hosting → Datenbanken)
    'db' => [
        'host'     => 'db5000000000.hosting-data.io',
        'port'     => 3306,
        'name'     => 'dbs0000000',
        'benutzer' => 'dbu0000000',
        'passwort' => '',
    ],

    // E-Mail-Versand per SMTP über das IONOS-Postfach
    'smtp' => [
        'host'             => 'smtp.ionos.de',
        'port'             => 587,
        'verschluesselung' => 'tls',   // 'tls' (Port 587) oder 'ssl' (Port 465)
        'benutzer'         => 'werkstatt@pichi.de',
        'passwort'         => '',
        'absender'         => 'werkstatt@pichi.de',   // Absender und Antwortadresse
        'absender_name'    => "Pichi's Gitarrenstudio",
    ],

    // Zufälliger geheimer Schlüssel (64 Hex-Zeichen). Wird für Signaturen und
    // das Hashen von IP-Adressen verwendet. Erzeugen z. B. mit:
    //   php -r "echo bin2hex(random_bytes(32));"
    'app_geheimnis' => '',

    // Optional: Verzeichnis für Logdatei und Sitzungen außerhalb des Web-Roots,
    // z. B. '/homepages/12/d123456789/auftrag-daten'. null = app/daten
    'daten_verzeichnis' => null,

    // Nur zur Fehlersuche auf true setzen – zeigt technische Fehlermeldungen an!
    'debug' => false,
];
