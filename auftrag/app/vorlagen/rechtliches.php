<?php /** @var string $betreiber @var string $datenschutz */ ?>
<article class="karte rechtstext">
  <h1 id="impressum">Impressum</h1>
  <p class="hilfe">Angaben gemäß § 5 DDG</p>
  <p><?= nl2br(e($betreiber)) ?></p>
</article>

<article class="karte rechtstext">
  <h1 id="datenschutz">Datenschutzhinweis zum Auftragsformular</h1>
  <p class="hilfe">Informationen nach Art. 13 DSGVO</p>
  <?= einfach_formatieren($datenschutz) ?>
</article>
