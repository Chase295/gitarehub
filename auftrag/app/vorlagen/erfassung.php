<?php
/** @var int $nummer @var array $daten @var array $fehler @var string $hinweis @var string[] $reparaturen @var string $token @var bool $datenschutzOk */
?>
<section class="held">
  <p class="held-marke">Reparaturauftrag</p>
  <h1>Nr. <span class="nummer"><?= e($nummer) ?></span></h1>
  <p>Schön, dass du da bist! Trag hier <strong>einmalig</strong> deine Daten und deinen Reparaturwunsch ein. Das dauert etwa zwei Minuten.</p>
</section>

<?php if ($hinweis): ?>
  <div class="meldung meldung-fehler" role="alert"><?= e($hinweis) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url((string) $nummer)) ?>" class="formular" data-formular="auftrag" novalidate>
  <input type="hidden" name="formular_token" value="<?= e($token) ?>">
  <div class="honigtopf" aria-hidden="true">
    <label for="webseite">Bitte leer lassen</label>
    <input id="webseite" name="webseite" type="text" tabindex="-1" autocomplete="off">
  </div>

  <?= vorlage_rendern('_auftragsfelder', ['daten' => $daten, 'fehler' => $fehler, 'reparaturen' => $reparaturen, 'werkstatt' => false]) ?>

  <fieldset class="karte">
    <legend><span class="schritt">4</span>Datenschutz</legend>
    <label class="checkbox-zeile">
      <input type="checkbox" name="datenschutz" value="1" required <?= $datenschutzOk ? 'checked' : '' ?><?= fehler_attr($fehler, 'datenschutz') ?>>
      <span>Ich habe den <a href="<?= e(url('rechtliches')) ?>#datenschutz" target="_blank" rel="noopener">Datenschutzhinweis</a> zur Kenntnis genommen. Meine Angaben werden nur zur Abwicklung dieses Auftrags verwendet. <span class="pflicht" aria-hidden="true">*</span></span>
    </label>
    <?= feld_fehler($fehler, 'datenschutz') ?>
  </fieldset>

  <p class="hilfe mitte">Felder mit <span class="pflicht">*</span> sind Pflichtfelder.</p>
  <button type="submit" class="knopf knopf-primaer knopf-gross knopf-breit">Auftrag absenden</button>
  <p class="hilfe mitte">Nach dem Absenden kannst du den Auftrag nicht mehr ändern – bei Fragen ruf uns einfach an.</p>
</form>
