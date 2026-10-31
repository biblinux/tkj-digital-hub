/* TKJ Digital Hub frontend v4.0 */
/* Komponen bersama: header, footer, ikon.
   Dipakai semua halaman lewat <div id="site-header"> dan <div id="site-footer">.
   Setiap <body> memiliki data-root="" (halaman utama) atau data-root="../" (folder pages). */
(function () {
  "use strict";
  var D = window.TKJ_DATA;
  var root = document.body.dataset.root || "";

  var ICONS = {
    network: '<rect x="9" y="2" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="16" y="16" width="6" height="6" rx="1"/><path d="M12 8v4M5 16v-4h14v4"/>',
    server: '<rect x="3" y="3" width="18" height="7" rx="1.5"/><rect x="3" y="14" width="18" height="7" rx="1.5"/><path d="M7 6.5h.01M7 17.5h.01"/>',
    shield: '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
    monitor: '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    close: '<path d="M6 6l12 12M18 6L6 18"/>',
    chevron: '<path d="M6 9l6 6 6-6"/>'
  };

  window.icon = function (name, cls) {
    return '<svg class="icon ' + (cls || "") + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[name] || "") + "</svg>";
  };

  window.esc = function (s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  };

  var here = location.pathname.split("/").pop() || "index.html";
  function isActive(href) { return href.split("/").pop() === here; }
  function link(href) { return root + href; }

  function navItems() {
    return D.nav.map(function (n) {
      if (!n.items) {
        return '<li><a class="nav-link" href="' + link(n.href) + '"' + (isActive(n.href) ? ' aria-current="page"' : "") + ">" + esc(n.label) + "</a></li>";
      }
      var active = n.items.some(function (i) { return isActive(i.href); });
      return '<li class="has-dropdown">' +
        '<button type="button" class="nav-link nav-group' + (active ? " is-active" : "") + '" aria-expanded="false">' + esc(n.label) + icon("chevron", "chev") + "</button>" +
        '<ul class="dropdown">' + n.items.map(function (i) {
          return '<li><a href="' + link(i.href) + '"' + (isActive(i.href) ? ' aria-current="page"' : "") + ">" + esc(i.label) + "</a></li>";
        }).join("") + "</ul></li>";
    }).join("");
  }

  /* Tautan masuk: berubah menjadi "Akun saya" bila token login ada di sesi tab ini. */
  function signedIn() { try { return !!sessionStorage.getItem("tkj_token"); } catch (e) { return false; } }
  function loginItem() {
    return '<li><a class="nav-link nav-login" href="' + link("pages/masuk.html") + '"' + (isActive("pages/masuk.html") ? ' aria-current="page"' : "") + ">" + (signedIn() ? "Akun saya" : "Masuk") + "</a></li>";
  }

  function headerHTML() {
    return '<header class="site-header"><div class="wrap bar">' +
      '<a class="brand" href="' + link("index.html") + '"><img class="brand-logo" src="' + link("img/logo-tkj-128.png") + '" alt="" width="44" height="44">' +
      '<span class="brand-text"><strong>' + esc(D.site.name) + "</strong><small>" + esc(D.site.school) + "</small></span></a>" +
      '<button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-nav" aria-label="Buka menu">' + icon("menu") + "</button>" +
      '<nav id="site-nav" class="nav" aria-label="Navigasi utama"><ul class="nav-list">' + navItems() + loginItem() + "</ul>" +
      '<a class="btn btn--primary btn--sm nav-cta" href="' + link("pages/laporan-it.html") + '">Laporkan Masalah IT</a></nav>' +
      "</div></header>";
  }

  /* Kontak resmi: hanya tampil jika diisi di D.site.contact (alamat, telepon, email). */
  function contactHTML() {
    var c = D.site.contact || {}, rows = [];
    if (c.address) rows.push("<li>" + esc(c.address) + "</li>");
    if (c.phone) rows.push("<li>Telepon: " + esc(c.phone) + "</li>");
    if (c.email) rows.push('<li>Email: <a href="mailto:' + esc(c.email) + '">' + esc(c.email) + "</a></li>");
    return rows.length ? '<ul class="foot-contact">' + rows.join("") + "</ul>" : "";
  }

  function footerHTML() {
    var cols = D.nav.filter(function (n) { return n.items; });
    var lists = cols.map(function (c) {
      return "<div><h2 class=\"foot-title\">" + esc(c.label) + "</h2><ul>" + c.items.map(function (i) {
        return '<li><a href="' + link(i.href) + '">' + esc(i.label) + "</a></li>";
      }).join("") + "</ul></div>";
    }).join("");
    return '<footer class="site-footer"><div class="wrap foot-grid">' +
      '<div class="foot-about"><p class="foot-brand"><img class="foot-logo" src="' + link("img/logo-smk-128.png") + '" alt="Logo SMK Negeri 1 Leuwimunding" width="40" height="40">' + esc(D.site.name) + "</p>" +
      "<p>Website resmi jurusan " + esc(D.site.dept) + " " + esc(D.site.school) + ".</p>" + contactHTML() + "</div>" +
      lists +
      '<div><h2 class="foot-title">Informasi</h2><ul><li><a href="' + link("pages/berita.html") + '">Berita</a></li><li><a href="' + link("pages/topologi-jaringan.html") + '">Topologi Jaringan</a></li><li><a href="' + link("pages/masuk.html") + '">Masuk</a></li></ul></div>' +
      '</div><div class="wrap foot-bottom"><p>&copy; <span id="year"></span> ' + esc(D.site.dept) + " " + esc(D.site.school) + ".</p></div></footer>";
  }

  var h = document.getElementById("site-header");
  var f = document.getElementById("site-footer");
  if (h) h.outerHTML = headerHTML();
  if (f) f.outerHTML = footerHTML();
  var y = document.getElementById("year");
  if (y) y.textContent = new Date().getFullYear();
})();
