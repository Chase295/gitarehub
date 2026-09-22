<?php
/**
 * Seitenausgabe: Sicherheits-Header und Vorlagen.
 */

declare(strict_types=1);

function sicherheits_header_senden(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; "
        . "font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store, private');
}

/**
 * Rendert eine Vorlage aus app/vorlagen/ innerhalb des Seitenlayouts.
 *
 * @param bool $werkstatt Navigation des Werkstattbereichs anzeigen
 */
function ansicht(string $vorlage, array $daten = [], string $titel = '', bool $werkstatt = false, string $koerperKlasse = ''): void
{
    $inhalt = vorlage_rendern($vorlage, $daten);
    $meldungen = $werkstatt ? meldungen_abholen() : [];
    echo vorlage_rendern('layout', compact('inhalt', 'titel', 'werkstatt', 'meldungen', 'koerperKlasse'));
}

function vorlage_rendern(string $__vorlage, array $__variablen = []): string
{
    // Parameter bewusst mit "__" benannt, damit extract() keine Vorlagenvariable (z. B. $daten) verdeckt
    extract($__variablen, EXTR_SKIP);
    ob_start();
    try {
        require APP_DIR . '/vorlagen/' . basename($__vorlage) . '.php';
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    return (string) ob_get_clean();
}

/** Kleine SVG-Symbole (inline, keine externen Ressourcen). */
function icon(string $name): string
{
    $pfade = [
        'telefon' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'zurueck' => '<path d="M15 18l-6-6 6-6"/>',
        'liste' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'speichern' => '<path d="M20 6 9 17l-5-5"/>',
        'fertig' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="M22 4 12 14l-3-3"/>',
        'uebergeben' => '<path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>',
        'rueckgaengig' => '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-15-6.7L3 13"/>',
        'loeschen' => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',
        'suche' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'einstellungen' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'aufkleber' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 17h4v4h-4z"/>',
        'abmelden' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'drucken' => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'warnung' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01"/>',
        'pfeil_hoch' => '<path d="m18 15-6-6-6 6"/>',
        'pfeil_runter' => '<path d="m6 9 6 6 6-6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ($pfade[$name] ?? '') . '</svg>';
}

/** Fehlermeldung unter einem Feld. */
function feld_fehler(array $fehler, string $feld): string
{
    return isset($fehler[$feld]) ? '<p class="feld-fehler" id="fehler-' . e($feld) . '">' . e($fehler[$feld]) . '</p>' : '';
}

function fehler_attr(array $fehler, string $feld): string
{
    return isset($fehler[$feld]) ? ' aria-invalid="true" aria-describedby="fehler-' . e($feld) . '"' : '';
}
