<?php /** @var array $auftrag */ $n = $auftrag['nummer']; ?>
<section class="held held-fehler">
  <div class="held-symbol" aria-hidden="true"><?= icon('loeschen') ?></div>
  <h1>Sind Sie sicher?</h1>
  <p>Auftrag <strong><?= e($n) ?></strong> (<?= e($auftrag['vorname'] . ' ' . $auftrag['nachname']) ?>, <?= e(instrument_name($auftrag['instrument_typ'])) ?>) wird <strong>endgültig gelöscht</strong>. Das kann nicht rückgängig gemacht werden. Die Nummer ist danach wieder frei.</p>
</section>
<form method="post" action="<?= e(url($n . '/loeschen')) ?>" class="knopf-stapel">
  <?= csrf_feld() ?>
  <button type="submit" name="bestaetigt" value="ja" class="knopf knopf-gefahr knopf-gross knopf-breit"><?= icon('loeschen') ?><span>Ja, endgültig löschen</span></button>
  <a class="knopf knopf-sekundaer knopf-breit" href="<?= e(url((string) $n)) ?>">Abbrechen</a>
</form>
