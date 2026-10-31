/* Klien API TKJ Digital Hub.
   Alamat backend diatur HANYA di sini (BASE). Token login disimpan di
   sessionStorage (hilang saat tab ditutup), tidak pernah di URL atau kode. */
(function () {
  "use strict";

  var BASE = "https://api.jarvar.web.id";
  var KEY = "tkj_token";

  function getToken() {
    try { return sessionStorage.getItem(KEY) || ""; } catch (e) { return ""; }
  }
  function setToken(t) {
    try { sessionStorage.setItem(KEY, t); } catch (e) { /* penyimpanan diblokir: sesi hanya berlaku di halaman ini */ }
  }
  function clearToken() {
    try { sessionStorage.removeItem(KEY); } catch (e) { /* abaikan */ }
  }

  /* Ubah respons error API menjadi pesan yang mudah dibaca. */
  function messageFor(status, data) {
    if (data && data.errors) {
      var first = Object.keys(data.errors)[0];
      if (first && data.errors[first] && data.errors[first][0]) return data.errors[first][0];
    }
    if (status === 429) return "Terlalu banyak percobaan. Tunggu beberapa saat lalu coba lagi.";
    if (status === 401) return "Sesi Anda berakhir. Silakan masuk lagi.";
    if (status === 403) return (data && data.message) || "Anda tidak memiliki izin untuk tindakan ini.";
    if (data && data.message) return data.message;
    return "Terjadi kesalahan pada server (kode " + status + ").";
  }

  function request(path, opts) {
    opts = opts || {};
    var headers = { Accept: "application/json" };
    if (opts.body && !opts.form) headers["Content-Type"] = "application/json";
    if (opts.auth) {
      var t = getToken();
      if (!t) return Promise.reject(Object.assign(new Error(messageFor(401)), { status: 401 }));
      headers.Authorization = "Bearer " + t;
    }
    var ctrl = (opts.timeout && typeof AbortController === "function") ? new AbortController() : null;
    var timer = ctrl ? setTimeout(function () { ctrl.abort(); }, opts.timeout) : null;
    function done() { if (timer) clearTimeout(timer); }
    return fetch(BASE + path, {
      method: opts.method || "GET",
      headers: headers,
      body: opts.form ? opts.form : (opts.body ? JSON.stringify(opts.body) : undefined),
      signal: ctrl ? ctrl.signal : undefined
    }).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (data) {
        done();
        if (!res.ok) {
          throw Object.assign(new Error(messageFor(res.status, data)), { status: res.status, data: data });
        }
        return data;
      });
    }, function () {
      done();
      throw Object.assign(new Error("Tidak dapat terhubung ke server. Periksa koneksi internet Anda lalu coba lagi."), { status: 0 });
    });
  }

  window.TKJ_API = {
    base: BASE,
    request: request,
    getToken: getToken,
    clearToken: clearToken,
    login: function (login, password) {
      return request("/api/v1/auth/login", { method: "POST", body: { login: login, password: password } })
        .then(function (d) { setToken(d.token); return d.user; });
    },
    me: function () { return request("/api/v1/me", { auth: true }).then(function (d) { return d.user; }); },
    logout: function () {
      var done = function () { clearToken(); };
      return request("/api/v1/auth/logout", { method: "POST", auth: true }).then(done, done);
    }
  };
})();
