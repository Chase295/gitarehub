<?php
/**
 * Datenbankverbindung (PDO, ausschließlich Prepared Statements).
 */

declare(strict_types=1);

function db_verbinden(array $zugang): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $zugang['host'] ?? 'localhost',
        (int) ($zugang['port'] ?? 3306),
        $zugang['name'] ?? ''
    );
    return new PDO($dsn, (string) ($zugang['benutzer'] ?? ''), (string) ($zugang['passwort'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ]);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = db_verbinden((array) konfig('db', []));
    }
    return $pdo;
}

/** Führt eine Abfrage mit Parametern aus. */
function db_abfrage(string $sql, array $parameter = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($parameter);
    return $stmt;
}

/**
 * Zerlegt eine SQL-Datei in einzelne Anweisungen (beachtet Zeichenketten
 * und Kommentare, damit Semikolons in Texten nicht stören).
 */
function sql_anweisungen(string $sql): array
{
    $anweisungen = [];
    $aktuell = '';
    $laenge = strlen($sql);
    $inText = false;
    for ($i = 0; $i < $laenge; $i++) {
        $z = $sql[$i];
        if ($inText) {
            $aktuell .= $z;
            if ($z === '\\' && $i + 1 < $laenge) {
                $aktuell .= $sql[++$i];
            } elseif ($z === "'") {
                if (($sql[$i + 1] ?? '') === "'") {
                    $aktuell .= $sql[++$i];
                } else {
                    $inText = false;
                }
            }
            continue;
        }
        if ($z === '-' && ($sql[$i + 1] ?? '') === '-') {
            $ende = strpos($sql, "\n", $i);
            $i = $ende === false ? $laenge : $ende;
            $aktuell .= "\n";
            continue;
        }
        if ($z === "'") {
            $inText = true;
        }
        if ($z === ';') {
            if (trim($aktuell) !== '') {
                $anweisungen[] = trim($aktuell);
            }
            $aktuell = '';
            continue;
        }
        $aktuell .= $z;
    }
    if (trim($aktuell) !== '') {
        $anweisungen[] = trim($aktuell);
    }
    return $anweisungen;
}
