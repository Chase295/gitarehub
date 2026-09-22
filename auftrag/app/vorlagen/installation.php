<?php
/** @var array $pruefungen @var bool $systemOk @var bool $konfigVorhanden @var array $eingabe @var array $fehler @var ?array $ergebnis */
$w = fn (string $k) => e($eingabe[$k] ?? '');
?>
<h1 class="seitentitel">Installation</h1>

<?php if ($ergebnis): ?>
  <section class="held held-erfolg">
    <div class="erfolg-kreis" aria-hidden="true"><?= icon('speichern') ?></div>
    <h1>Fertig installiert!</h1>
    <p>Die Datenbank ist eingerichtet und das Passwort gesetzt. Die Installation ist jetzt gesperrt.</p>
  </section>

  <?php if (!$ergebnis['konfigGeschrieben']): ?>
    <div class="karte">
      <h2>Bitte config.php von Hand hochladen</h2>
      <p>Die Datei <code>app/config.php</code> konnte nicht geschrieben werden (fehlende Schreibrechte). Bitte den folgenden Inhalt als <code>auftrag/app/config.php</code> per SFTP hochladen:</p>
      <textarea class="code" rows="20" readonly><?= e($ergebnis['konfigInhalt']) ?></textarea>
    </div>
  <?php endif; ?>

  <?php if ($ergebnis['mailOk']): ?>
    <div class="meldung meldung-erfolg">Eine Test-Mail wurde versendet – der Mailversand funktioniert.</div>
  <?php else: ?>
    <div class="meldung meldung-warnung">Die Test-Mail konnte nicht gesendet werden: <?= e($ergebnis['mailFehler']) ?><br>Bitte die SMTP-Daten in <code>app/config.php</code> prüfen. Die Anwendung funktioniert trotzdem.</div>
  <?php endif; ?>

  <div class="karte">
    <h2>Nächste Schritte</h2>
    <ol class="schritte">
      <li><strong>install.php löschen</strong> (sie ist zwar gesperrt, wird aber nicht mehr gebraucht).</li>
      <li>Anmelden und unter <em>Einstellungen</em> Öffnungszeiten, Nummernbereich und Aufkleber-Layout prüfen.</li>
      <li>In <code>robots.txt</code> im Hauptverzeichnis der Website die Zeile <code>Disallow: /auftrag/</code> ergänzen.</li>
    </ol>
    <a class="knopf knopf-primaer knopf-gross knopf-breit" href="<?= e(url('login')) ?>">Zur Anmeldung</a>
  </div>

<?php else: ?>

  <div class="karte">
    <h2>1. Systemprüfung</h2>
    <ul class="pruefliste">
      <?php foreach ($pruefungen as $name => $ok): ?>
        <li class="<?= $ok ? 'ok' : 'nicht-ok' ?>"><span aria-hidden="true"><?= $ok ? '✓' : '✗' ?></span> <?= e($name) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php if (!$systemOk): ?>
      <div class="meldung meldung-fehler">Bitte zuerst die markierten Punkte beheben (IONOS-Kundenbereich → Hosting → PHP-Version auf 8.2 oder neuer stellen).</div>
    <?php endif; ?>
  </div>

  <?php if ($systemOk): ?>
  <?php if (isset($fehler['db'])): ?>
    <div class="meldung meldung-fehler"><?= e($fehler['db']) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('install.php')) ?>" class="formular" novalidate>
    <?php if ($konfigVorhanden): ?>
      <div class="meldung meldung-info">Eine <code>app/config.php</code> ist bereits vorhanden – Datenbank- und SMTP-Zugangsdaten werden von dort übernommen.</div>
    <?php else: ?>
      <fieldset class="karte">
        <legend>2. Adresse</legend>
        <div class="feld">
          <label for="basis_url">Adresse der Anwendung</label>
          <input id="basis_url" name="basis_url" value="<?= $w('basis_url') ?>" required<?= fehler_attr($fehler, 'basis_url') ?>>
          <?= feld_fehler($fehler, 'basis_url') ?>
          <p class="hilfe">Diese Adresse steht später in den QR-Codes, z. B. <code>https://www.pichi.de/auftrag</code>.</p>
        </div>
      </fieldset>

      <fieldset class="karte">
        <legend>3. Datenbank</legend>
        <p class="hilfe">IONOS-Kundenbereich → Hosting → Datenbanken → MariaDB anlegen. Dort stehen Host, Datenbankname und Benutzer.</p>
        <div class="feld">
          <label for="db_host">Host</label>
          <input id="db_host" name="db_host" value="<?= $w('db_host') ?>" placeholder="db5000000000.hosting-data.io" required<?= fehler_attr($fehler, 'db_host') ?>>
          <?= feld_fehler($fehler, 'db_host') ?>
        </div>
        <div class="reihe">
          <div class="feld">
            <label for="db_name">Datenbankname</label>
            <input id="db_name" name="db_name" value="<?= $w('db_name') ?>" placeholder="dbs0000000" required<?= fehler_attr($fehler, 'db_name') ?>>
            <?= feld_fehler($fehler, 'db_name') ?>
          </div>
          <div class="feld">
            <label for="db_port">Port</label>
            <input id="db_port" name="db_port" value="<?= $w('db_port') ?>" inputmode="numeric">
          </div>
        </div>
        <div class="feld">
          <label for="db_benutzer">Benutzername</label>
          <input id="db_benutzer" name="db_benutzer" value="<?= $w('db_benutzer') ?>" placeholder="dbu0000000" autocomplete="off" required<?= fehler_attr($fehler, 'db_benutzer') ?>>
          <?= feld_fehler($fehler, 'db_benutzer') ?>
        </div>
        <div class="feld">
          <label for="db_passwort">Passwort</label>
          <input id="db_passwort" name="db_passwort" type="password" autocomplete="off">
        </div>
      </fieldset>

      <fieldset class="karte">
        <legend>4. E-Mail-Postfach (SMTP)</legend>
        <p class="hilfe">Das IONOS-Postfach, über das alle Mails verschickt werden (Absender und Antwortadresse).</p>
        <div class="feld">
          <label for="smtp_benutzer">Postfach-Adresse</label>
          <input id="smtp_benutzer" name="smtp_benutzer" type="email" value="<?= $w('smtp_benutzer') ?>" placeholder="werkstatt@pichi.de" autocomplete="off" required<?= fehler_attr($fehler, 'smtp_benutzer') ?>>
          <?= feld_fehler($fehler, 'smtp_benutzer') ?>
        </div>
        <div class="feld">
          <label for="smtp_passwort">Postfach-Passwort</label>
          <input id="smtp_passwort" name="smtp_passwort" type="password" autocomplete="off">
        </div>
        <div class="feld">
          <label for="smtp_absender_name">Absendername</label>
          <input id="smtp_absender_name" name="smtp_absender_name" value="<?= $w('smtp_absender_name') ?>">
        </div>
        <details>
          <summary>Server-Einstellungen (normalerweise nicht ändern)</summary>
          <div class="reihe">
            <div class="feld">
              <label for="smtp_host">SMTP-Server</label>
              <input id="smtp_host" name="smtp_host" value="<?= $w('smtp_host') ?>"<?= fehler_attr($fehler, 'smtp_host') ?>>
              <?= feld_fehler($fehler, 'smtp_host') ?>
            </div>
            <div class="feld">
              <label for="smtp_port">Port</label>
              <input id="smtp_port" name="smtp_port" value="<?= $w('smtp_port') ?>" inputmode="numeric">
            </div>
          </div>
          <div class="feld">
            <label for="smtp_verschluesselung">Verschlüsselung</label>
            <select id="smtp_verschluesselung" name="smtp_verschluesselung">
              <option value="tls" <?= $eingabe['smtp_verschluesselung'] !== 'ssl' ? 'selected' : '' ?>>STARTTLS (Port 587)</option>
              <option value="ssl" <?= $eingabe['smtp_verschluesselung'] === 'ssl' ? 'selected' : '' ?>>SSL/TLS (Port 465)</option>
            </select>
          </div>
        </details>
      </fieldset>
    <?php endif; ?>

    <fieldset class="karte">
      <legend><?= $konfigVorhanden ? '2' : '5' ?>. Werkstatt</legend>
      <div class="feld">
        <label for="empfaenger">Benachrichtigung „Neuer Auftrag“ an (optional, kommagetrennt)</label>
        <input id="empfaenger" name="empfaenger" value="<?= $w('empfaenger') ?>" placeholder="Standard: das Postfach" autocapitalize="off">
      </div>
      <div class="feld">
        <label for="passwort">Werkstatt-Passwort (mind. 8 Zeichen)</label>
        <input id="passwort" name="passwort" type="password" autocomplete="new-password" minlength="8" required<?= fehler_attr($fehler, 'passwort') ?>>
        <?= feld_fehler($fehler, 'passwort') ?>
      </div>
      <div class="feld">
        <label for="passwort_wdh">Passwort wiederholen</label>
        <input id="passwort_wdh" name="passwort_wdh" type="password" autocomplete="new-password" minlength="8" required<?= fehler_attr($fehler, 'passwort_wdh') ?>>
        <?= feld_fehler($fehler, 'passwort_wdh') ?>
      </div>
    </fieldset>

    <button type="submit" class="knopf knopf-primaer knopf-gross knopf-breit">Installieren</button>
  </form>
  <?php endif; ?>
<?php endif; ?>
