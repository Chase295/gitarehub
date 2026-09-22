# Manuelle Testcheckliste

Bitte auf **echten Handys** testen (mindestens ein iPhone mit Safari und ein Android-Gerät mit Chrome). Die Beispielnummern gehen vom Standard-Nummernbereich 1000–1999 aus.

## Kunde

- [ ] `/auftrag/1234` (frei, im Bereich) zeigt das Erfassungsformular.
- [ ] Tastaturen passen: E-Mail-Tastatur bei E-Mail, Telefon-Tastatur bei Telefon, Zahlen bei PLZ.
- [ ] Autovervollständigen (Name, Adresse) wird angeboten.
- [ ] Absenden ohne Pflichtfelder → Fehlermeldungen direkt am Feld, Seite springt zum ersten Fehler.
- [ ] Ohne angehakte Reparatur → Hinweis „Bitte mindestens eine Reparatur auswählen“.
- [ ] Ausfüllen und absenden → Bestätigungsseite mit Zusammenfassung.
- [ ] Kunde erhält die **Eingangsbestätigung** per Mail.
- [ ] Die Werkstatt-Adresse(n) erhalten die Mail **„Neuer Auftrag“** mit Direktlink. „Antworten“ geht an den Kunden.
- [ ] Doppeltipp auf „Absenden“ bzw. Seite neu laden → kein zweiter Datensatz, Hinweis „bereits erfasst“ ohne Kundendaten.
- [ ] Erneuter Aufruf von `/auftrag/1234` ohne Login zeigt **nur die Login-Maske**, keine Kundendaten.
- [ ] `/auftrag/abc`, `/auftrag/0`, `/auftrag/-5` → Fehlerseite.
- [ ] `/auftrag/0042` leitet auf `/auftrag/42` um.
- [ ] Nummer außerhalb des Bereichs (z. B. `/auftrag/5000`) → „Nummer nicht freigegeben“.
- [ ] Link „Datenschutzhinweis“ im Formular öffnet die Rechtsseite.
- [ ] Im Browser werden beim Ausfüllen **keine Cookies** gesetzt (Safari: Einstellungen → Website-Daten).

## Werkstatt

- [ ] Richtiges Passwort → Auftrag ist bearbeitbar. Nach dem Login landet man wieder auf der ursprünglich aufgerufenen Seite.
- [ ] 5 falsche Passwörter → Sperre 15 Minuten mit Uhrzeit-Hinweis.
- [ ] „Angemeldet bleiben“ → nach Schließen des Browsers weiterhin angemeldet.
- [ ] **Anrufen** öffnet die Telefon-App, **E-Mail** das Mailprogramm.
- [ ] Felder ändern → Speichern → „Gespeichert“. Beim Verlassen ohne Speichern kommt eine Warnung.
- [ ] Status manuell ändern (z. B. „In Arbeit“) → es wird keine Mail verschickt.
- [ ] Preis `45,50` eintragen → erscheint in der Abhol-Mail als „45,50 €“. Ohne Preis entfällt die Preiszeile.
- [ ] **Fertig** → Mail-Vorschau → „Mail senden“ → Kunde erhält die Abhol-Mail, Status „Abholbereit“.
- [ ] Mailfehler simulieren (in `config.php` ein falsches SMTP-Passwort eintragen) → klarer Hinweis „Kunde bitte anrufen“, der Status bleibt gespeichert. Danach wieder korrigieren!
- [ ] **Erledigt** → Status „Erledigt“, Zeitpunkt „Ausgehändigt“ sichtbar. **Rückgängig** funktioniert.
- [ ] **Löschen** → Sicherheitsabfrage → Auftrag weg. Die Nummer öffnet danach wieder das Formular.

## Übersicht

- [ ] Erreichbar über das Menü, `/auftrag/uebersicht` und `/auftrag/übersicht`. Ohne Login → Login mit Rücksprung.
- [ ] Zähler oben stimmen (neu / in Arbeit / abholbereit).
- [ ] Suche nach Nummer, Name, Marke/Modell und Telefonnummer (auch ohne Leerzeichen) funktioniert.
- [ ] „Erledigte anzeigen“ blendet erledigte Aufträge ein.
- [ ] Aufträge, die länger als die eingestellten Tage offen bzw. nicht abgeholt sind, sind farbig markiert.

## Einstellungen

- [ ] Empfänger ändern → nächste Werkstatt-Mail geht an die neue Adresse.
- [ ] Nummernbereich leeren → Warnhinweis erscheint, beliebige Nummern öffnen das Formular. Danach wieder setzen.
- [ ] Reparatur hinzufügen, umbenennen, verschieben, ausblenden → wirkt sofort im Formular.
- [ ] Öffnungszeiten und Mailvorlagen ändern → Test-Mail kommt mit neuem Text an.
- [ ] Aufbewahrung ändern → Rechtsseite zeigt die neue Dauer.
- [ ] Passwort ändern → ein zweites angemeldetes Gerät ist abgemeldet, das eigene nicht.
- [ ] „Alle anderen Geräte abmelden“ funktioniert.

## Automatische Löschung

- [ ] Nur **erledigte** Aufträge älter als N Jahre werden gelöscht. Offene Aufträge werden nie gelöscht.
- [ ] N = 0 löscht nie.
- [ ] Einstellungen zeigen, wann die letzte Bereinigung lief und wie viele Aufträge dabei entfernt wurden.
- [ ] Übersicht zeigt „… werden in den nächsten 30 Tagen automatisch gelöscht“, wenn das zutrifft.

  *Zum Testen per phpMyAdmin bei einem erledigten Auftrag `erledigt_am` auf ein Datum vor 3 Jahren setzen, dann unter Einstellungen „jetzt ausführen“.*

## Aufkleber

- [ ] Testdruck mit Rahmen auf Normalpapier passt auf den Etikettenbogen (gegen das Licht halten).
- [ ] Beim Drucken „Tatsächliche Größe/100 %“ und „Ränder: keine“ gewählt.
- [ ] QR-Code mit iPhone- und Android-Kamera scannen → öffnet `https://www.pichi.de/auftrag/{nummer}`.
- [ ] „Erstes Etikett“ > 1 überspringt bereits benutzte Etiketten.

## Allgemein

- [ ] Alle Seiten enthalten `noindex` (Quelltext bzw. HTTP-Header `X-Robots-Tag`).
- [ ] `http://www.pichi.de/auftrag/1234` leitet auf HTTPS um.
- [ ] `https://www.pichi.de/auftrag/app/config.php` und `…/app/schema.sql` → „Kein Zugriff“.
- [ ] `install.php` nach der Installation → „Installation gesperrt“ (danach löschen).
- [ ] Layout ab 320 px Breite ohne seitliches Scrollen (z. B. iPhone SE).
- [ ] Die bestehende Website pichi.de funktioniert unverändert.
- [ ] `robots.txt` enthält `Disallow: /auftrag/`.
