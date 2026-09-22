<?php
/** @var array $werte @var array $fehler @var array $reparaturen @var array $layout */
$w = fn (string $k) => e($werte[$k] ?? '');
$bereichOffen = ($werte['nummer_von'] ?? '') === '' || ($werte['nummer_bis'] ?? '') === '';
$letzte = $werte['letzte_bereinigung_am'] ?? '';
$platzhalter = '{vorname} {nachname} {nummer} {instrument} {marke_modell} {reparaturen} {preis} {oeffnungszeiten}';
$layoutFelder = [
    'seite_breite' => 'Seitenbreite', 'seite_hoehe' => 'Seitenhöhe',
    'rand_oben' => 'Rand oben', 'rand_links' => 'Rand links',
    'etikett_breite' => 'Etikett-Breite', 'etikett_hoehe' => 'Etikett-Höhe',
    'abstand_h' => 'Abstand waagrecht', 'abstand_v' => 'Abstand senkrecht',
    'spalten' => 'Spalten', 'zeilen' => 'Zeilen',
];
?>
<h1 class="seitentitel">Einstellungen</h1>
<nav class="sprungmarken" aria-label="Bereiche">
  <a href="#benachrichtigung">Benachrichtigung</a><a href="#nummern">Nummern</a><a href="#aufbewahrung">Aufbewahrung</a>
  <a href="#reparaturen">Reparaturen</a><a href="#mails">Mails</a><a href="#rechtliches">Rechtliches</a>
  <a href="#aufkleber">Aufkleber</a><a href="#passwort">Passwort</a>
</nav>

<form method="post" action="<?= e(url('einstellungen')) ?>" class="formular" id="einstellungsformular" data-warnen-bei-aenderung novalidate>
  <?= csrf_feld() ?>
  <?php /* Standard-Knopf für die Enter-Taste (sonst würde der erste Test-Mail-Knopf ausgelöst) */ ?>
  <button type="submit" name="aktion" value="speichern" class="nur-screenreader" tabindex="-1" aria-hidden="true">Speichern</button>

  <fieldset class="karte" id="benachrichtigung">
    <legend>Benachrichtigung „Neuer Auftrag“</legend>
    <div class="feld">
      <label for="benachrichtigung_empfaenger">Empfänger (mehrere mit Komma trennen)</label>
      <input id="benachrichtigung_empfaenger" name="benachrichtigung_empfaenger" value="<?= $w('benachrichtigung_empfaenger') ?>" inputmode="email" autocapitalize="off" spellcheck="false"<?= fehler_attr($fehler, 'benachrichtigung_empfaenger') ?>>
      <?= feld_fehler($fehler, 'benachrichtigung_empfaenger') ?>
    </div>
  </fieldset>

  <fieldset class="karte" id="nummern">
    <legend>Nummernbereich</legend>
    <p class="hilfe">Nur Nummern in diesem Bereich öffnen das Erfassungsformular. So können Fremde nicht einfach beliebige Nummern ausprobieren.</p>
    <div class="reihe">
      <div class="feld">
        <label for="nummer_von">Von</label>
        <input id="nummer_von" name="nummer_von" inputmode="numeric" value="<?= $w('nummer_von') ?>" placeholder="unbegrenzt"<?= fehler_attr($fehler, 'nummer_von') ?>>
        <?= feld_fehler($fehler, 'nummer_von') ?>
      </div>
      <div class="feld">
        <label for="nummer_bis">Bis</label>
        <input id="nummer_bis" name="nummer_bis" inputmode="numeric" value="<?= $w('nummer_bis') ?>" placeholder="unbegrenzt"<?= fehler_attr($fehler, 'nummer_bis') ?>>
        <?= feld_fehler($fehler, 'nummer_bis') ?>
      </div>
    </div>
    <?php if ($bereichOffen): ?>
      <div class="meldung meldung-warnung">Achtung: Der Nummernbereich ist nicht (vollständig) begrenzt. Dann öffnet <strong>jede beliebige Zahl</strong> ein Erfassungsformular.</div>
    <?php else: ?>
      <p class="hilfe">Leer lassen = unbegrenzt (nicht empfohlen).</p>
    <?php endif; ?>
  </fieldset>

  <fieldset class="karte" id="aufbewahrung">
    <legend>Aufbewahrung &amp; Warnungen</legend>
    <div class="feld">
      <label for="aufbewahrung_jahre">Erledigte Aufträge automatisch löschen nach … Jahren</label>
      <input id="aufbewahrung_jahre" name="aufbewahrung_jahre" inputmode="numeric" value="<?= $w('aufbewahrung_jahre') ?>"<?= fehler_attr($fehler, 'aufbewahrung_jahre') ?>>
      <?= feld_fehler($fehler, 'aufbewahrung_jahre') ?>
      <p class="hilfe">0 = nie automatisch löschen. Gelöscht werden nur erledigte Aufträge, nie offene. Die Frist beginnt mit „Erledigt“.</p>
    </div>
    <p class="hilfe">Letzte Bereinigung: <?= $letzte ? e(datum($letzte, true)) . ' – ' . e($werte['letzte_bereinigung_anzahl'] ?? '0') . ' gelöscht' : 'noch nie' ?>
      · <button type="submit" form="bereinigungsformular" class="link-knopf">jetzt ausführen</button></p>
    <div class="reihe">
      <div class="feld">
        <label for="warnung_offen_tage">Hervorheben, wenn offen seit … Tagen</label>
        <input id="warnung_offen_tage" name="warnung_offen_tage" inputmode="numeric" value="<?= $w('warnung_offen_tage') ?>"<?= fehler_attr($fehler, 'warnung_offen_tage') ?>>
        <?= feld_fehler($fehler, 'warnung_offen_tage') ?>
      </div>
      <div class="feld">
        <label for="warnung_abholung_tage">… fertig, aber nicht abgeholt seit … Tagen</label>
        <input id="warnung_abholung_tage" name="warnung_abholung_tage" inputmode="numeric" value="<?= $w('warnung_abholung_tage') ?>"<?= fehler_attr($fehler, 'warnung_abholung_tage') ?>>
        <?= feld_fehler($fehler, 'warnung_abholung_tage') ?>
      </div>
    </div>
  </fieldset>

  <fieldset class="karte" id="reparaturen">
    <legend>Reparaturliste</legend>
    <p class="hilfe">Reihenfolge per Pfeil oder Zahl ändern. Ausgeblendete Einträge erscheinen nicht mehr im Formular, bleiben aber in bestehenden Aufträgen erhalten. Umbenennen ändert bestehende Aufträge nicht.</p>
    <?= feld_fehler($fehler, 'reparaturen') ?>
    <ol class="reparatur-liste" data-reparatur-liste>
      <?php $zeilen = array_merge($reparaturen, array_fill(0, 3, ['id' => '', 'name' => '', 'aktiv' => true]));
      foreach ($zeilen as $i => $r): ?>
        <li class="reparatur-zeile<?= $r['name'] === '' ? ' neu' : '' ?>">
          <input type="hidden" name="rep[<?= $i ?>][id]" value="<?= e($r['id']) ?>">
          <input class="rep-pos" name="rep[<?= $i ?>][pos]" value="<?= $i + 1 ?>" inputmode="numeric" aria-label="Position">
          <input class="rep-name" name="rep[<?= $i ?>][name]" value="<?= e($r['name']) ?>" maxlength="120" placeholder="Neue Reparatur …" aria-label="Bezeichnung">
          <label class="rep-aktiv" title="Im Formular anzeigen"><input type="checkbox" name="rep[<?= $i ?>][aktiv]" value="1" <?= !empty($r['aktiv']) ? 'checked' : '' ?>><span>aktiv</span></label>
          <span class="rep-pfeile nur-js">
            <button type="button" class="knopf-icon" data-nach-oben aria-label="Nach oben"><?= icon('pfeil_hoch') ?></button>
            <button type="button" class="knopf-icon" data-nach-unten aria-label="Nach unten"><?= icon('pfeil_runter') ?></button>
          </span>
        </li>
      <?php endforeach; ?>
    </ol>
    <p class="hilfe">Zum Hinzufügen einfach in eine leere Zeile schreiben. Nach dem Speichern erscheinen neue leere Zeilen.</p>
  </fieldset>

  <fieldset class="karte" id="mails">
    <legend>E-Mails</legend>
    <div class="feld">
      <label for="oeffnungszeiten">Öffnungszeiten (für die Mails)</label>
      <textarea id="oeffnungszeiten" name="oeffnungszeiten" rows="2" maxlength="500"><?= $w('oeffnungszeiten') ?></textarea>
    </div>
    <p class="hilfe">Platzhalter: <code><?= e($platzhalter) ?></code> – in der Werkstatt-Mail zusätzlich <code>{link}</code>. Eine Zeile wie „Preis: {preis}“ entfällt automatisch, wenn kein Preis eingetragen ist.</p>
    <div class="feld">
      <label for="testmail_an">Test-Mails senden an</label>
      <input id="testmail_an" name="testmail_an" type="email" value="<?= $w('testmail_an') ?>" autocapitalize="off"<?= fehler_attr($fehler, 'testmail_an') ?>>
      <?= feld_fehler($fehler, 'testmail_an') ?>
    </div>
    <?php foreach (mail_vorlagen() as $vorlage => $name): ?>
      <details class="mailvorlage" <?= isset($fehler['mail_' . $vorlage . '_betreff']) || isset($fehler['mail_' . $vorlage . '_text']) ? 'open' : '' ?>>
        <summary><?= e($name) ?></summary>
        <div class="feld">
          <label for="mail_<?= $vorlage ?>_betreff">Betreff</label>
          <input id="mail_<?= $vorlage ?>_betreff" name="mail_<?= $vorlage ?>_betreff" value="<?= $w('mail_' . $vorlage . '_betreff') ?>" maxlength="200"<?= fehler_attr($fehler, 'mail_' . $vorlage . '_betreff') ?>>
          <?= feld_fehler($fehler, 'mail_' . $vorlage . '_betreff') ?>
        </div>
        <div class="feld">
          <label for="mail_<?= $vorlage ?>_text">Text</label>
          <textarea id="mail_<?= $vorlage ?>_text" name="mail_<?= $vorlage ?>_text" rows="12"<?= fehler_attr($fehler, 'mail_' . $vorlage . '_text') ?>><?= $w('mail_' . $vorlage . '_text') ?></textarea>
          <?= feld_fehler($fehler, 'mail_' . $vorlage . '_text') ?>
        </div>
        <button type="submit" name="aktion" value="test_<?= $vorlage ?>" class="knopf knopf-sekundaer"><?= icon('mail') ?><span>Speichern &amp; Test-Mail an mich senden</span></button>
      </details>
    <?php endforeach; ?>
  </fieldset>

  <fieldset class="karte" id="rechtliches">
    <legend>Impressum &amp; Datenschutz</legend>
    <div class="feld">
      <label for="betreiber">Betreiberdaten (Impressum)</label>
      <textarea id="betreiber" name="betreiber" rows="9"<?= fehler_attr($fehler, 'betreiber') ?>><?= $w('betreiber') ?></textarea>
      <?= feld_fehler($fehler, 'betreiber') ?>
    </div>
    <div class="feld">
      <label for="datenschutz_text">Datenschutzhinweis</label>
      <textarea id="datenschutz_text" name="datenschutz_text" rows="14"<?= fehler_attr($fehler, 'datenschutz_text') ?>><?= $w('datenschutz_text') ?></textarea>
      <?= feld_fehler($fehler, 'datenschutz_text') ?>
      <p class="hilfe">Format: „## “ am Zeilenanfang = Überschrift, „- “ = Aufzählung, Leerzeile = neuer Absatz. Platzhalter: <code>{betreiber}</code>, <code>{speicherdauer}</code> (Satz zur Löschfrist), <code>{aufbewahrung_jahre}</code>. <a href="<?= e(url('rechtliches')) ?>" target="_blank" rel="noopener">Rechtsseite ansehen</a></p>
    </div>
  </fieldset>

  <fieldset class="karte" id="aufkleber">
    <legend>Aufkleber-Layout (Maße in mm)</legend>
    <p class="hilfe">Die Maße stehen auf der Verpackung der Etikettenbögen (bzw. im Datenblatt des Herstellers). Mit einem Testdruck auf Normalpapier („Rahmen mitdrucken“) lässt sich das Layout prüfen, indem man das Blatt auf den Etikettenbogen legt.</p>
    <div class="raster-layout">
      <?php foreach ($layoutFelder as $feld => $name): ?>
        <div class="feld">
          <label for="layout_<?= $feld ?>"><?= e($name) ?></label>
          <input id="layout_<?= $feld ?>" name="layout_<?= $feld ?>" inputmode="decimal" value="<?= e(str_replace('.', ',', (string) ($layout[$feld] ?? ''))) ?>"<?= fehler_attr($fehler, 'layout_' . $feld) ?>>
          <?= isset($fehler['layout_' . $feld]) ? '<p class="feld-fehler">Erlaubt: ' . e($fehler['layout_' . $feld]) . '</p>' : '' ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="feld">
      <label for="layout_pro_nummer">Aufkleber pro Nummer</label>
      <select id="layout_pro_nummer" name="layout_pro_nummer">
        <option value="2" <?= (int) ($layout['pro_nummer'] ?? 2) === 2 ? 'selected' : '' ?>>2 – Instrument + Abholschein</option>
        <option value="1" <?= (int) ($layout['pro_nummer'] ?? 2) === 1 ? 'selected' : '' ?>>1 – nur Instrument</option>
      </select>
    </div>
    <p><a href="<?= e(url('aufkleber')) ?>?rahmen=1">Testdruck-Vorschau öffnen</a> (vorher speichern)</p>
  </fieldset>

  <div class="aktionsleiste">
    <a class="knopf knopf-leiste" href="<?= e(url('uebersicht')) ?>"><?= icon('liste') ?><span>Übersicht</span></a>
    <button type="submit" name="aktion" value="speichern" class="knopf knopf-leiste knopf-primaer"><?= icon('speichern') ?><span>Speichern</span></button>
  </div>
</form>

<form method="post" action="<?= e(url('einstellungen')) ?>" id="bereinigungsformular" hidden>
  <?= csrf_feld() ?>
  <input type="hidden" name="aktion" value="bereinigung">
</form>

<form method="post" action="<?= e(url('einstellungen')) ?>" class="karte formular" id="passwort">
  <h2>Passwort ändern</h2>
  <?= csrf_feld() ?>
  <input type="hidden" name="aktion" value="passwort">
  <input type="text" name="benutzer" value="werkstatt" autocomplete="username" hidden>
  <div class="feld">
    <label for="passwort_alt">Bisheriges Passwort</label>
    <input id="passwort_alt" name="passwort_alt" type="password" autocomplete="current-password" required>
  </div>
  <div class="feld">
    <label for="passwort_neu">Neues Passwort (mind. 8 Zeichen)</label>
    <input id="passwort_neu" name="passwort_neu" type="password" autocomplete="new-password" minlength="8" required>
  </div>
  <div class="feld">
    <label for="passwort_wdh">Neues Passwort wiederholen</label>
    <input id="passwort_wdh" name="passwort_wdh" type="password" autocomplete="new-password" minlength="8" required>
  </div>
  <p class="hilfe">Nach der Änderung werden alle anderen Geräte abgemeldet.</p>
  <button type="submit" class="knopf knopf-sekundaer knopf-breit">Passwort ändern</button>
</form>

<form method="post" action="<?= e(url('einstellungen')) ?>" class="karte" id="sicherheit">
  <h2>Alle Geräte abmelden</h2>
  <?= csrf_feld() ?>
  <input type="hidden" name="aktion" value="alle_abmelden">
  <p class="hilfe">Z. B. wenn ein Handy verloren gegangen ist. Alle anderen Geräte müssen sich neu anmelden – dieses Gerät bleibt angemeldet.</p>
  <button type="submit" class="knopf knopf-gefahr-leise knopf-breit" data-bestaetigen="Wirklich alle anderen Geräte abmelden?">Alle anderen Geräte abmelden</button>
</form>
