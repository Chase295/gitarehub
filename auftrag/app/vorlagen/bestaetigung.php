<?php
/** @var array $auftrag @var bool $kundenMailOk */
$a = $auftrag;
?>
<section class="held held-erfolg">
  <div class="erfolg-kreis" aria-hidden="true"><?= icon('speichern') ?></div>
  <h1>Danke, <?= e($a['vorname']) ?>!</h1>
  <p>Auftrag <strong>Nr. <?= e($a['nummer']) ?></strong> ist erfasst.</p>
</section>

<div class="karte hinweis-karte">
  <h2>So geht's weiter</h2>
  <ol class="schritte">
    <li>Klebe den <strong>ersten Aufkleber</strong> mit der Nummer <?= e($a['nummer']) ?> auf dein Instrument bzw. den Koffer.</li>
    <li>Bewahre den <strong>zweiten Aufkleber</strong> als Abholschein auf.</li>
    <li>Sobald dein Instrument fertig ist, bekommst du eine E-Mail.</li>
  </ol>
</div>

<?php if (!$kundenMailOk): ?>
  <div class="meldung meldung-warnung">Die Bestätigungs-Mail konnte gerade nicht gesendet werden. Keine Sorge – dein Auftrag ist trotzdem gespeichert.</div>
<?php endif; ?>

<div class="karte">
  <h2>Zusammenfassung</h2>
  <dl class="daten">
    <dt>Name</dt><dd><?= e($a['vorname'] . ' ' . $a['nachname']) ?></dd>
    <dt>Anschrift</dt><dd><?= e($a['strasse_hausnr']) ?><br><?= e($a['plz'] . ' ' . $a['ort']) ?></dd>
    <dt>E-Mail</dt><dd><?= e($a['email']) ?></dd>
    <dt>Telefon</dt><dd><?= e($a['telefon']) ?></dd>
    <dt>Instrument</dt><dd><?= e(instrument_name($a['instrument_typ'])) ?><?= $a['marke_modell'] ? ' · ' . e($a['marke_modell']) : '' ?><?= $a['farbe'] ? ' · ' . e($a['farbe']) : '' ?></dd>
    <?php if ($a['seriennummer']): ?><dt>Seriennummer</dt><dd><?= e($a['seriennummer']) ?></dd><?php endif; ?>
    <?php if ($a['zubehoer']): ?><dt>Zubehör</dt><dd><?= e($a['zubehoer']) ?></dd><?php endif; ?>
    <dt>Arbeiten</dt><dd><ul class="liste-kompakt"><?php foreach ($a['reparaturen'] as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul></dd>
    <?php if ($a['beschreibung']): ?><dt>Beschreibung</dt><dd class="umbruch"><?= e($a['beschreibung']) ?></dd><?php endif; ?>
  </dl>
</div>
<p class="hilfe mitte">Du kannst diese Seite jetzt schließen.</p>
