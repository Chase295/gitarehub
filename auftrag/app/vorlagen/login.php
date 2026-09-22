<?php /** @var string $ziel @var string $fehler @var bool $gesperrt */ ?>
<section class="login">
  <div class="karte">
    <div class="login-kopf">
      <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="64" height="64">
      <h1>Werkstatt</h1>
      <p class="hilfe">Bitte mit dem Werkstatt-Passwort anmelden.</p>
    </div>
    <?php if ($fehler): ?>
      <div class="meldung meldung-fehler" role="alert"><?= e($fehler) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('login')) ?>" class="formular">
      <?= csrf_feld() ?>
      <input type="hidden" name="zurueck" value="<?= e($ziel) ?>">
      <input type="text" name="benutzer" value="werkstatt" autocomplete="username" hidden>
      <div class="feld">
        <label for="passwort">Passwort</label>
        <input id="passwort" name="passwort" type="password" required autocomplete="current-password" autofocus <?= $gesperrt ? 'disabled' : '' ?>>
      </div>
      <label class="checkbox-zeile">
        <input type="checkbox" name="merken" value="1" checked>
        <span>Auf diesem Gerät angemeldet bleiben (30 Tage)</span>
      </label>
      <button type="submit" class="knopf knopf-primaer knopf-gross knopf-breit" <?= $gesperrt ? 'disabled' : '' ?>>Anmelden</button>
    </form>
  </div>
</section>
