/*
 * Druckbogen für QR-Aufkleber.
 * Erzeugt aus den Layout-Maßen (mm) die A4-Bögen mit je einem bzw. zwei
 * Aufklebern pro Nummer (Instrument + Abholschein).
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var ziel = document.getElementById('aufkleberboegen');
    if (!ziel || typeof qrcode !== 'function') return;

    var L = JSON.parse(ziel.dataset.layout);
    var von = parseInt(ziel.dataset.von, 10);
    var bis = parseInt(ziel.dataset.bis, 10);
    var start = Math.max(1, parseInt(ziel.dataset.start, 10) || 1);
    var basisUrl = ziel.dataset.url;
    var kurzUrl = ziel.dataset.kurzUrl;
    var proBogen = L.spalten * L.zeilen;
    var proNummer = parseInt(L.pro_nummer, 10) === 1 ? 1 : 2;

    // Druckformat = Seitengröße aus dem Layout
    try {
      var blatt = document.styleSheets[0];
      blatt.insertRule('@page { size: ' + L.seite_breite + 'mm ' + L.seite_hoehe + 'mm; margin: 0; }', blatt.cssRules.length);
    } catch (e) { /* Standard aus app.css (A4) */ }

    // Liste aller Etiketten
    var etiketten = [];
    for (var i = 1; i < start && i <= proBogen; i++) etiketten.push(null); // bereits benutzte Plätze
    for (var n = von; n <= bis; n++) {
      etiketten.push({ nummer: n, art: 'Instrument' });
      if (proNummer === 2) etiketten.push({ nummer: n, art: 'Abholschein' });
    }

    var qrCache = {};
    function qrSvg(nummer) {
      if (!qrCache[nummer]) {
        var qr = qrcode(0, 'M');
        qr.addData(basisUrl + nummer);
        qr.make();
        qrCache[nummer] = qr.createSvgTag({ cellSize: 1, margin: 2, scalable: true });
      }
      return qrCache[nummer];
    }

    var klein = L.etikett_hoehe < 22 || L.etikett_breite < 45;
    var fragment = document.createDocumentFragment();
    var boegen = [];

    for (var b = 0; b * proBogen < etiketten.length; b++) {
      var huelle = document.createElement('div');
      huelle.className = 'bogen-vorschau';
      var bogen = document.createElement('div');
      bogen.className = 'bogen';
      bogen.style.width = L.seite_breite + 'mm';
      bogen.style.height = L.seite_hoehe + 'mm';

      for (var p = 0; p < proBogen; p++) {
        var e = etiketten[b * proBogen + p];
        if (!e) continue;
        var spalte = p % L.spalten;
        var zeile = Math.floor(p / L.spalten);
        var el = document.createElement('div');
        el.className = 'etikett' + (e.art === 'Abholschein' ? ' etikett-abholschein' : '') + (klein ? ' etikett-klein' : '');
        el.style.left = (L.rand_links + spalte * (L.etikett_breite + L.abstand_h)) + 'mm';
        el.style.top = (L.rand_oben + zeile * (L.etikett_hoehe + L.abstand_v)) + 'mm';
        el.style.width = L.etikett_breite + 'mm';
        el.style.height = L.etikett_hoehe + 'mm';

        var qrBox = document.createElement('div');
        qrBox.className = 'etikett-qr';
        qrBox.innerHTML = qrSvg(e.nummer); // selbst erzeugtes SVG, keine Fremddaten

        var text = document.createElement('div');
        text.className = 'etikett-text';
        text.appendChild(span('etikett-art', proNummer === 2 ? e.art : 'Auftrag'));
        text.appendChild(span('etikett-nummer', String(e.nummer)));
        text.appendChild(span('etikett-url', kurzUrl));

        el.appendChild(qrBox);
        el.appendChild(text);
        bogen.appendChild(el);
      }
      huelle.appendChild(bogen);
      fragment.appendChild(huelle);
      boegen.push(bogen);
    }
    ziel.appendChild(fragment);

    // Bildschirmvorschau auf verfügbare Breite verkleinern
    function skalieren() {
      boegen.forEach(function (bogen) {
        var huelle = bogen.parentNode;
        bogen.style.transform = '';
        var breite = bogen.offsetWidth;
        var verfuegbar = Math.min(huelle.clientWidth, 900);
        var faktor = Math.min(1, verfuegbar / breite);
        bogen.style.transform = 'scale(' + faktor + ')';
        bogen.style.marginLeft = Math.max(0, (huelle.clientWidth - breite * faktor) / 2) + 'px';
        huelle.style.height = (bogen.offsetHeight * faktor + 20) + 'px';
      });
    }
    skalieren();
    window.addEventListener('resize', skalieren);

    var druckKnopf = document.querySelector('[data-drucken]');
    if (druckKnopf) druckKnopf.addEventListener('click', function () { window.print(); });
  });

  function span(klasse, text) {
    var s = document.createElement('span');
    s.className = klasse;
    s.textContent = text;
    return s;
  }
})();
