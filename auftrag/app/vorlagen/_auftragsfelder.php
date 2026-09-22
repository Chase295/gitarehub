<?php
/**
 * Gemeinsame Formularfelder für Kunde (Erfassung) und Werkstatt (Bearbeitung).
 * @var array $daten @var array $fehler @var string[] $reparaturen @var bool $werkstatt
 */
$w = fn (string $feld) => e($daten[$feld] ?? '');
$gewaehlt = $daten['reparaturen'] ?? [];
$du = !$werkstatt;
?>
<fieldset class="karte">
  <legend><span class="schritt">1</span><?= $du ? 'Deine Kontaktdaten' : 'Kunde' ?></legend>
  <div class="reihe">
    <div class="feld">
      <label for="vorname">Vorname <span class="pflicht" aria-hidden="true">*</span></label>
      <input id="vorname" name="vorname" value="<?= $w('vorname') ?>" required maxlength="100" autocomplete="given-name" autocapitalize="words"<?= fehler_attr($fehler, 'vorname') ?>>
      <?= feld_fehler($fehler, 'vorname') ?>
    </div>
    <div class="feld">
      <label for="nachname">Nachname <span class="pflicht" aria-hidden="true">*</span></label>
      <input id="nachname" name="nachname" value="<?= $w('nachname') ?>" required maxlength="100" autocomplete="family-name" autocapitalize="words"<?= fehler_attr($fehler, 'nachname') ?>>
      <?= feld_fehler($fehler, 'nachname') ?>
    </div>
  </div>
  <div class="feld">
    <label for="strasse_hausnr">Straße und Hausnummer <span class="pflicht" aria-hidden="true">*</span></label>
    <input id="strasse_hausnr" name="strasse_hausnr" value="<?= $w('strasse_hausnr') ?>" required maxlength="150" autocomplete="street-address"<?= fehler_attr($fehler, 'strasse_hausnr') ?>>
    <?= feld_fehler($fehler, 'strasse_hausnr') ?>
  </div>
  <div class="reihe reihe-plz">
    <div class="feld">
      <label for="plz">PLZ <span class="pflicht" aria-hidden="true">*</span></label>
      <input id="plz" name="plz" value="<?= $w('plz') ?>" required inputmode="numeric" pattern="[0-9]{4,5}" maxlength="5" autocomplete="postal-code" title="4 oder 5 Ziffern"<?= fehler_attr($fehler, 'plz') ?>>
      <?= feld_fehler($fehler, 'plz') ?>
    </div>
    <div class="feld">
      <label for="ort">Ort <span class="pflicht" aria-hidden="true">*</span></label>
      <input id="ort" name="ort" value="<?= $w('ort') ?>" required maxlength="100" autocomplete="address-level2"<?= fehler_attr($fehler, 'ort') ?>>
      <?= feld_fehler($fehler, 'ort') ?>
    </div>
  </div>
  <div class="feld">
    <label for="email">E-Mail <span class="pflicht" aria-hidden="true">*</span></label>
    <div class="<?= $werkstatt ? 'mit-knopf' : '' ?>">
      <input id="email" name="email" type="email" value="<?= $w('email') ?>" required maxlength="200" autocomplete="email" autocapitalize="off" spellcheck="false"<?= fehler_attr($fehler, 'email') ?>>
      <?php if ($werkstatt && !empty($daten['email'])): ?>
        <a class="knopf knopf-sekundaer" href="mailto:<?= e((string) $daten['email']) ?>?subject=<?= e(rawurlencode('Dein Auftrag Nr. ' . $daten['nummer'])) ?>"><?= icon('mail') ?><span>E-Mail</span></a>
      <?php endif; ?>
    </div>
    <?= feld_fehler($fehler, 'email') ?>
  </div>
  <div class="feld">
    <label for="telefon">Telefon (Festnetz oder Mobil) <span class="pflicht" aria-hidden="true">*</span></label>
    <div class="<?= $werkstatt ? 'mit-knopf' : '' ?>">
      <input id="telefon" name="telefon" type="tel" value="<?= $w('telefon') ?>" required maxlength="40" autocomplete="tel"<?= fehler_attr($fehler, 'telefon') ?>>
      <?php if ($werkstatt && !empty($daten['telefon'])): ?>
        <a class="knopf knopf-sekundaer" href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $daten['telefon'])) ?>"><?= icon('telefon') ?><span>Anrufen</span></a>
      <?php endif; ?>
    </div>
    <?= feld_fehler($fehler, 'telefon') ?>
  </div>
</fieldset>

<fieldset class="karte">
  <legend><span class="schritt">2</span><?= $du ? 'Dein Instrument' : 'Instrument' ?> <span class="pflicht" aria-hidden="true">*</span></legend>
  <div class="kacheln kacheln-instrument" role="radiogroup" aria-label="Instrument">
    <?php foreach (instrument_typen() as $typ => $name): ?>
      <label class="kachel">
        <input type="radio" name="instrument_typ" value="<?= e($typ) ?>" required <?= ($daten['instrument_typ'] ?? '') === $typ ? 'checked' : '' ?>>
        <img class="kachel-bild" src="<?= e(asset('img/' . $typ . '.svg')) ?>" alt="" width="56" height="56">
        <span class="kachel-text"><?= e($name) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <?= feld_fehler($fehler, 'instrument_typ') ?>
  <div class="reihe">
    <div class="feld">
      <label for="marke_modell">Marke / Modell</label>
      <input id="marke_modell" name="marke_modell" value="<?= $w('marke_modell') ?>" maxlength="150" placeholder="z. B. Fender Stratocaster">
    </div>
    <div class="feld">
      <label for="farbe">Farbe</label>
      <input id="farbe" name="farbe" value="<?= $w('farbe') ?>" maxlength="50" placeholder="z. B. Sunburst">
    </div>
  </div>
  <div class="reihe">
    <div class="feld">
      <label for="seriennummer">Seriennummer</label>
      <input id="seriennummer" name="seriennummer" value="<?= $w('seriennummer') ?>" maxlength="100" autocapitalize="characters" spellcheck="false">
    </div>
    <div class="feld">
      <label for="zubehoer">Mitgebrachtes Zubehör</label>
      <input id="zubehoer" name="zubehoer" value="<?= $w('zubehoer') ?>" maxlength="255" placeholder="z. B. Koffer, Gurt">
    </div>
  </div>
</fieldset>

<fieldset class="karte" id="reparaturen-feld">
  <legend><span class="schritt">3</span>Was soll gemacht werden? <span class="pflicht" aria-hidden="true">*</span></legend>
  <p class="hilfe"><?= $du ? 'Hake alles an, was zutrifft.' : 'Mindestens eine Reparatur.' ?></p>
  <div class="kacheln kacheln-reparatur" data-mindestens-eins="Bitte mindestens eine Reparatur auswählen.">
    <?php foreach ($reparaturen as $name): ?>
      <label class="kachel kachel-check">
        <input type="checkbox" name="reparaturen[]" value="<?= e($name) ?>" <?= in_array($name, $gewaehlt, true) ? 'checked' : '' ?>>
        <span class="haken" aria-hidden="true"></span>
        <span class="kachel-text"><?= e($name) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <?= feld_fehler($fehler, 'reparaturen') ?>
  <div class="feld">
    <label for="beschreibung"><?= $du ? 'Beschreibe kurz das Problem (optional)' : 'Beschreibung des Kunden' ?></label>
    <textarea id="beschreibung" name="beschreibung" rows="4" maxlength="5000" placeholder="z. B. Die A-Saite schnarrt im 5. Bund, Klinkenbuchse wackelt …"><?= $w('beschreibung') ?></textarea>
  </div>
</fieldset>
