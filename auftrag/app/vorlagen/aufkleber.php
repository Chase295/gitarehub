<?php
/** @var int $von @var int $bis @var int $start @var bool $rahmen @var string $fehler @var bool $ausserhalb @var array $belegt @var array $layout */
$proBogen = (int) $layout['spalten'] * (int) $layout['zeilen'];
$anzahlEtiketten = ($bis - $von + 1) * (int) $layout['pro_nummer'];
?>
<div class="nicht-drucken">
  <h1 class="seitentitel">Aufkleber drucken</h1>
  <form method="get" action="<?= e(url('aufkleber')) ?>" class="karte formular">
    <div class="reihe">
      <div class="feld">
        <label for="von">Nummer von</label>
        <input id="von" name="von" inputmode="numeric" pattern="[0-9]*" value="<?= e($von) ?>" required>
      </div>
      <div class="feld">
        <label for="bis">bis</label>
        <input id="bis" name="bis" inputmode="numeric" pattern="[0-9]*" value="<?= e($bis) ?>" required>
      </div>
    </div>
    <div class="feld">
      <label for="start">Erstes Etikett auf dem Bogen (für angebrochene Bögen)</label>
      <input id="start" name="start" inputmode="numeric" pattern="[0-9]*" value="<?= e($start) ?>">
      <p class="hilfe">1 = oben links, zeilenweise gezählt (Bogen hat <?= $proBogen ?> Etiketten).</p>
    </div>
    <label class="checkbox-zeile">
      <input type="checkbox" name="rahmen" value="1" <?= $rahmen ? 'checked' : '' ?>>
      <span>Rahmen mitdrucken (Testdruck auf Normalpapier, um das Layout zu prüfen)</span>
    </label>
    <button type="submit" class="knopf knopf-sekundaer knopf-breit">Vorschau aktualisieren</button>
  </form>

  <?php if ($fehler): ?>
    <div class="meldung meldung-fehler"><?= e($fehler) ?></div>
  <?php else: ?>
    <?php if ($ausserhalb): ?>
      <div class="meldung meldung-warnung">Achtung: Nicht alle Nummern liegen im freigegebenen Nummernbereich (Einstellungen). Kunden können mit diesen Aufklebern keinen Auftrag erfassen.</div>
    <?php endif; ?>
    <?php if ($belegt): ?>
      <div class="meldung meldung-warnung">Bereits vergeben: <?= e(implode(', ', $belegt)) ?></div>
    <?php endif; ?>
    <div class="karte aufkleber-info">
      <p><strong><?= $bis - $von + 1 ?></strong> Nummern · <?= (int) $layout['pro_nummer'] === 2 ? 'je 2 Aufkleber (Instrument + Abholschein)' : 'je 1 Aufkleber' ?> · <strong><?= $anzahlEtiketten ?></strong> Etiketten</p>
      <p class="hilfe">Layout: <?= e($layout['spalten']) ?> × <?= e($layout['zeilen']) ?> Etiketten à <?= e($layout['etikett_breite']) ?> × <?= e($layout['etikett_hoehe']) ?> mm – anpassbar unter <a href="<?= e(url('einstellungen')) ?>#aufkleber">Einstellungen</a>. Beim Drucken „Tatsächliche Größe / 100 %“ und „Ränder: keine“ wählen.</p>
      <button type="button" class="knopf knopf-primaer knopf-gross knopf-breit" data-drucken><?= icon('drucken') ?><span>Drucken</span></button>
      <noscript><p class="meldung meldung-warnung">Für die QR-Codes wird JavaScript benötigt.</p></noscript>
    </div>
  <?php endif; ?>
</div>

<?php if (!$fehler): ?>
<div id="aufkleberboegen" class="aufkleberboegen<?= $rahmen ? ' mit-rahmen' : '' ?>"
  data-von="<?= e($von) ?>" data-bis="<?= e($bis) ?>" data-start="<?= e($start) ?>"
  data-layout="<?= e(json_encode($layout)) ?>"
  data-url="<?= e(absolute_url('')) ?>"
  data-kurz-url="<?= e(preg_replace('~^https?://(www\.)?~', '', absolute_url(''))) ?>"></div>
<script src="<?= e(asset('js/qrcode.js')) ?>" defer></script>
<script src="<?= e(asset('js/aufkleber.js')) ?>" defer></script>
<?php endif; ?>
