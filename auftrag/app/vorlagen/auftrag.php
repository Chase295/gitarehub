<?php
/** @var array $auftrag (gespeicherter Stand) @var array $daten (Formularstand) @var array $fehler @var string[] $reparaturen */
$a = $auftrag;
$n = $a['nummer'];
$status = $a['status'];
?>
<div class="auftrag-kopf status-<?= e($status) ?>">
  <div>
    <p class="auftrag-kopf-nummer">Auftrag <strong><?= e($n) ?></strong></p>
    <p class="auftrag-kopf-name"><?= e($a['vorname'] . ' ' . $a['nachname']) ?></p>
  </div>
  <span class="status-marke"><?= e(status_kurz($status)) ?></span>
</div>

<dl class="zeitleiste">
  <div><dt>Eingegangen</dt><dd><?= e(datum($a['erstellt_am'], true)) ?></dd></div>
  <?php if ($a['fertig_am']): ?><div><dt>Fertig</dt><dd><?= e(datum($a['fertig_am'], true)) ?></dd></div><?php endif; ?>
  <?php if ($a['fertig_am'] || $a['mail_gesendet_am']): ?><div><dt>Abhol-Mail</dt><dd><?= $a['mail_gesendet_am'] ? e(datum($a['mail_gesendet_am'], true)) : 'nicht gesendet' ?></dd></div><?php endif; ?>
  <?php if ($a['erledigt_am']): ?><div><dt>Ausgehändigt</dt><dd><?= e(datum($a['erledigt_am'], true)) ?></dd></div><?php endif; ?>
</dl>

<form method="post" action="<?= e(url((string) $n)) ?>" class="formular" id="auftragsformular" data-formular="auftrag" data-warnen-bei-aenderung novalidate>
  <?= csrf_feld() ?>

  <fieldset class="karte karte-werkstatt">
    <legend><?= icon('einstellungen') ?>Werkstatt</legend>
    <div class="feld">
      <label for="status">Status</label>
      <select id="status" name="status"<?= fehler_attr($fehler, 'status') ?>>
        <?php foreach (status_liste() as $wert => $name): ?>
          <option value="<?= e($wert) ?>" <?= ($daten['status'] ?? $status) === $wert ? 'selected' : '' ?>><?= e($name) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="hilfe">Manuelle Änderung verschickt keine Mail.</p>
    </div>
    <div class="feld">
      <label for="preis">Preis (optional, erscheint in der Abhol-Mail)</label>
      <div class="mit-einheit">
        <input id="preis" name="preis" inputmode="decimal" value="<?= e($daten['preis_eingabe'] ?? ($daten['preis'] !== null && $daten['preis'] !== '' ? number_format((float) $daten['preis'], 2, ',', '') : '')) ?>" placeholder="0,00"<?= fehler_attr($fehler, 'preis') ?>>
        <span>€</span>
      </div>
      <?= feld_fehler($fehler, 'preis') ?>
    </div>
    <div class="feld">
      <label for="notiz_intern">Interne Notiz (nur Werkstatt)</label>
      <textarea id="notiz_intern" name="notiz_intern" rows="3" maxlength="5000"><?= e($daten['notiz_intern'] ?? '') ?></textarea>
    </div>
  </fieldset>

  <?= vorlage_rendern('_auftragsfelder', ['daten' => $daten + ['nummer' => $n], 'fehler' => $fehler, 'reparaturen' => $reparaturen, 'werkstatt' => true]) ?>

  <div class="gefahrenzone">
    <a class="knopf knopf-gefahr-leise" href="<?= e(url($n . '/loeschen')) ?>"><?= icon('loeschen') ?><span>Auftrag löschen</span></a>
  </div>

  <nav class="aktionsleiste" aria-label="Aktionen">
    <a class="knopf knopf-leiste" href="<?= e(url('uebersicht')) ?>" title="Zur Übersicht"><?= icon('liste') ?><span>Übersicht</span></a>
    <button type="submit" name="aktion" value="speichern" class="knopf knopf-leiste knopf-primaer"><?= icon('speichern') ?><span>Speichern</span></button>
    <?php if (in_array($status, ['eingegangen', 'in_arbeit'], true)): ?>
      <button type="submit" name="aktion" value="fertig" class="knopf knopf-leiste knopf-erfolg"><?= icon('fertig') ?><span>Fertig</span></button>
    <?php elseif ($status === 'fertig'): ?>
      <button type="submit" name="aktion" value="erledigt" class="knopf knopf-leiste knopf-erfolg" data-bestaetigen="Instrument wurde ausgehändigt? Der Auftrag wird als erledigt markiert."><?= icon('uebergeben') ?><span>Erledigt</span></button>
    <?php else: ?>
      <button type="submit" name="aktion" value="rueckgaengig" class="knopf knopf-leiste"><?= icon('rueckgaengig') ?><span>Rückgängig</span></button>
    <?php endif; ?>
  </nav>
</form>

<?php if ($status === 'fertig'): ?>
  <p class="hilfe mitte">Abhol-Mail erneut senden? <a href="<?= e(url($n . '/fertig')) ?>">Mail-Vorschau öffnen</a></p>
<?php endif; ?>
