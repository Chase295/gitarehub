<?php
/** @var string $inhalt @var string $titel @var bool $werkstatt @var array $meldungen @var string $koerperKlasse */
$aktuell = trim(substr((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), strlen(basis_pfad())), '/');
$navigation = [
    'uebersicht' => ['Übersicht', 'liste'],
    'aufkleber' => ['Aufkleber', 'aufkleber'],
    'einstellungen' => ['Einstellungen', 'einstellungen'],
];
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<meta name="format-detection" content="telephone=no">
<meta name="theme-color" content="#1f1a17">
<title><?= e($titel ? $titel . ' · ' : '') ?>Pichi's Gitarrenstudio</title>
<link rel="icon" href="<?= e(asset('img/icon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(asset('img/icon-180.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="<?= e(trim(($werkstatt ? 'werkstatt ' : 'oeffentlich ') . $koerperKlasse)) ?>">
<header class="kopf">
  <div class="kopf-innen">
    <a class="marke" href="<?= e($werkstatt ? url('uebersicht') : url('')) ?>">
      <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="36" height="36">
      <span><strong>Pichi's</strong> Gitarrenstudio</span>
    </a>
    <?php if ($werkstatt): ?>
    <nav class="hauptnav" aria-label="Hauptmenü">
      <?php foreach ($navigation as $ziel => [$name, $symbol]): ?>
        <a href="<?= e(url($ziel)) ?>" class="<?= $aktuell === $ziel ? 'aktiv' : '' ?>" title="<?= e($name) ?>"><?= icon($symbol) ?><span><?= e($name) ?></span></a>
      <?php endforeach; ?>
      <a href="<?= e(url('logout')) ?>" title="Abmelden"><?= icon('abmelden') ?><span>Abmelden</span></a>
    </nav>
    <?php endif; ?>
  </div>
</header>

<main class="inhalt">
<?php foreach ($meldungen as $m): ?>
  <div class="meldung meldung-<?= e($m['art']) ?>" role="status"><?= e($m['text']) ?></div>
<?php endforeach; ?>
<?= $inhalt ?>
</main>

<footer class="fuss">
  <a href="<?= e(url('rechtliches')) ?>">Impressum &amp; Datenschutz</a>
</footer>
</body>
</html>
