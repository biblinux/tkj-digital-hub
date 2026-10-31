/* Interaksi khusus halaman dalam. Dipilih lewat <body data-page="...">.
   Memerlukan data.js, components.js, dan main.js (window.TKJ) dimuat lebih dulu. */
(function () {
  "use strict";
  var D = window.TKJ_DATA;
  var T = window.TKJ;
  var AV = T.root + "img/avatar.svg";
  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };

  function avatar(src, alt) { return '<div class="avatar">' + T.img(alt || "Foto", "", src, AV) + "</div>"; }

  /* ---------- Profil TKJ ---------- */
  function profil() {
    var hasV = !!(D.vision.text), hasM = D.mission.items.length > 0;
    var panel = document.querySelector('[data-render="vision"]');
    panel = panel && panel.closest(".panel");
    if (panel) panel.hidden = !(hasV || hasM);
    if (hasV) T.mount("vision", "<p>" + esc(D.vision.text) + "</p>");
    if (hasM) T.mount("mission", '<ol class="mission-list">' + D.mission.items.map(function (m) { return "<li>" + esc(m) + "</li>"; }).join("") + "</ol>");
  }

  /* ---------- Struktur organisasi ---------- */
  function compact(p) {
    return '<div class="person person--compact">' + avatar("", "Foto " + p.name) + "<div><h3>" + esc(p.name) + '</h3><p class="role">' + esc(p.role) + "</p></div></div>";
  }
  function struktur() {
    var o = D.org;
    var groups = [o.teachers, o.students].filter(function (b) { return b.people.length; });
    if (!o.head && !groups.length) { T.mount("org", T.empty("Struktur organisasi belum dipublikasikan.")); return; }
    T.mount("org",
      (o.head ? '<div class="org-head">' + compact(o.head) + '</div><div class="org-line" aria-hidden="true"></div>' : "") +
      '<div class="org-branches">' + groups.map(function (b) {
        return '<div class="org-branch"><h3>' + esc(b.title) + '</h3><div class="stack">' + b.people.map(compact).join("") + "</div></div>";
      }).join("") + "</div>");
  }

  /* ---------- Tim IT ---------- */
  function timIt() {
    T.mount("team", D.itTeam.length ? D.itTeam.map(function (m) {
      return '<article class="person">' + avatar("", "Foto " + m.name) + "<h3>" + esc(m.name) + '</h3><p class="role">' + esc(m.role) + "</p><ul>" +
        m.tasks.map(function (t) { return "<li>" + esc(t) + "</li>"; }).join("") + "</ul></article>";
    }).join("") : T.empty("Anggota tim IT belum dipublikasikan."));
    T.mount("services", D.itServices.map(function (s) {
      return '<div class="panel"><h3>' + esc(s.title) + "</h3><p>" + esc(s.text) + "</p></div>";
    }).join(""));
  }

  /* ---------- Teknisi ---------- */
  function teknisi() {
    if (!D.technicians.length) { T.mount("technicians", T.empty("Data teknisi belum dipublikasikan.")); return; }
    T.mount("technicians",
      '<div class="row row-head" aria-hidden="true"><span>Nama</span><span>Bidang keahlian</span><span>Tugas dan tanggung jawab</span></div>' +
      D.technicians.map(function (t) {
        return '<div class="row"><h3>' + esc(t.name) + '</h3><span class="field-name">' + esc(t.field) + "</span><p>" + esc(t.duties) + "</p></div>";
      }).join(""));
  }

  /* ---------- Direktori siswa ---------- */
  function siswa() {
    var tool = $("#student-tool"), off = $("#student-off");
    if (!D.features.studentDirectory) { tool.hidden = true; off.hidden = false; return; }
    var q = $("#q"), kelas = $("#kelas"), grid = $("#student-grid"), count = $("#student-count"), empty = $("#student-empty");
    D.classes.forEach(function (c) { kelas.add(new Option(c, c)); });
    if (!D.students.length) {
      tool.hidden = true; off.hidden = false;
      off.innerHTML = "<h2>Belum ada siswa terdaftar</h2><p style=\"margin-inline:auto\">" +
        esc(D.contentError ? "Konten belum dapat dimuat. Muat ulang halaman." : "Profil siswa akan tampil di sini setelah akun siswa diaktifkan.") + "</p>";
      return;
    }
    function render() {
      var term = q.value.trim().toLowerCase();
      var list = D.students.filter(function (s) {
        var okClass = !kelas.value || s.className === kelas.value;
        var okText = !term || (s.name + " " + s.className + " " + s.bio).toLowerCase().indexOf(term) !== -1;
        return okClass && okText;
      });
      grid.innerHTML = list.map(function (s) {
        return '<article class="student">' + avatar(s.photo, "Foto " + s.name) + "<h3>" + esc(s.name) + "</h3>" + (s.className ? "<p>" + esc(s.className) + "</p>" : "") + (s.bio ? '<p class="bio">' + esc(s.bio) + "</p>" : "") + "</article>";
      }).join("");
      count.textContent = "Menampilkan " + list.length + " dari " + D.students.length + " siswa";
      empty.hidden = list.length > 0;
    }
    q.addEventListener("input", render);
    kelas.addEventListener("change", render);
    $("#student-reset").addEventListener("click", function () { q.value = ""; kelas.value = ""; render(); q.focus(); });
    render();
  }

  /* ---------- Topologi jaringan ---------- */
  function topologi() {
    var info = $("#device-info"), devs = document.querySelectorAll(".dev");
    function byId(id) { return D.devices.filter(function (d) { return d.id === id; })[0]; }
    function select(id) {
      var d = byId(id);
      if (!d) return;
      devs.forEach(function (n) { n.classList.toggle("is-selected", n.dataset.device === id); n.setAttribute("aria-pressed", String(n.dataset.device === id)); });
      info.innerHTML = "<h3>" + esc(d.name) + '</h3><p class="role">' + esc(d.role) + "</p><p>" + esc(d.text) + "</p>";
    }
    devs.forEach(function (n) {
      n.addEventListener("click", function () { select(n.dataset.device); });
      n.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") { e.preventDefault(); select(n.dataset.device); }
      });
    });
    T.mount("legend", D.devices.map(function (d) {
      return "<li><strong>" + esc(d.name) + "</strong><span>" + esc(d.role) + ". " + esc(d.text) + "</span></li>";
    }).join(""));
  }

  /* ---------- Proyek siswa ---------- */
  function projectCard(p, mine) {
    var by = p.author ? "Oleh " + p.author + (p.className ? " (" + p.className + ")" : "") : "";
    return '<article class="project"><div class="ph ph--wide">' + T.img("Foto proyek " + p.title, "", p.image) + "</div>" +
      '<div class="project-body"><h3>' + esc(p.title) + "</h3>" + (by ? '<p class="meta">' + esc(by) + "</p>" : "") +
      '<p class="pre-wrap">' + esc(p.text) + "</p>" +
      (p.tech && p.tech.length ? '<ul class="chips" aria-label="Teknologi yang digunakan">' + p.tech.map(function (t) { return "<li>" + esc(t) + "</li>"; }).join("") + "</ul>" : "") +
      (p.result ? '<p class="result">' + esc(p.result) + "</p>" : "") +
      (p.link ? '<p><a href="' + esc(p.link) + '" rel="noopener noreferrer nofollow" target="_blank">Lihat proyek</a></p>' : "") +
      (mine ? '<p class="meta"><span class="badge-status">' + esc(mine.status_label) + "</span>" + (mine.review_note ? " " + esc(mine.review_note) : "") + "</p>" : "") +
      "</div></article>";
  }

  function proyek() {
    T.mount("projects", D.projects.length ? D.projects.map(function (p) { return projectCard(p); }).join("") : T.empty("Belum ada proyek yang dipublikasikan."));
    projectSubmit();
  }

  /* Pengiriman proyek oleh siswa yang sudah masuk. */
  function projectSubmit() {
    var API = window.TKJ_API, box = $("#project-submit");
    if (!box) return;
    var login = $("#ps-login"), formBox = $("#ps-form"), mineBox = $("#ps-mine"), form = $("#form-project");
    var status = $("#ps-status"), editing = null, mineList = [];

    function msg(ok, text) {
      status.hidden = false;
      status.className = ok ? "callout callout--info" : "callout";
      status.innerHTML = "<p><strong>" + (ok ? "Berhasil." : "Gagal.") + "</strong> " + esc(text) + "</p>";
    }
    function show(loggedIn) { login.hidden = loggedIn; formBox.hidden = !loggedIn; mineBox.hidden = !loggedIn; }
    function resetForm() {
      editing = null; form.reset(); $("#ps-cancel").hidden = true; $("#ps-submit").textContent = "Kirim proyek";
      $("#ps-form-title").textContent = "Kirim proyek Anda"; $("#ps-image-note").hidden = true;
    }
    function loadMine() {
      API.request("/api/v1/projects", { auth: true }).then(function (r) {
        mineList = r.projects || [];
        $("#ps-list").innerHTML = mineList.length ? mineList.map(function (p) {
          return '<div class="ps-item">' + projectCard({ title: p.title, text: p.description, tech: (p.technologies || "").split(",").map(function (x) { return x.trim(); }).filter(Boolean), result: p.result || "", link: p.link_url || "", image: p.image || "" }, p) +
            '<div class="form-actions"><button type="button" class="btn btn--ghost btn--sm" data-edit="' + p.id + '">Ubah</button>' +
            '<button type="button" class="btn btn--ghost btn--sm" data-del="' + p.id + '">Hapus</button></div></div>';
        }).join("") : "<p>Anda belum mengirim proyek.</p>";
      }, function (err) {
        if (err.status === 401) { API.clearToken(); show(false); return; }
        $("#ps-list").innerHTML = "<p>" + esc(err.message) + "</p>";
      });
    }

    $("#ps-list").addEventListener("click", function (e) {
      var ed = e.target.closest("[data-edit]"), del = e.target.closest("[data-del]");
      if (ed) {
        var p = mineList.filter(function (x) { return String(x.id) === ed.dataset.edit; })[0];
        if (!p) return;
        editing = p.id;
        form.elements.title.value = p.title; form.elements.description.value = p.description;
        form.elements.technologies.value = p.technologies || ""; form.elements.result.value = p.result || "";
        form.elements.link_url.value = p.link_url || "";
        $("#ps-cancel").hidden = false; $("#ps-submit").textContent = "Simpan perubahan";
        $("#ps-form-title").textContent = "Ubah proyek"; $("#ps-image-note").hidden = !p.image;
        form.scrollIntoView({ behavior: "smooth", block: "start" });
      } else if (del && window.confirm("Hapus proyek ini?")) {
        API.request("/api/v1/projects/" + del.dataset.del, { method: "DELETE", auth: true })
          .then(function () { msg(true, "Proyek dihapus."); loadMine(); }, function (err) { msg(false, err.message); });
      }
    });
    $("#ps-cancel").addEventListener("click", resetForm);

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var title = form.elements.title.value.trim(), desc = form.elements.description.value.trim();
      if (title.length < 5) { msg(false, "Judul proyek minimal 5 karakter."); return; }
      if (desc.length < 20) { msg(false, "Deskripsi minimal 20 karakter."); return; }
      var f = form.elements.image.files[0];
      if (f && !/^image\/(jpeg|png|webp)$/.test(f.type)) { msg(false, "Format foto harus JPG, PNG, atau WebP."); return; }
      if (f && f.size > 5 * 1024 * 1024) { msg(false, "Ukuran foto maksimal 5 MB."); return; }
      var fd = new FormData();
      ["title", "description", "technologies", "result", "link_url"].forEach(function (k) { fd.append(k, form.elements[k].value.trim()); });
      if (f) fd.append("image", f);
      if (editing && form.elements.remove_image && form.elements.remove_image.checked) fd.append("remove_image", "1");
      var btn = $("#ps-submit"); btn.disabled = true;
      API.request("/api/v1/projects" + (editing ? "/" + editing : ""), { method: "POST", auth: true, form: fd, timeout: 30000 })
        .then(function (r) { msg(true, r.message); resetForm(); loadMine(); })
        .catch(function (err) { if (err.status === 401) { API.clearToken(); show(false); } msg(false, err.message); })
        .then(function () { btn.disabled = false; });
    });

    if (API.getToken()) {
      API.me().then(function () { show(true); loadMine(); }, function () { API.clearToken(); show(false); });
    } else { show(false); }
  }

  /* ---------- Kirim berita / foto galeri (perlu persetujuan admin) ---------- */
  function submitBox(kind) {
    var API = window.TKJ_API, box = $("#submit-box");
    if (!box) return;
    var login = $("#sb-login"), formBox = $("#sb-form"), mineBox = $("#sb-mine"), form = $("#form-sub"), status = $("#sb-status");
    function msg(ok, text) {
      status.hidden = false;
      status.className = ok ? "callout callout--info" : "callout";
      status.innerHTML = "<p><strong>" + (ok ? "Berhasil." : "Gagal.") + "</strong> " + esc(text) + "</p>";
    }
    function show(on) { login.hidden = on; formBox.hidden = !on; mineBox.hidden = !on; }
    function loadMine() {
      API.request("/api/v1/submissions", { auth: true }).then(function (r) {
        var items = r[kind] || [];
        $("#sb-list").innerHTML = items.length ? items.map(function (x) {
          var title = kind === "news" ? x.title : x.caption;
          return '<div class="sb-item"><div><strong>' + esc(title) + '</strong> <span class="badge-status">' + esc(x.status_label) + "</span>" +
            (x.review_note ? '<div class="meta">' + esc(x.review_note) + "</div>" : "") + "</div>" +
            '<button type="button" class="btn btn--ghost btn--sm" data-del="' + x.id + '">Hapus</button></div>';
        }).join("") : "<p>Anda belum mengirim apa pun.</p>";
      }, function (err) {
        if (err.status === 401) { API.clearToken(); show(false); return; }
        $("#sb-list").innerHTML = "<p>" + esc(err.message) + "</p>";
      });
    }
    $("#sb-list").addEventListener("click", function (e) {
      var d = e.target.closest("[data-del]");
      if (d && window.confirm("Hapus kiriman ini?")) {
        API.request("/api/v1/submissions/" + kind + "/" + d.dataset.del, { method: "DELETE", auth: true })
          .then(function () { msg(true, "Kiriman dihapus."); loadMine(); }, function (err) { msg(false, err.message); });
      }
    });
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var f = form.elements.image.files[0], fd = new FormData();
      if (kind === "news") {
        if (form.elements.title.value.trim().length < 5) { msg(false, "Judul minimal 5 karakter."); return; }
        if (form.elements.summary.value.trim().length < 10) { msg(false, "Ringkasan minimal 10 karakter."); return; }
        ["title", "category", "summary", "body"].forEach(function (k) { fd.append(k, form.elements[k].value.trim()); });
      } else {
        if (form.elements.caption.value.trim().length < 3) { msg(false, "Keterangan minimal 3 karakter."); return; }
        if (!f) { msg(false, "Pilih foto terlebih dahulu."); return; }
        fd.append("caption", form.elements.caption.value.trim());
      }
      if (f && !/^image\/(jpeg|png|webp)$/.test(f.type)) { msg(false, "Format foto harus JPG, PNG, atau WebP."); return; }
      if (f && f.size > 5 * 1024 * 1024) { msg(false, "Ukuran foto maksimal 5 MB."); return; }
      if (f) fd.append("image", f);
      var btn = $("#sb-submit"); btn.disabled = true;
      API.request("/api/v1/submissions/" + kind, { method: "POST", auth: true, form: fd, timeout: 30000 })
        .then(function (r) { msg(true, r.message); form.reset(); loadMine(); })
        .catch(function (err) { if (err.status === 401) { API.clearToken(); show(false); } msg(false, err.message); })
        .then(function () { btn.disabled = false; });
    });
    if (API.getToken()) {
      API.me().then(function () { show(true); loadMine(); }, function () { API.clearToken(); show(false); });
    } else { show(false); }
  }

  /* ---------- Galeri ---------- */
  function galeri() { T.mount("gallery", T.galleryTiles(D.gallery)); submitBox("gallery"); }

  /* ---------- Berita ---------- */
  function berita() {
    submitBox("news");
    var all = T.sortedNews(), cats = ["Semua"], current = "Semua";
    all.forEach(function (a) { if (cats.indexOf(a.category) === -1) cats.push(a.category); });
    var pills = $("#news-pills"), list = $("#news-list"), count = $("#news-count");
    if (!all.length) {
      pills.innerHTML = ""; count.textContent = "";
      list.innerHTML = T.empty("Belum ada berita.");
      return;
    }
    function render() {
      var items = all.filter(function (a) { return current === "Semua" || a.category === current; });
      list.innerHTML = items.map(function (a) {
        return '<article class="news-item"><div class="ph">' + T.img("Foto " + a.title, "", a.image) + "</div><div>" + T.newsMeta(a) + "<h2>" + esc(a.title) + "</h2><p>" + esc(a.summary) + "</p>" +
          (a.body ? '<details class="news-more"><summary>Baca selengkapnya</summary><p>' + esc(a.body) + "</p></details>" : "") + "</div></article>";
      }).join("");
      count.textContent = items.length + " artikel" + (current === "Semua" ? "" : " dalam kategori " + current);
      pills.querySelectorAll(".pill").forEach(function (b) { b.setAttribute("aria-pressed", String(b.dataset.cat === current)); });
    }
    pills.innerHTML = cats.map(function (c) { return '<button type="button" class="pill" data-cat="' + esc(c) + '" aria-pressed="false">' + esc(c) + "</button>"; }).join("");
    pills.addEventListener("click", function (e) {
      var b = e.target.closest(".pill");
      if (b) { current = b.dataset.cat; render(); }
    });
    render();
  }

  /* ---------- Formulir ----------
     Validasi di browser hanya untuk kemudahan pengguna; server memeriksa ulang.
     send(form) mengembalikan Promise berisi {title, text} untuk ditampilkan,
     atau nilai kosong jika tidak ada pesan yang perlu ditampilkan. */
  function initForm(form, rules, send) {
    var status = form.querySelector('[role="status"]');
    form.querySelectorAll("select[data-options]").forEach(function (sel) {
      D.forms[sel.dataset.options].forEach(function (o) { sel.add(new Option(o, o)); });
    });
    form.querySelectorAll("[data-radios]").forEach(function (box) {
      box.innerHTML = D.forms[box.dataset.radios].map(function (o, i) {
        return '<label><input type="radio" name="' + box.dataset.name + '" value="' + esc(o) + '"> ' + esc(o) + "</label>";
      }).join("");
    });
    form.querySelectorAll("[data-count]").forEach(function (ta) {
      var out = document.getElementById(ta.dataset.count), max = ta.getAttribute("maxlength");
      function upd() { out.textContent = ta.value.length + " / " + max + " karakter"; }
      ta.addEventListener("input", upd); upd();
    });

    function fieldOf(name) { var el = form.querySelector('[name="' + name + '"]'); return { el: el, box: el.closest(".field") }; }
    function check(name) {
      var f = fieldOf(name), value = String(form.elements[name].value || "").trim();
      var msg = rules[name](value);
      f.box.classList.toggle("has-error", !!msg);
      document.getElementById("err-" + name).textContent = msg;
      if (name !== "prioritas") f.el.setAttribute("aria-invalid", msg ? "true" : "false");
      return msg;
    }
    form.addEventListener("focusout", function (e) { if (e.target.name && rules[e.target.name]) check(e.target.name); });
    form.addEventListener("change", function (e) { if (e.target.name && rules[e.target.name]) check(e.target.name); });
    form.addEventListener("input", function (e) {
      if (e.target.name && rules[e.target.name] && fieldOf(e.target.name).box.classList.contains("has-error")) check(e.target.name);
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (form.elements.website && form.elements.website.value) return; /* kolom jebakan spam terisi */
      var bad = Object.keys(rules).filter(function (n) { return check(n); });
      status.hidden = false;
      if (bad.length) {
        status.className = "callout";
        status.innerHTML = "<p><strong>Formulir belum lengkap.</strong> Periksa " + bad.length + " isian yang ditandai merah.</p>";
        var first = fieldOf(bad[0]).el; first.focus();
      } else if (send) {
        var btn = form.querySelector('button[type="submit"]');
        if (form.dataset.busy) return;
        form.dataset.busy = "1"; btn.disabled = true;
        status.className = "callout callout--info";
        status.innerHTML = "<p><strong>Mengirim…</strong> Mohon tunggu.</p>";
        Promise.resolve().then(function () { return send(form); }).then(function (result) {
          form.reset();
          if (result) {
            status.hidden = false;
            status.className = "callout callout--info";
            status.innerHTML = "<p><strong>" + esc(result.title) + "</strong> " + esc(result.text) + "</p>";
            status.scrollIntoView({ block: "nearest", behavior: "smooth" });
          }
        }).catch(function (err) {
          status.hidden = false;
          status.className = "callout";
          status.innerHTML = "<p><strong>Gagal mengirim.</strong> " + esc(err && err.message ? err.message : "Terjadi kesalahan.") + "</p>";
        }).then(function () { delete form.dataset.busy; btn.disabled = false; });
      } else {
        status.className = "callout callout--info";
        status.innerHTML = "<p><strong>" + esc(form.dataset.okTitle) + "</strong> " + esc(form.dataset.okText) + "</p>";
        status.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
    });
    form.addEventListener("reset", function () {
      status.hidden = true;
      Object.keys(rules).forEach(function (n) {
        var f = fieldOf(n); f.box.classList.remove("has-error");
        document.getElementById("err-" + n).textContent = "";
      });
      setTimeout(function () { form.querySelectorAll("[data-count]").forEach(function (t) { t.dispatchEvent(new Event("input")); }); }, 0);
    });
  }
  function required(label) { return function (v) { return v ? "" : label + " wajib diisi."; }; }
  function lengthRule(label, min, max) {
    return function (v) {
      if (!v) return label + " wajib diisi.";
      if (v.length < min) return label + " terlalu singkat (minimal " + min + " karakter).";
      if (v.length > max) return label + " terlalu panjang (maksimal " + max + " karakter).";
      return "";
    };
  }
  function saran() {
    var API = window.TKJ_API;
    initForm($("#form-saran"), {
      nama: function (v) { return v.length > 60 ? "Nama maksimal 60 karakter." : ""; },
      kategori: required("Kategori"),
      pesan: lengthRule("Pesan", 10, 500)
    }, function (form) {
      var body = {
        subject: "Saran: " + form.elements.kategori.value, /* backend meminta subjek; diisi dari kategori */
        message: form.elements.pesan.value.trim(),
        website: form.elements.website ? form.elements.website.value : ""
      };
      var nama = form.elements.nama.value.trim();
      if (nama) body.name = nama;
      return API.request("/api/v1/suggestions", { method: "POST", body: body }).then(function () {
        return { title: "Saran terkirim.", text: "Terima kasih, saran Anda sudah kami terima." };
      });
    });
  }

  /* Label tautan header: "Masuk" atau "Akun saya" sesuai status login. */
  function syncHeader() {
    var a = document.querySelector(".nav-login");
    if (a) a.textContent = window.TKJ_API && window.TKJ_API.getToken() ? "Akun saya" : "Masuk";
  }

  /* Halaman Masuk: satu pintu login untuk semua peran. */
  function masuk() {
    var API = window.TKJ_API;
    var loginCard = $("#login-card"), accountCard = $("#account-card");
    var STAFF = { technician: true };
    if (/[?&]aktif=1/.test(location.search)) $("#activated-note").hidden = false;

    function showLoggedOut() { loginCard.hidden = false; accountCard.hidden = true; syncHeader(); }
    function showLoggedIn(user) {
      $("#who").textContent = user.name;
      $("#role").textContent = user.role_label;
      var staff = !!STAFF[user.role];
      $("#admin-link").hidden = !staff;
      $("#admin-note").hidden = !staff;
      if (staff) $("#admin-link").href = API.base + "/login";
      loginCard.hidden = true; accountCard.hidden = false; syncHeader();
      loadProfile();
    }

    initForm($("#form-login"), {
      login: required("Username atau email"),
      password: required("Kata sandi")
    }, function (form) {
      return API.login(form.elements.login.value.trim(), form.elements.password.value).then(function (user) {
        showLoggedIn(user);
        return null;
      });
    });

    $("#btn-logout").addEventListener("click", function () { API.logout().then(showLoggedOut); });

    /* ----- Profil: nama tampilan, email, foto ----- */
    var pStatus = $("#profile-status"), pForm = $("#form-profile");
    function pMsg(ok, text) {
      pStatus.hidden = false;
      pStatus.className = ok ? "callout callout--info" : "callout";
      pStatus.innerHTML = "<p><strong>" + (ok ? "Berhasil." : "Gagal.") + "</strong> " + esc(text) + "</p>";
    }
    function pBusy() {
      pStatus.hidden = false;
      pStatus.className = "callout callout--info";
      pStatus.innerHTML = "<p><strong>Menyimpan…</strong> Mohon tunggu.</p>";
    }
    function fillProfile(p) {
      $("#who").textContent = p.shown_name || p.name;
      pForm.elements.display_name.value = p.display_name || "";
      pForm.elements.email.value = p.email || "";
      pForm.elements.bio.value = p.bio || "";
      pForm.elements.show_in_directory.checked = p.show_in_directory !== false;
      $("#class-info").textContent = p.class_name ? "Kelas: " + p.class_name + " (diatur admin)." : "";
      $("#dir-field").hidden = p.role !== "student";
      pForm.elements.current_password.value = "";
      var img = $("#avatar");
      if (p.photo_url) { img.src = p.photo_url; img.hidden = false; $("#avatar-ph").hidden = true; }
      else { img.removeAttribute("src"); img.hidden = true; $("#avatar-ph").hidden = false; }
      $("#btn-photo-remove").hidden = !p.photo_url;
    }
    function loadProfile() {
      API.request("/api/v1/profile", { auth: true }).then(function (r) { fillProfile(r.profile); }, function () { /* profil opsional: abaikan */ });
    }
    pForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var email = pForm.elements.email.value.trim();
      if (!email) { pMsg(false, "Email wajib diisi."); return; }
      var btn = pForm.querySelector('button[type="submit"]');
      btn.disabled = true; pBusy();
      API.request("/api/v1/profile", { method: "POST", auth: true, timeout: 15000, body: {
        display_name: pForm.elements.display_name.value.trim(),
        email: email,
        bio: pForm.elements.bio.value.trim(),
        show_in_directory: pForm.elements.show_in_directory.checked,
        current_password: pForm.elements.current_password.value
      } }).then(function (r) { fillProfile(r.profile); pMsg(true, "Profil diperbarui."); })
        .catch(function (err) { pMsg(false, err.message); })
        .then(function () { btn.disabled = false; });
    });
    $("#btn-photo").addEventListener("click", function () {
      var f = $("#photo").files[0];
      if (!f) { pMsg(false, "Pilih foto terlebih dahulu."); return; }
      if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { pMsg(false, "Format foto harus JPG, PNG, atau WebP."); return; }
      if (f.size > 5 * 1024 * 1024) { pMsg(false, "Ukuran foto maksimal 5 MB."); return; }
      var fd = new FormData(); fd.append("photo", f); pBusy();
      API.request("/api/v1/profile/photo", { method: "POST", auth: true, form: fd, timeout: 30000 })
        .then(function (r) { fillProfile(r.profile); $("#photo").value = ""; pMsg(true, "Foto profil diperbarui."); })
        .catch(function (err) { pMsg(false, err.message); });
    });
    $("#btn-photo-remove").addEventListener("click", function () {
      pBusy();
      API.request("/api/v1/profile/photo/remove", { method: "POST", auth: true })
        .then(function (r) { fillProfile(r.profile); pMsg(true, "Foto profil dihapus."); })
        .catch(function (err) { pMsg(false, err.message); });
    });

    if (API.getToken()) {
      API.me().then(showLoggedIn, function () { API.clearToken(); showLoggedOut(); });
    } else {
      showLoggedOut();
    }
  }

  /* Laporan IT: wajib login agar setiap laporan punya pemilik dan bisa dilacak. */
  var REPORT_CATEGORY = { "Jaringan": "network", "Komputer": "hardware", "Wi-Fi": "network", "Perangkat lain": "other" };
  var REPORT_URGENCY = { "Rendah": "low", "Sedang": "normal", "Tinggi": "high" };

  function laporan() {
    var API = window.TKJ_API;
    var loginCard = $("#login-card"), reportCard = $("#report-card"), mine = $("#mine-panel");
    var who = $("#who"), list = $("#my-reports");

    function showLoggedOut() {
      loginCard.hidden = false; reportCard.hidden = true; mine.hidden = true; syncHeader();
    }
    function showLoggedIn(user) {
      who.textContent = user.name;
      loginCard.hidden = true; reportCard.hidden = false; mine.hidden = false; syncHeader();
      loadMine();
    }
    function loadMine() {
      list.innerHTML = "<li>Memuat…</li>";
      API.request("/api/v1/reports", { auth: true }).then(function (res) {
        var items = res.data || [];
        if (!items.length) { list.innerHTML = "<li>Belum ada laporan.</li>"; return; }
        list.innerHTML = items.map(function (r) {
          return "<li><strong>" + esc(r.reference) + "</strong> · " + esc(r.status_label) + "<br>" + esc(r.title) + "</li>";
        }).join("");
      }).catch(function (err) {
        if (err.status === 401) { API.clearToken(); showLoggedOut(); return; }
        list.innerHTML = "<li>" + esc(err.message) + "</li>";
      });
    }

    initForm($("#form-login"), {
      login: required("Username atau email"),
      password: required("Kata sandi")
    }, function (form) {
      return API.login(form.elements.login.value.trim(), form.elements.password.value).then(function (user) {
        showLoggedIn(user);
        return null;
      });
    });

    initForm($("#form-laporan"), {
      kategori: required("Kategori masalah"),
      lokasi: required("Lokasi"),
      deskripsi: lengthRule("Deskripsi", 15, 600),
      prioritas: required("Prioritas")
    }, function (form) {
      var kategori = form.elements.kategori.value, lokasi = form.elements.lokasi.value;
      return API.request("/api/v1/reports", {
        method: "POST", auth: true,
        body: {
          category: REPORT_CATEGORY[kategori],
          title: kategori + " - " + lokasi, /* backend meminta judul; dibentuk dari kategori dan lokasi */
          description: form.elements.deskripsi.value.trim(),
          location: lokasi,
          urgency: REPORT_URGENCY[form.elements.prioritas.value]
        }
      }).then(function (res) {
        loadMine();
        return { title: "Laporan terkirim.", text: "Nomor tiket Anda: " + res.data.reference + ". Simpan nomor ini untuk memantau status." };
      }, function (err) {
        if (err.status === 401) { API.clearToken(); showLoggedOut(); }
        throw err;
      });
    });

    $("#btn-logout").addEventListener("click", function () {
      API.logout().then(showLoggedOut);
    });

    /* Pulihkan sesi jika token masih berlaku. */
    if (API.getToken()) {
      API.me().then(showLoggedIn, function () { API.clearToken(); showLoggedOut(); });
    } else {
      showLoggedOut();
    }
  }

  var pages = { profil: profil, struktur: struktur, "tim-it": timIt, teknisi: teknisi, siswa: siswa, topologi: topologi, proyek: proyek, galeri: galeri, berita: berita, saran: saran, laporan: laporan, masuk: masuk };
  var run = pages[document.body.dataset.page];
  /* Hanya halaman yang isinya bisa berasal dari backend yang menunggu konten (paling lama beberapa detik).
     Halaman lain (topologi, saran, laporan, masuk) langsung berjalan,
     jadi formulir dan daftar tidak tertahan oleh backend yang lambat atau mati. */
  var WAITS_FOR_CONTENT = { profil: 1, struktur: 1, "tim-it": 1, galeri: 1, berita: 1, siswa: 1, proyek: 1 };
  if (run) (WAITS_FOR_CONTENT[document.body.dataset.page] ? (window.TKJ_READY || Promise.resolve()) : Promise.resolve()).then(run);
})();
