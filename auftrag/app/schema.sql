-- ---------------------------------------------------------------------------
-- Auftragsverwaltung Pichi's Gitarrenstudio – Datenbankschema
-- MariaDB 10.3+ / MySQL 5.7+ (utf8mb4)
--
-- Wird von install.php automatisch eingespielt. Kann alternativ auch per
-- phpMyAdmin importiert werden. Alle Anweisungen sind wiederholbar
-- (CREATE TABLE IF NOT EXISTS / INSERT IGNORE) – bestehende Daten und
-- geänderte Einstellungen werden nicht überschrieben.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS auftraege (
  nummer            INT UNSIGNED NOT NULL,
  status            ENUM('eingegangen','in_arbeit','fertig','erledigt') NOT NULL DEFAULT 'eingegangen',
  vorname           VARCHAR(100) NOT NULL,
  nachname          VARCHAR(100) NOT NULL,
  strasse_hausnr    VARCHAR(150) NOT NULL,
  plz               VARCHAR(10)  NOT NULL,
  ort               VARCHAR(100) NOT NULL,
  email             VARCHAR(200) NOT NULL,
  telefon           VARCHAR(40)  NOT NULL,
  instrument_typ    VARCHAR(30)  NOT NULL,
  marke_modell      VARCHAR(150) NULL,
  farbe             VARCHAR(50)  NULL,
  seriennummer      VARCHAR(100) NULL,
  zubehoer          VARCHAR(255) NULL,
  reparaturen       LONGTEXT     NOT NULL COMMENT 'JSON-Liste der angehakten Reparaturen',
  beschreibung      TEXT         NULL,
  notiz_intern      TEXT         NULL,
  preis             DECIMAL(8,2) NULL,
  datenschutz_ok_am DATETIME     NOT NULL,
  erstellt_am       DATETIME     NOT NULL,
  geaendert_am      DATETIME     NOT NULL,
  fertig_am         DATETIME     NULL,
  mail_gesendet_am  DATETIME     NULL,
  erledigt_am       DATETIME     NULL,
  PRIMARY KEY (nummer),
  KEY idx_status (status),
  KEY idx_erledigt_am (erledigt_am),
  KEY idx_nachname (nachname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS einstellungen (
  schluessel VARCHAR(64) NOT NULL,
  wert       MEDIUMTEXT  NOT NULL,
  PRIMARY KEY (schluessel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_versuche (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_hash     CHAR(64)   NOT NULL COMMENT 'gesalzener Hash, keine Klar-IP',
  zeitpunkt   DATETIME   NOT NULL,
  erfolgreich TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_ip_zeit (ip_hash, zeitpunkt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate-Limit für das öffentliche Erfassungsformular (max. 5 Aufträge pro IP und Stunde)
CREATE TABLE IF NOT EXISTS formular_eingaenge (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_hash   CHAR(64) NOT NULL,
  zeitpunkt DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ip_zeit (ip_hash, zeitpunkt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Standard-Einstellungen (alles in der Einstellungsseite änderbar)
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO einstellungen (schluessel, wert) VALUES
('benachrichtigung_empfaenger', 'werkstatt@pichi.de'),
('nummer_von', '1000'),
('nummer_bis', '1999'),
('aufbewahrung_jahre', '2'),
('warnung_offen_tage', '14'),
('warnung_abholung_tage', '7'),
('oeffnungszeiten', 'Di–Fr 10:00–18:00 Uhr, Sa 10:00–13:00 Uhr (bitte in den Einstellungen anpassen)'),
('testmail_an', 'werkstatt@pichi.de'),
('letzte_bereinigung_am', ''),
('letzte_bereinigung_anzahl', '0'),

('reparaturen', '[
 {"id":"r01","name":"Saiten wechseln","aktiv":true},
 {"id":"r02","name":"Einstellung / Setup (Saitenlage, Halskrümmung)","aktiv":true},
 {"id":"r03","name":"Oktavreinheit einstellen","aktiv":true},
 {"id":"r04","name":"Bünde abrichten / polieren","aktiv":true},
 {"id":"r05","name":"Neubundierung","aktiv":true},
 {"id":"r06","name":"Elektronik prüfen / reparieren (Knacksen, Brummen, Ausfall)","aktiv":true},
 {"id":"r07","name":"Tonabnehmer tauschen / einbauen","aktiv":true},
 {"id":"r08","name":"Mechaniken tauschen / reparieren","aktiv":true},
 {"id":"r09","name":"Sattel / Stegeinlage anfertigen","aktiv":true},
 {"id":"r10","name":"Riss, Bruch oder Lackschaden","aktiv":true},
 {"id":"r11","name":"Reinigung & Pflege","aktiv":true},
 {"id":"r12","name":"Sonstiges (bitte unten beschreiben)","aktiv":true}
]'),

-- Aufkleber-Layout (Maße in mm). Bitte nach dem Kauf der Etikettenbögen anpassen.
('aufkleber_layout', '{"seite_breite":210,"seite_hoehe":297,"rand_oben":13,"rand_links":8,"spalten":3,"zeilen":8,"etikett_breite":64,"etikett_hoehe":34,"abstand_h":3,"abstand_v":0,"pro_nummer":2}'),

('mail_eingang_betreff', 'Dein Reparaturauftrag Nr. {nummer} ist bei uns eingegangen'),
('mail_eingang_text', 'Hallo {vorname},

danke für deinen Auftrag! Wir haben folgende Angaben erfasst:

Auftragsnummer: {nummer}
Instrument: {instrument} {marke_modell}
Gewünschte Arbeiten:
{reparaturen}

Bitte bewahre den zweiten Aufkleber mit der Nummer {nummer} als Abholschein auf.
Sobald dein Instrument fertig ist, melden wir uns per E-Mail.

Unsere Öffnungszeiten: {oeffnungszeiten}

Viele Grüße
Pichi''s Gitarrenstudio'),

('mail_werkstatt_betreff', 'Neuer Auftrag Nr. {nummer}: {vorname} {nachname} – {instrument}'),
('mail_werkstatt_text', 'Neuer Reparaturauftrag Nr. {nummer}

Kunde: {vorname} {nachname}
Instrument: {instrument} {marke_modell}
Arbeiten:
{reparaturen}

Auftrag öffnen:
{link}'),

('mail_abholbereit_betreff', 'Dein Instrument ist fertig – Auftrag Nr. {nummer}'),
('mail_abholbereit_text', 'Hallo {vorname},

gute Nachrichten: Dein Instrument ({instrument} {marke_modell}) ist fertig und kann abgeholt werden.

Auftragsnummer: {nummer}
Preis: {preis}

Bitte bring zur Abholung deinen Abholschein-Aufkleber mit der Nummer {nummer} mit.

Unsere Öffnungszeiten: {oeffnungszeiten}

Bis bald
Pichi''s Gitarrenstudio'),

('betreiber', 'Pichi´s Gitarrenstudio
Andreas Pichler
Blumenstraße D32
86633 Neuburg an der Donau

Telefon: 08431 / 8835
Telefax: 08431 / 537938
E-Mail: werkstatt@pichi.de'),

('datenschutz_text', '## Verantwortlicher

{betreiber}

## Zweck der Verarbeitung

Wir verarbeiten deine Angaben ausschließlich zur Abwicklung deines Reparaturauftrags: zur Zuordnung deines Instruments, zur Durchführung der Arbeiten und um dich bei Fertigstellung zu benachrichtigen.

## Rechtsgrundlage

Art. 6 Abs. 1 lit. b DSGVO (Erfüllung eines Vertrags bzw. vorvertragliche Maßnahmen).

## Welche Daten?

- Name und Anschrift
- E-Mail-Adresse und Telefonnummer
- Angaben zum Instrument (Typ, Marke/Modell, Farbe, Seriennummer, Zubehör)
- Angaben zur gewünschten Reparatur

## Empfänger

Die Daten werden bei unserem Hosting- und E-Mail-Dienstleister IONOS SE, Elgendorfer Str. 57, 56410 Montabaur, im Rahmen einer Auftragsverarbeitung gespeichert. Eine Weitergabe an sonstige Dritte findet nicht statt.

## Speicherdauer

Deine Daten werden bis zur Erledigung des Auftrags (Rückgabe des Instruments) gespeichert. {speicherdauer} Gesetzliche Aufbewahrungspflichten bleiben unberührt.

## Deine Rechte

Du hast das Recht auf Auskunft, Berichtigung, Löschung und Einschränkung der Verarbeitung sowie ein Widerspruchsrecht. Wende dich dazu einfach an die oben genannten Kontaktdaten. Außerdem kannst du dich bei einer Datenschutz-Aufsichtsbehörde beschweren, z. B. beim Bayerischen Landesamt für Datenschutzaufsicht (BayLDA), Promenade 18, 91522 Ansbach.

## Cookies

Beim Ausfüllen des Auftragsformulars werden keine Cookies gesetzt. Nur der interne Werkstattbereich verwendet ein technisch notwendiges Anmelde-Cookie.

## Server-Logfiles

Beim Aufruf dieser Seiten speichert unser Hoster technisch notwendige Zugriffsdaten (z. B. IP-Adresse, Zeitpunkt, aufgerufene Seite) in Logfiles. Diese dienen der Sicherheit und dem stabilen Betrieb und werden vom Hoster nach kurzer Zeit gelöscht.');
