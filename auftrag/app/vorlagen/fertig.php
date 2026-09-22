<?php /** @var array $auftrag @var string $betreff @var string $text */ $n = $auftrag['nummer']; ?>
<h1 class="seitentitel">Fertig – Kunde benachrichtigen</h1>
<p>Auftrag <strong><?= e($n) ?></strong> wird auf <strong>„fertig / abholbereit“</strong> gesetzt. Diese Mail geht an den Kunden:</p>

<div class="karte mail-vorschau">
  <dl>
    <dt>An</dt><dd><?= e($auftrag['vorname'] . ' ' . $auftrag['nachname']) ?> &lt;<?= e($auftrag['email']) ?>&gt;</dd>
    <dt>Betreff</dt><dd><?= e($betreff) ?></dd>
  </dl>
  <pre class="mail-text"><?= e($text) ?></pre>
</div>
<?php if ($auftrag['preis'] === null): ?>
  <p class="hilfe">Tipp: Es ist kein Preis eingetragen – die Preiszeile wird weggelassen.</p>
<?php endif; ?>

<form method="post" action="<?= e(url($n . '/fertig')) ?>" class="knopf-stapel">
  <?= csrf_feld() ?>
  <button type="submit" name="aktion" value="senden" class="knopf knopf-erfolg knopf-gross knopf-breit"><?= icon('mail') ?><span>Mail senden &amp; als fertig markieren</span></button>
  <button type="submit" name="aktion" value="ohne_mail" class="knopf knopf-sekundaer knopf-breit"><span>Nur als fertig markieren (ohne Mail)</span></button>
  <a class="knopf knopf-leise knopf-breit" href="<?= e(url((string) $n)) ?>">Abbrechen</a>
</form>
