/* Memuat konten resmi dari backend: berita, galeri, fasilitas, struktur, tim IT,
   direktori siswa, dan proyek siswa. Tidak ada data cadangan: jika backend tidak
   terjangkau, halaman menampilkan keterangan "belum dapat dimuat".
   main.js dan pages.js menunggu window.TKJ_READY sebelum menampilkan halaman. */
(function () {
  "use strict";
  var D = window.TKJ_DATA, API = window.TKJ_API;
  if (!D || !API) return;

  function list(x) { return Array.isArray(x) ? x : []; }
  function text(x) { return typeof x === "string" ? x : ""; }

  /* true bila konten gagal dimuat (bukan sekadar kosong). */
  D.contentError = false;

  window.TKJ_READY = API.request("/api/v1/content", { timeout: 8000 }).then(function (c) {
    D.news = list(c.news).filter(function (n) { return /^\d{4}-\d{2}-\d{2}$/.test(text(n.date)); }).map(function (n) {
      return { date: n.date, category: text(n.category), title: text(n.title), summary: text(n.summary), body: text(n.body), image: n.image || "", author: text(n.author) };
    });

    D.gallery = list(c.gallery).filter(function (g) { return g.src; }).map(function (g) {
      return { caption: text(g.caption), src: g.src, author: text(g.author) };
    });

    D.facilities = list(c.facilities).map(function (f) {
      return { title: text(f.title), text: text(f.text), image: f.image || "" };
    });

    var org = c.org || {};
    function people(x) { return list(x).map(function (p) { return { role: text(p.role), name: text(p.name) }; }); }
    D.org = {
      head: org.head ? { role: text(org.head.role), name: text(org.head.name) } : null,
      teachers: { title: D.org.teachers.title, people: people(org.teachers) },
      students: { title: D.org.students.title, people: people(org.students) }
    };

    D.itTeam = list(c.it_team).map(function (m) {
      return { name: text(m.name), role: text(m.role), tasks: list(m.tasks).map(text) };
    });

    D.students = list(c.students).map(function (s) {
      return { name: text(s.name), className: text(s.class_name), bio: text(s.bio), photo: s.photo || "" };
    });
    var seen = {};
    D.classes = D.students.map(function (s) { return s.className; }).filter(function (k) {
      if (!k || seen[k]) return false; seen[k] = 1; return true;
    }).sort();

    D.projects = list(c.projects).map(function (p) {
      return {
        title: text(p.title), text: text(p.text), tech: list(p.tech).map(text), result: text(p.result),
        link: /^https?:\/\//i.test(text(p.link)) ? p.link : "", image: p.image || "",
        author: text(p.author), className: text(p.class_name)
      };
    });
  }).catch(function () { D.contentError = true; });
})();
