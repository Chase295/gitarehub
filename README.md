# Auftragsverwaltung – Pichi's Gitarrenstudio

Mit dieser kleinen Web-Anwendung gehen Reparaturaufträge nicht mehr verloren. Die Kundschaft scannt einen **nummerierten QR-Aufkleber** und trägt **einmalig** ihre Daten und den Reparaturwunsch ein. Danach ist der Auftrag nur noch für die Werkstatt sichtbar (passwortgeschützt).

- PHP 8.2+, MariaDB/MySQL, reines HTML/CSS/JS: keine Frameworks, kein Build-Schritt, kein Composer auf dem Server
- Für das Smartphone gebaut (ab 320 px Breite), mit automatischem Dunkelmodus
- Läuft auf IONOS Linux-Webhosting unter `https://www.pichi.de/auftrag/`
- Nicht in Suchmaschinen auffindbar (`noindex`), keine externen Ressourcen, keine Cookies für Kunden

---

## Inhalt

```
auftrag/                  ← dieser Ordner kommt auf den Webspace
├── .htaccess             Routing, HTTPS-Weiterleitung, Schutz interner Dateien
├── index.php             zentraler Einstiegspunkt
├── install.php           einmalige Installation (sperrt sich danach selbst)
├── assets/               CSS, JavaScript (inkl. QR-Bibliothek), Bilder
├── cron/bereinigung.php  optionaler Cronjob für die automatische Löschung
└── app/                  interner Code (per .htaccess gesperrt)
    ├── config.example.php   Vorlage für die Konfiguration
    ├── schema.sql           Tabellen + Standard-Einstellungen
    ├── bootstrap.php, lib/, seiten/, vorlagen/
    ├── vendor/PHPMailer/    mitgelieferter Mailversand (SMTP)
    └── daten/               Logdatei, Sitzungen, Installationssperre
TESTCHECKLISTE.md         manuelle Tests zu den Akzeptanzkriterien
```

---

## Installation auf IONOS (Schritt für Schritt)

### 1. Vorbereitung im IONOS-Kundenbereich

1. **PHP-Version**: *Hosting → PHP-Version* auf **8.2 oder neuer** stellen.
2. **Datenbank anlegen**: *Hosting → Datenbanken → Datenbank anlegen* (MariaDB). Host, Datenbankname, Benutzername und Passwort notieren.
3. **Postfach anlegen**, z. B. `werkstatt@pichi.de` (*E-Mail → Postfach anlegen*). Adresse und Passwort notieren.
4. **SSL** muss für `www.pichi.de` aktiv sein (bei IONOS normalerweise der Fall).
5. **Auftragsverarbeitungsvertrag (AVV)** unter *Mein Konto → Datenschutz* abschließen (ein Klick).

### 2. Dateien hochladen

1. Per **SFTP** (z. B. mit FileZilla; Zugangsdaten unter *Hosting → SFTP & SSH*) im Hauptverzeichnis der Website, dort wo die `index.htm` von pichi.de liegt, einen Ordner **`auftrag`** anlegen.
2. Den **Inhalt** des Ordners `auftrag/` aus diesem Repository dort hineinladen. Wichtig: auch die unsichtbaren Dateien **`.htaccess`** mitnehmen (in FileZilla: *Server → Versteckte Dateien anzeigen*).

Die bestehende Website wird dabei nicht verändert. Die Anwendung hat ihre eigene `.htaccess` nur im Unterordner.

### 3. Installation ausführen

1. **Direkt nach dem Hochladen** `https://www.pichi.de/auftrag/install.php` aufrufen.
2. Die Systemprüfung muss überall ✓ zeigen.
3. Datenbank-Zugangsdaten, Postfach (SMTP) und das **Werkstatt-Passwort** eintragen und auf **Installieren** tippen.
4. Das Skript legt die Tabellen an, schreibt `app/config.php`, setzt das Passwort und schickt eine **Test-Mail**. Danach ist es gesperrt.
5. **`install.php` löschen** (empfohlen, auch wenn sie gesperrt ist).

> Falls `config.php` nicht geschrieben werden kann, zeigt die Installation den Inhalt an. Dann die Datei von Hand als `auftrag/app/config.php` hochladen.
> Alternativ `app/config.example.php` nach `app/config.php` kopieren, von Hand ausfüllen und danach `install.php` aufrufen (dann wird nur noch das Passwort abgefragt).

### 4. robots.txt ergänzen (von Hand)

In der `robots.txt` im Hauptverzeichnis von pichi.de (falls keine existiert, neu anlegen) ergänzen:

```
User-agent: *
Disallow: /auftrag/
```

Die Datei wird bewusst **nicht automatisch** verändert, damit die bestehende Website unberührt bleibt. Zusätzlich senden alle Seiten `noindex` per Meta-Tag und HTTP-Header.

### 5. Einrichten

Unter `https://www.pichi.de/auftrag/login` anmelden, dann unter **Einstellungen**:

- **Öffnungszeiten** eintragen (sie erscheinen in den Mails)
- **Nummernbereich** prüfen (Standard 1000–1999)
- **Aufkleber-Layout**: Maße des gekauften Etikettenbogens eintragen (stehen auf der Packung). Unter *Aufkleber* mit „Rahmen mitdrucken“ einen Testdruck auf Normalpapier machen und gegen das Licht auf den Etikettenbogen legen.
- **Mailvorlagen** anpassen und per „Test-Mail“ prüfen
- **Betreiberdaten** und Datenschutzhinweis kontrollieren

### 6. Optional: Cronjob

Erledigte Aufträge werden nach der eingestellten Anzahl Jahre gelöscht. Das passiert automatisch einmal täglich, sobald sich die Werkstatt einloggt. Wenn das IONOS-Paket Cronjobs anbietet, kann zusätzlich täglich laufen:

```
php /homepages/XX/dXXXXXXXXX/htdocs/auftrag/cron/bereinigung.php
```

(Pfad siehe *Hosting → SFTP & SSH*. Das Skript ist über den Browser nicht erreichbar.)

---

## Bedienung im Alltag

| Wer | Was |
|-----|-----|
| Kunde | scannt Aufkleber → Formular → „Auftrag absenden“ → klebt Aufkleber 1 aufs Instrument, behält Aufkleber 2 als Abholschein |
| Werkstatt | bekommt eine Mail mit Direktlink. Instrument scannen öffnet den Auftrag. |
| Werkstatt | **Fertig**: Mail-Vorschau → „Mail senden & als fertig markieren“ |
| Werkstatt | bei Abholung **Erledigt** tippen (rückgängig machbar) |
| Werkstatt | **Übersicht**: offene Aufträge, Suche, Warnungen bei langer Liegezeit |

Tipp: Die Übersicht auf dem Handy über *Teilen → Zum Home-Bildschirm* als App-Symbol ablegen.

---

## Adressen

| Adresse | Funktion |
|---------|----------|
| `/auftrag/{nummer}` | QR-Einstieg: freie Nummer = Formular, vergebene Nummer = Auftrag (Login) |
| `/auftrag/uebersicht` (auch `/auftrag/übersicht`) | Übersicht |
| `/auftrag/einstellungen` | Einstellungen |
| `/auftrag/aufkleber` | Druckbogen |
| `/auftrag/login`, `/auftrag/logout` | An-/Abmelden |
| `/auftrag/rechtliches` | Impressum & Datenschutzhinweis |

---

## Sicherheit (kurz)

- Passwort nur als Argon2id-/bcrypt-Hash. „Angemeldet bleiben“ gilt 30 Tage mit signiertem Token. „Alle Geräte abmelden“ und eine Passwortänderung machen alle anderen Anmeldungen ungültig.
- 5 Fehlversuche pro IP → 15 Minuten Sperre (gespeichert wird nur ein gesalzener Hash der IP).
- Öffentliches Formular: Nummernbereich, Honeypot, Mindest-Ausfüllzeit 3 s, max. 5 Aufträge pro IP und Stunde, signiertes Formular-Token (ohne Cookie).
- CSRF-Token in allen Werkstatt-Formularen, Prepared Statements, konsequentes HTML-Escaping.
- Security-Header (CSP ohne Inline-Skripte, `X-Frame-Options`, `Referrer-Policy: no-referrer` …).
- `app/` und `cron/` sind per `.htaccess` gesperrt. Die Logdatei heißt zusätzlich `fehler-log.php` und kann selbst ohne `.htaccess` nicht gelesen werden.

**Abweichung von der Spezifikation:** Der Passwort-Hash liegt in der Datenbank (Tabelle `einstellungen`) statt in `config.php`. Nur so kann das Passwort über die Einstellungsseite geändert werden, ohne dass PHP Dateien umschreiben muss.

---

## Fehlersuche

| Problem | Lösung |
|---------|--------|
| `/auftrag/1234` → „Not Found“ von IONOS | `.htaccess` fehlt (versteckte Datei!) oder die Anwendung liegt nicht unter `/auftrag/`. Dann `RewriteBase` in `.htaccess` anpassen. |
| Weiße Seite / „Da ist etwas schiefgelaufen“ | Logdatei `app/daten/log/fehler-log.php` per SFTP herunterladen und ansehen. Zur Not in `config.php` kurz `'debug' => true` setzen. |
| Mails kommen nicht an | Einstellungen → Test-Mail. SMTP-Daten in `app/config.php` prüfen (IONOS: `smtp.ionos.de`, Port 587, `tls`). Spam-Ordner prüfen. |
| Passwort vergessen | In phpMyAdmin (IONOS → Datenbanken) in Tabelle `einstellungen` den Eintrag `passwort_hash` leeren, die Datei `app/daten/install.lock` löschen, `install.php` erneut hochladen und aufrufen. Dann wird nur das Passwort neu gesetzt, Daten bleiben erhalten. |
| Update einspielen | Alle Dateien außer `app/config.php` und `app/daten/` überschreiben. `install.php` nicht mit hochladen. |

---

## Hinweise für den Betrieb (keine Rechtsberatung)

- Den Datenschutztext am besten einmal mit einem Generator (z. B. eRecht24) gegenprüfen.
- Aufbewahrung: Auftragsdaten sind keine Buchhaltungsunterlagen. Kurze Fristen (1–2 Jahre) sind üblich, solange Rechnungen separat geschrieben werden.
- Das Haupt-Impressum von pichi.de nennt keine E-Mail-Adresse (§ 5 DDG). Mit dem neuen Werkstatt-Postfach lässt sich das nebenbei beheben.
- Die Webagentur (zagam webpublishing) informieren, dass der Ordner `/auftrag/` bei Website-Änderungen nicht überschrieben werden darf.

---

## Mitgelieferte Bibliotheken

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) 6.12.0 (LGPL-2.1), in `app/vendor/PHPMailer/`
- [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) 2.0.4 von Kazuhiko Arase (MIT), in `assets/js/qrcode.js`
