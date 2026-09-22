<?php
/** @var array $auftraege @var array $zaehler @var string $suche @var bool $mitErledigten @var int $warnungOffen @var int $warnungAbholung @var int $baldGeloescht */
?>
<div class="zaehler">
  <div class="zaehler-kachel status-eingegangen"><strong><?= $zaehler['eingegangen'] ?></strong><span>neu</span></div>
  <div class="zaehler-kachel status-in_arbeit"><strong><?= $zaehler['in_arbeit'] ?></strong><span>in Arbeit</span></div>
  <div class="zaehler-kachel status-fertig"><strong><?= $zaehler['fertig'] ?></strong><span>abholbereit</span></div>
</div>

<form method="get" action="<?= e(url('uebersicht')) ?>" class="suchleiste" role="search">
  <label class="suchfeld">
    <?= icon('suche') ?>
    <span class="nur-screenreader">Suche</span>
    <input type="search" name="q" value="<?= e($suche) ?>" placeholder="Nummer, Name, Modell, Telefon" autocomplete="off" enterkeyhint="search">
  </label>
  <label class="schalter">
    <input type="checkbox" name="erledigte" value="1" <?= $mitErledigten ? 'checked' : '' ?> data-auto-absenden>
    <span>Erledigte anzeigen</span>
  </label>
  <button type="submit" class="knopf knopf-sekundaer ohne-js">Suchen</button>
</form>

<?php if ($baldGeloescht > 0): ?>
  <div class="meldung meldung-info"><?= $baldGeloescht ?> erledigte<?= $baldGeloescht === 1 ? 'r Auftrag wird' : ' Aufträge werden' ?> in den nächsten 30 Tagen automatisch gelöscht.</div>
<?php endif; ?>

<?php if (!$auftraege): ?>
  <div class="leer">
    <?php if ($suche !== ''): ?>
      <p>Keine Aufträge zu „<?= e($suche) ?>“ gefunden.</p>
      <a class="knopf knopf-sekundaer" href="<?= e(url('uebersicht')) ?>">Suche zurücksetzen</a>
    <?php else: ?>
      <p>Keine offenen Aufträge. Zeit für eine Kaffeepause ☕</p>
    <?php endif; ?>
  </div>
<?php else: ?>
  <ul class="auftragsliste">
    <?php foreach ($auftraege as $a):
        $alter = tage_seit($a['erstellt_am']);
        $warnung = '';
        if (in_array($a['status'], ['eingegangen', 'in_arbeit'], true) && $alter >= $warnungOffen) {
            $warnung = 'Seit ' . $alter . ' Tagen offen';
        } elseif ($a['status'] === 'fertig' && $a['fertig_am'] && tage_seit($a['fertig_am']) >= $warnungAbholung) {
            $warnung = 'Seit ' . tage_seit($a['fertig_am']) . ' Tagen nicht abgeholt';
        }
    ?>
    <li>
      <a class="auftragskarte status-<?= e($a['status']) ?><?= $warnung ? ' hat-warnung' : '' ?>" href="<?= e(url((string) $a['nummer'])) ?>">
        <span class="auftrag-nummer"><?= e($a['nummer']) ?></span>
        <span class="auftrag-haupt">
          <span class="auftrag-name"><?= e($a['vorname'] . ' ' . $a['nachname']) ?></span>
          <span class="auftrag-instrument"><?= e(instrument_name($a['instrument_typ'])) ?><?= $a['marke_modell'] ? ' · ' . e($a['marke_modell']) : '' ?></span>
          <?php if ($warnung): ?><span class="auftrag-warnung"><?= icon('warnung') ?><?= e($warnung) ?></span><?php endif; ?>
        </span>
        <span class="auftrag-rechts">
          <span class="status-marke"><?= e(status_kurz($a['status'])) ?></span>
          <span class="auftrag-alter"><?= $a['status'] === 'erledigt' ? e(datum($a['erledigt_am'])) : e(tage_text($alter)) ?></span>
        </span>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
