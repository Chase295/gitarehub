<?php
/**
 * E-Mail-Versand per SMTP (PHPMailer, mitgeliefert) und Mailvorlagen.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once APP_DIR . '/vendor/PHPMailer/Exception.php';
require_once APP_DIR . '/vendor/PHPMailer/PHPMailer.php';
require_once APP_DIR . '/vendor/PHPMailer/SMTP.php';

/** Die drei Mailvorlagen (Schlüssel => Beschreibung). */
function mail_vorlagen(): array
{
    return [
        'eingang' => 'Eingangsbestätigung an Kunden',
        'werkstatt' => 'Neuer Auftrag an Werkstatt',
        'abholbereit' => 'Abholbereit an Kunden',
    ];
}

/**
 * Sendet eine Mail. Gibt true bei Erfolg zurück; Fehler werden geloggt
 * und brechen nie den aufrufenden Vorgang ab.
 *
 * @param string[] $an
 */
function mail_senden(array $an, string $betreff, string $text, ?string $antwortAn = null, ?string &$fehler = null): bool
{
    $an = array_values(array_filter($an, 'ist_email'));
    if (!$an) {
        $fehler = 'Keine gültige Empfängeradresse.';
        return false;
    }
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) konfig('smtp.host', 'smtp.ionos.de');
        $mail->Port = (int) konfig('smtp.port', 587);
        $verschluesselung = strtolower((string) konfig('smtp.verschluesselung', 'tls'));
        $mail->SMTPSecure = match ($verschluesselung) {
            'ssl' => PHPMailer::ENCRYPTION_SMTPS,
            'keine', 'none', '' => '',
            default => PHPMailer::ENCRYPTION_STARTTLS,
        };
        $mail->SMTPAutoTLS = $verschluesselung !== 'keine' && $verschluesselung !== 'none' && $verschluesselung !== '';
        $benutzer = (string) konfig('smtp.benutzer', '');
        $mail->SMTPAuth = $benutzer !== '';
        $mail->Username = $benutzer;
        $mail->Password = (string) konfig('smtp.passwort', '');
        $mail->Timeout = 15;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;
        $mail->XMailer = ' ';

        $absender = (string) konfig('smtp.absender', $benutzer);
        $mail->setFrom($absender, (string) konfig('smtp.absender_name', ''));
        $mail->addReplyTo($antwortAn && ist_email($antwortAn) ? $antwortAn : $absender);
        foreach ($an as $adresse) {
            $mail->addAddress($adresse);
        }
        $mail->Subject = $betreff;
        $mail->isHTML(true);
        $mail->Body = mail_html($betreff, $text);
        $mail->AltBody = $text;
        $mail->send();
        return true;
    } catch (Throwable $e) {
        $fehler = $e->getMessage();
        log_schreiben('Mailversand fehlgeschlagen (' . implode(', ', $an) . '): ' . $e->getMessage());
        return false;
    }
}

/** Einfache HTML-Version einer Textmail (Links anklickbar). */
function mail_html(string $betreff, string $text): string
{
    $html = e($text);
    $html = preg_replace('~(https?://[^\s<]+)~', '<a href="$1" style="color:#b4531f">$1</a>', $html);
    $html = nl2br((string) $html);
    return '<!doctype html><html lang="de"><head><meta charset="utf-8"><title>' . e($betreff) . '</title></head>'
        . '<body style="margin:0;padding:24px;background:#f6f3ee;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f1a17">'
        . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:24px;font-size:15px;line-height:1.55">'
        . $html
        . '</div></body></html>';
}

/**
 * Setzt Platzhalter ein. Zeilen, in denen ein optionaler Platzhalter
 * ({preis}, {marke_modell}) leer bleibt und die sonst nur Beschriftung
 * enthalten (z. B. "Preis: {preis}"), werden entfernt.
 */
function vorlage_fuellen(string $vorlage, array $werte): string
{
    $vorlage = str_replace("\r", '', $vorlage);
    $zeilen = [];
    foreach (explode("\n", $vorlage) as $zeile) {
        if (preg_match('/^[^{}]*:\s*\{preis\}\s*$/u', $zeile) && trim((string) ($werte['preis'] ?? '')) === '') {
            continue;
        }
        $zeilen[] = $zeile;
    }
    $text = implode("\n", $zeilen);
    $ersetzungen = [];
    foreach ($werte as $schluessel => $wert) {
        $ersetzungen['{' . $schluessel . '}'] = (string) $wert;
    }
    $text = strtr($text, $ersetzungen);
    // Doppelte Leerzeichen durch leere Platzhalter glätten
    return (string) preg_replace('/[ \t]+$/m', '', preg_replace('/ {2,}/', ' ', $text));
}

function auftrag_platzhalter(array $auftrag): array
{
    $reparaturen = array_map(fn ($r) => '- ' . $r, $auftrag['reparaturen'] ?? []);
    return [
        'vorname' => $auftrag['vorname'] ?? '',
        'nachname' => $auftrag['nachname'] ?? '',
        'nummer' => (string) ($auftrag['nummer'] ?? ''),
        'instrument' => instrument_name((string) ($auftrag['instrument_typ'] ?? '')),
        'marke_modell' => (string) ($auftrag['marke_modell'] ?? ''),
        'reparaturen' => implode("\n", $reparaturen),
        'preis' => preis_format($auftrag['preis'] ?? null),
        'oeffnungszeiten' => einstellung('oeffnungszeiten'),
        'link' => absolute_url((string) ($auftrag['nummer'] ?? '')),
    ];
}

/** Liefert [betreff, text] einer Vorlage für einen Auftrag. */
function vorlage_fuer_auftrag(string $vorlage, array $auftrag): array
{
    $werte = auftrag_platzhalter($auftrag);
    if ($vorlage !== 'werkstatt') {
        $werte['link'] = '';
    }
    return [
        trim(vorlage_fuellen(einstellung('mail_' . $vorlage . '_betreff'), $werte)),
        vorlage_fuellen(einstellung('mail_' . $vorlage . '_text'), $werte),
    ];
}

/** Beispieldaten für Test-Mails. */
function beispiel_auftrag(): array
{
    return [
        'nummer' => 1234,
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
        'instrument_typ' => 'e_gitarre',
        'marke_modell' => 'Fender Stratocaster',
        'reparaturen' => ['Saiten wechseln', 'Einstellung / Setup (Saitenlage, Halskrümmung)'],
        'preis' => '79.00',
        'email' => 'max@example.org',
    ];
}
