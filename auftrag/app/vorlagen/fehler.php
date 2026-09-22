<?php /** @var string $titel @var string $text @var int $code @var string $debug */ ?>
<section class="held held-fehler">
  <div class="held-symbol" aria-hidden="true"><?= icon('warnung') ?></div>
  <h1><?= e($titel) ?></h1>
  <p><?= e($text) ?></p>
</section>
<?php if ($debug !== ''): ?>
  <pre class="debug"><?= e($debug) ?></pre>
<?php endif; ?>
