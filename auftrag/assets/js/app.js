/*
 * Komfortfunktionen – die Anwendung funktioniert auch ohne JavaScript.
 */
(function () {
  'use strict';
  document.documentElement.classList.add('js');

  document.addEventListener('DOMContentLoaded', function () {
    doppeltesAbsendenVerhindern();
    bestaetigungen();
    mindestensEineReparatur();
    ungespeicherteAenderungen();
    autoAbsenden();
    reparaturListe();
    fehlerFokussieren();
  });

  /* Formular nur einmal absenden (z. B. bei Doppeltipp) */
  function doppeltesAbsendenVerhindern() {
    // Am document registriert, damit die Formular-Prüfungen vorher laufen
    document.addEventListener('submit', function (ev) {
      var form = ev.target;
      if (ev.defaultPrevented || form.method.toLowerCase() !== 'post') return;
      if (form.dataset.sendet === '1') { ev.preventDefault(); return; }
      form.dataset.sendet = '1';
      var knopf = ev.submitter;
      if (knopf) knopf.classList.add('sendet');
      // Sicherheitsnetz, falls die Antwort ausbleibt
      setTimeout(function () { form.dataset.sendet = ''; if (knopf) knopf.classList.remove('sendet'); }, 8000);
    });
    window.addEventListener('pageshow', function () {
      document.querySelectorAll('form').forEach(function (f) { f.dataset.sendet = ''; });
      document.querySelectorAll('.sendet').forEach(function (k) { k.classList.remove('sendet'); });
    });
  }

  /* Sicherheitsabfrage für Knöpfe mit data-bestaetigen="…" */
  function bestaetigungen() {
    document.addEventListener('click', function (ev) {
      var el = ev.target.closest('[data-bestaetigen]');
      if (el && !window.confirm(el.getAttribute('data-bestaetigen'))) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
      }
    }, true);
  }

  /* Clientseitige Prüfung: Pflichtfelder + mindestens eine Reparatur */
  function mindestensEineReparatur() {
    document.querySelectorAll('form[data-formular="auftrag"]').forEach(function (form) {
      form.addEventListener('submit', function (ev) {
        var ersterFehler = null;
        form.querySelectorAll('.feld-fehler.js-fehler').forEach(function (f) { f.remove(); });
        form.querySelectorAll('[aria-invalid="true"]').forEach(function (f) { f.removeAttribute('aria-invalid'); });

        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (feld) {
          if (feld.type === 'radio') return;
          if (!feld.checkValidity()) {
            feld.setAttribute('aria-invalid', 'true');
            var feldBox = feld.closest('.feld') || feld.closest('.checkbox-zeile');
            if (feldBox) meldungAnhaengen(feldBox, fehlertext(feld));
            ersterFehler = ersterFehler || feld;
          }
        });

        var radios = form.querySelectorAll('input[name="instrument_typ"]');
        if (radios.length && !form.querySelector('input[name="instrument_typ"]:checked')) {
          meldungAnhaengen(radios[0].closest('.kacheln'), 'Bitte das Instrument auswählen.', true);
          ersterFehler = ersterFehler || radios[0];
        }

        var box = form.querySelector('[data-mindestens-eins]');
        if (box && !box.querySelector('input:checked')) {
          meldungAnhaengen(box, box.getAttribute('data-mindestens-eins'), true);
          ersterFehler = ersterFehler || box.querySelector('input');
        }

        if (ersterFehler) {
          ev.preventDefault();
          ev.stopImmediatePropagation();
          var ziel = ersterFehler.closest('.feld, .kacheln, .checkbox-zeile') || ersterFehler;
          ziel.scrollIntoView({ behavior: 'smooth', block: 'center' });
          if (ersterFehler.type !== 'radio' && ersterFehler.type !== 'checkbox') {
            setTimeout(function () { ersterFehler.focus({ preventScroll: true }); }, 300);
          }
        }
      });
    });
  }

  function fehlertext(feld) {
    if (feld.validity.valueMissing) {
      return feld.type === 'checkbox' ? 'Bitte bestätigen.' : 'Bitte ausfüllen.';
    }
    if (feld.type === 'email') return 'Bitte eine gültige E-Mail-Adresse angeben.';
    if (feld.name === 'plz') return 'Bitte eine gültige Postleitzahl (4–5 Ziffern) angeben.';
    return 'Bitte prüfen.';
  }

  function meldungAnhaengen(el, text, danach) {
    var p = document.createElement('p');
    p.className = 'feld-fehler js-fehler';
    p.textContent = text;
    if (danach) el.insertAdjacentElement('afterend', p); else el.appendChild(p);
  }

  /* Warnung beim Verlassen mit ungespeicherten Änderungen */
  function ungespeicherteAenderungen() {
    var form = document.querySelector('form[data-warnen-bei-aenderung]');
    if (!form) return;
    var geaendert = false;
    form.addEventListener('input', function () { geaendert = true; });
    form.addEventListener('change', function () { geaendert = true; });
    form.addEventListener('submit', function () { geaendert = false; });
    window.addEventListener('beforeunload', function (ev) {
      if (geaendert) { ev.preventDefault(); ev.returnValue = ''; }
    });
  }

  /* Filter „Erledigte anzeigen“ sofort anwenden */
  function autoAbsenden() {
    document.querySelectorAll('[data-auto-absenden]').forEach(function (el) {
      el.addEventListener('change', function () { el.form.submit(); });
    });
  }

  /* Reparaturliste: Reihenfolge per Pfeil ändern */
  function reparaturListe() {
    var liste = document.querySelector('[data-reparatur-liste]');
    if (!liste) return;
    function neuNummerieren() {
      liste.querySelectorAll('.rep-pos').forEach(function (feld, i) { feld.value = i + 1; });
    }
    liste.addEventListener('click', function (ev) {
      var hoch = ev.target.closest('[data-nach-oben]');
      var runter = ev.target.closest('[data-nach-unten]');
      if (!hoch && !runter) return;
      var zeile = ev.target.closest('li');
      if (hoch && zeile.previousElementSibling) {
        liste.insertBefore(zeile, zeile.previousElementSibling);
      } else if (runter && zeile.nextElementSibling) {
        liste.insertBefore(zeile.nextElementSibling, zeile);
      }
      neuNummerieren();
      (hoch || runter).focus();
      zeile.closest('form').dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  /* Nach serverseitiger Prüfung zum ersten Fehler springen */
  function fehlerFokussieren() {
    var feld = document.querySelector('.inhalt [aria-invalid="true"], .inhalt .feld-fehler');
    if (feld) feld.scrollIntoView({ block: 'center' });
  }
})();
