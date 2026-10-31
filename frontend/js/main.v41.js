/* Interaksi dasar situs. Memerlukan data.js dan components.js dimuat lebih dulu. */
(function () {
  "use strict";
  var D = window.TKJ_DATA;
  var root = document.body.dataset.root || "";
  var PH = root + "img/placeholder.svg";

  /* ---------- Navigasi ---------- */
  var toggle = document.querySelector(".nav-toggle");
  var nav = document.getElementById("site-nav");
  var groups = Array.prototype.slice.call(document.querySelectorAll(".nav-group"));
  var desktop = window.matchMedia("(min-width: 960px)");

  function setMenu(open) {
    if (!toggle || !nav) return;
    nav.classList.toggle("is-open", open);
    toggle.setAttribute("aria-expanded", String(open));
    toggle.setAttribute("aria-label", open ? "Tutup menu" : "Buka menu");
    toggle.innerHTML = icon(open ? "close" : "menu");
  }
  function closeGroups(except) {
    groups.forEach(function (g) {
      if (g !== except) g.setAttribute("aria-expanded", "false");
    });
  }

  if (toggle) toggle.addEventListener("click", function () {
    setMenu(toggle.getAttribute("aria-expanded") !== "true");
  });

  groups.forEach(function (g) {
    g.addEventListener("click", function () {
      if (!desktop.matches) return; /* di ponsel semua submenu sudah terbuka */
      var open = g.getAttribute("aria-expanded") === "true";
      closeGroups(g);
      g.setAttribute("aria-expanded", String(!open));
    });
  });

  document.addEventListener("click", function (e) {
    if (!e.target.closest(".has-dropdown")) closeGroups();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeGroups();
      if (toggle && toggle.getAttribute("aria-expanded") === "true") { setMenu(false); toggle.focus(); }
    }
  });
  desktop.addEventListener("change", function () { setMenu(false); closeGroups(); });

  /* ---------- Helper bersama (dipakai juga oleh pages.js) ---------- */
  function fmtDate(iso) {
    return new Date(iso).toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
  }
  /* Jika gambar gagal dimuat, diganti gambar penanda (bukan ikon rusak). */
  function img(alt, cls, src, ph) {
    var fallback = ph || PH;
    var url = src ? (/^https?:\/\//i.test(src) ? src : root + src) : fallback;
    return '<img class="' + (cls || "") + '" src="' + esc(url) + '" alt="' + esc(alt) +
      '" loading="lazy" onerror="this.onerror=null;this.src=\'' + fallback + '\'">';
  }
  /* Keterangan untuk bagian yang belum berisi (atau gagal dimuat). */
  function empty(msg) {
    return '<p class="empty-note">' + esc(D.contentError ? "Konten belum dapat dimuat. Muat ulang halaman atau coba lagi beberapa saat." : msg) + "</p>";
  }
  function mount(name, html) {
    var el = document.querySelector('[data-render="' + name + '"]');
    if (el) el.innerHTML = html;
    return el;
  }
  function sortedNews() {
    return D.news.slice().sort(function (a, b) { return b.date.localeCompare(a.date); });
  }
  function newsMeta(a) {
    return '<p class="meta"><span class="cat">' + esc(a.category) + '</span><time datetime="' + a.date + '">' + fmtDate(a.date) + "</time>" + (a.author ? ' <span class="by">Oleh ' + esc(a.author) + "</span>" : "") + "</p>";
  }
  function galleryTiles(list) {
    if (!list.length) return empty("Belum ada foto di galeri.");
    return list.map(function (g, i) {
      return '<figure class="tile"><button type="button" class="tile-btn" data-index="' + i + '" aria-label="Perbesar foto: ' + esc(g.caption) + '">' +
        '<span class="ph">' + img("Foto: " + g.caption, "", g.src) + "</span></button>" +
        "<figcaption>" + esc(g.caption) + (g.author ? ' <span class="by">Oleh ' + esc(g.author) + "</span>" : "") + "</figcaption></figure>";
    }).join("");
  }
  window.TKJ = { PH: PH, root: root, fmtDate: fmtDate, img: img, empty: empty, mount: mount, sortedNews: sortedNews, newsMeta: newsMeta, galleryTiles: galleryTiles };

  /* ---------- Lightbox galeri (dialog) ---------- */
  var lb, lbIndex = 0;
  function buildLightbox() {
    lb = document.createElement("dialog");
    lb.className = "lightbox";
    lb.setAttribute("aria-label", "Pratinjau foto");
    lb.innerHTML = '<div class="lb-inner"><div class="ph"></div><div class="lb-bar"><p class="lb-caption" aria-live="polite"></p>' +
      '<div class="lb-actions"><button type="button" class="lb-btn" data-lb="prev">Sebelumnya</button>' +
      '<button type="button" class="lb-btn" data-lb="next">Berikutnya</button>' +
      '<button type="button" class="lb-btn" data-lb="close">Tutup</button></div></div></div>';
    document.body.appendChild(lb);
    lb.addEventListener("click", function (e) {
      var b = e.target.closest("[data-lb]");
      if (b) {
        var a = b.dataset.lb;
        if (a === "close") lb.close();
        else showPhoto(lbIndex + (a === "next" ? 1 : -1));
      } else if (e.target === lb) { lb.close(); } /* klik latar gelap */
    });
    lb.addEventListener("keydown", function (e) {
      if (e.key === "ArrowRight") showPhoto(lbIndex + 1);
      if (e.key === "ArrowLeft") showPhoto(lbIndex - 1);
    });
  }
  function showPhoto(i) {
    var n = D.gallery.length;
    lbIndex = (i + n) % n;
    var g = D.gallery[lbIndex];
    lb.querySelector(".ph").innerHTML = img("Foto: " + g.caption, "", g.src);
    lb.querySelector(".lb-caption").textContent = g.caption + " (" + (lbIndex + 1) + " dari " + n + ")";
  }
  document.addEventListener("click", function (e) {
    var t = e.target.closest(".tile-btn");
    if (!t || !t.closest("[data-render='gallery'], [data-render='gallery-home']")) return;
    if (!lb) buildLightbox();
    showPhoto(parseInt(t.dataset.index, 10));
    lb.showModal();
  });

  /* ---------- Render beranda ---------- */
  /* Kompetensi bersifat statis: tampil langsung tanpa menunggu backend. */
  mount("competencies", D.competencies.map(function (c) {
    return '<li class="comp-item"><span class="comp-icon">' + icon(c.icon) + "</span><div><h3>" + esc(c.title) + "</h3><p>" + esc(c.text) + "</p></div></li>";
  }).join(""));

  /* Fasilitas, berita, dan galeri bisa berasal dari backend: tunggu paling lama beberapa detik. */
  function renderHome() {
    mount("facilities", D.facilities.length ? D.facilities.map(function (f) {
      return '<article class="fac"><div class="ph ph--wide">' + img("Foto " + f.title, "", f.image) + "</div>" +
        '<div class="fac-body"><h3>' + esc(f.title) + "</h3><p>" + esc(f.text) + "</p></div></article>";
    }).join("") : empty("Informasi fasilitas belum dipublikasikan."));

    var latest = sortedNews().slice(0, 3);
    var feat = latest[0];
    mount("news", feat ?
      '<article class="news-feat"><div class="ph ph--wide">' + img("Foto " + feat.title, "", feat.image) + "</div>" +
      newsMeta(feat) + "<h3>" + esc(feat.title) + "</h3><p>" + esc(feat.summary) + "</p></article>" +
      '<div class="news-list">' + latest.slice(1).map(function (a) {
        return '<article class="news-row">' + newsMeta(a) + "<h3>" + esc(a.title) + "</h3><p>" + esc(a.summary) + "</p></article>";
      }).join("") + "</div>" : empty("Belum ada berita."));

    mount("gallery-home", galleryTiles(D.gallery.slice(0, 6)));
    var more = document.querySelector("[data-needs='gallery']");
    if (more) more.hidden = !D.gallery.length;
  }
  (window.TKJ_READY || Promise.resolve()).then(renderHome);
})();
