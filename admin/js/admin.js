/* Panel de administración: un solo archivo, cada página activa su módulo según <body data-page>. */
(function () {
"use strict";

var D = document, R = D.documentElement, BASE = R.getAttribute("data-base") || "";
var $ = function (s, r) { return (r || D).querySelector(s); };
var $$ = function (s, r) { return [].slice.call((r || D).querySelectorAll(s)); };
var esc = function (s) {
  return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
    return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
  });
};
function csrf() { var m = $('meta[name="csrf-token"]'); return m ? m.content : ""; }
function debounce(fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; }

/* ---------- avisos ---------- */
function toast(msg, type) {
  var box = $("#toasts"); if (!box) return;
  var t = D.createElement("div");
  t.className = "toast" + (type === "err" ? " err" : "");
  t.textContent = msg;
  box.appendChild(t);
  setTimeout(function () { t.remove(); }, type === "err" ? 6500 : 3500);
}

/* ---------- peticiones: todo POST + CSRF, respuesta JSON ---------- */
function toFD(data) {
  var fd = new FormData();
  Object.keys(data || {}).forEach(function (k) {
    var v = data[k];
    if (Array.isArray(v)) v.forEach(function (x) { fd.append(k + "[]", x); });
    else if (v !== undefined && v !== null) fd.append(k, v);
  });
  return fd;
}
function api(data, url) {
  var fd = data instanceof FormData ? data : toFD(data);
  fd.set("csrf", csrf());
  return fetch(url || location.pathname, {
    method: "POST", body: fd, credentials: "same-origin",
    headers: { "X-Requested-With": "fetch", "Accept": "application/json", "X-CSRF-Token": csrf() }
  }).then(function (r) {
    return r.json().catch(function () { return { ok: false, error: "Respuesta no válida del servidor." }; })
      .then(function (j) {
        if (r.status === 401 && j.login) { location.href = BASE + "/admin/login.php"; }
        if (j.redirect && r.status === 403) { location.href = j.redirect; }
        if (!r.ok || !j.ok) { var e = new Error(j.error || "Ocurrió un error."); e.data = j; e.status = r.status; throw e; }
        return j;
      });
  }, function () { throw new Error("No hay conexión con el servidor."); });
}
function fail(e) { toast(e.message || "Ocurrió un error.", "err"); }
function busy(btn, on, label) {
  if (!btn) return;
  if (on) { btn.dataset.l = btn.textContent; btn.disabled = true; btn.setAttribute("aria-busy", "true"); if (label) btn.textContent = label; }
  else { btn.disabled = false; btn.removeAttribute("aria-busy"); if (btn.dataset.l) btn.textContent = btn.dataset.l; }
}

/* ---------- confirmación (reemplaza a confirm()) ---------- */
function confirmBox(msg, yes, title) {
  var d = $("#confirmDlg");
  return new Promise(function (resolve) {
    $("#confirmMsg").textContent = msg;
    $("#confirmTitle").textContent = title || "¿Confirmas?";
    $("#confirmYes").textContent = yes || "Eliminar";
    var done = function (v) { d.close(); $("#confirmYes").onclick = $("#confirmNo").onclick = null; d.onclose = null; resolve(v); };
    $("#confirmYes").onclick = function () { done(true); };
    $("#confirmNo").onclick = function () { done(false); };
    d.onclose = function () { resolve(false); };
    d.showModal();
  });
}

/* ---------- vista previa inmediata de imágenes (antes de subir) ---------- */
var OK_TYPES = ["image/jpeg", "image/png", "image/webp"], MAX_BYTES = 3 * 1024 * 1024;
function previewFile(input, img) {
  var f = input.files[0]; if (!f) return false;
  if (OK_TYPES.indexOf(f.type) < 0) { toast("Formato no permitido. Usa JPG, PNG o WebP.", "err"); input.value = ""; return false; }
  if (f.size > MAX_BYTES) { toast("La imagen supera los 3 MB.", "err"); input.value = ""; return false; }
  if (img.dataset.blob) URL.revokeObjectURL(img.dataset.blob);
  img.dataset.blob = URL.createObjectURL(f);
  img.src = img.dataset.blob;
  return true;
}
function bindPreview(input, img, onPick) {
  input.addEventListener("change", function () { if (previewFile(input, img) && onPick) onPick(input.files[0]); });
}

/* Recoge los campos [name] de una fila/formulario (checkbox -> "1"/"0", archivo solo si se eligió uno) */
function collect(scope, extra) {
  var fd = new FormData();
  $$("[name]", scope).forEach(function (el) {
    if (el.type === "file") { if (el.files[0]) fd.append(el.name, el.files[0]); }
    else if (el.type === "checkbox") fd.append(el.name, el.checked ? "1" : "0");
    else if (el.type === "radio") { if (el.checked) fd.append(el.name, el.value); }
    else fd.append(el.name, el.value);
  });
  Object.keys(extra || {}).forEach(function (k) { fd.set(k, extra[k]); });
  return fd;
}
function swapRow(row, html) {
  var t = D.createElement("div"); t.innerHTML = html;
  var n = t.firstElementChild; row.replaceWith(n); return n;
}

/* ---------- arrastrar y soltar para reordenar (con tirador ⠿) ---------- */
function sortable(box, onDrop) {
  var drag = null;
  box.addEventListener("pointerdown", function (e) {
    var h = e.target.closest(".handle"); if (!h) return;
    h.closest(".row").draggable = true;
  });
  box.addEventListener("pointerup", function () { $$(".row[draggable=true]", box).forEach(function (r) { r.draggable = false; }); });
  box.addEventListener("dragstart", function (e) {
    var r = e.target.closest(".row"); if (!r || !r.draggable) return;
    drag = r; r.classList.add("dragging");
    e.dataTransfer.effectAllowed = "move";
    try { e.dataTransfer.setData("text/plain", r.dataset.id); } catch (x) {}
  });
  box.addEventListener("dragover", function (e) {
    if (!drag) return;
    e.preventDefault();
    var r = e.target.closest(".row");
    $$(".row.over", box).forEach(function (x) { x.classList.remove("over"); });
    if (r && r !== drag) r.classList.add("over");
  });
  box.addEventListener("drop", function (e) {
    if (!drag) return;
    e.preventDefault();
    var r = e.target.closest(".row");
    if (r && r !== drag) {
      var after = drag.compareDocumentPosition(r) & Node.DOCUMENT_POSITION_FOLLOWING;
      r.parentNode.insertBefore(drag, after ? r.nextSibling : r);
      onDrop($$(".row", box).map(function (x) { return x.dataset.id; }));
    }
  });
  box.addEventListener("dragend", function () {
    $$(".row", box).forEach(function (x) { x.classList.remove("dragging", "over"); x.draggable = false; });
    drag = null;
  });
}

/* ---------- menú lateral (móvil) ---------- */
(function () {
  var b = $("#menuBtn"); if (!b) return;
  var set = function (o) { D.body.classList.toggle("menu-open", o); b.setAttribute("aria-expanded", o); };
  b.addEventListener("click", function () { set(!D.body.classList.contains("menu-open")); });
  $("#scrim").addEventListener("click", function () { set(false); });
  D.addEventListener("keydown", function (e) { if (e.key === "Escape") set(false); });
})();

function setBadge(n) {
  var b = $("#badgeNuevas"); if (!b || n == null) return;
  b.textContent = n; b.hidden = !(n > 0);
}
function markDirty(e) { var r = e.target.closest(".row"); if (r) r.classList.add("dirty"); }
function fieldErrors(scope, errors) {
  $$("[data-err]", scope).forEach(function (s) { s.textContent = ""; });
  Object.keys(errors || {}).forEach(function (k) { var s = $('[data-err="' + k + '"]', scope); if (s) s.textContent = errors[k]; });
}
function setHTML(el, html) { el.innerHTML = html; }

/* ====================== módulos ====================== */
var pages = {};
/* ---------- login ---------- */
pages.login = function () {
  var f = $("#loginForm"), msg = $("#msg");
  f.addEventListener("submit", function (e) {
    e.preventDefault();
    msg.classList.remove("show");
    var btn = $("#go"); busy(btn, true, "Entrando…");
    api(new FormData(f), BASE + "/admin/login.php").then(function (j) {
      location.href = j.redirect;
    }).catch(function (err) {
      msg.textContent = err.message; msg.classList.add("show");
      $("#password").value = ""; $("#password").focus();
      busy(btn, false);
    });
  });
};

/* ---------- cambio de contraseña ---------- */
pages.password = function () {
  var f = $("#pwForm");
  f.addEventListener("submit", function (e) {
    e.preventDefault();
    fieldErrors(f, {});
    var btn = $("button[type=submit]", f); busy(btn, true, "Guardando…");
    api(new FormData(f)).then(function (j) {
      toast(j.message); setTimeout(function () { location.href = j.redirect; }, 700);
    }).catch(function (err) {
      if (err.data && err.data.errors) fieldErrors(f, err.data.errors);
      fail(err); busy(btn, false);
    });
  });
};
/* ---------- Carrusel ---------- */
pages.carrusel = function () {
  var list = $("#list"), counter = $("#counter"), add = $("#add"), MAX = +counter.dataset.max;
  function upd(j) {
    if (j.activos != null) counter.textContent = "Visibles: " + j.activos + " de " + MAX;
    if (j.total != null) add.disabled = j.total >= j.max_total;
  }
  function all(j) { setHTML(list, j.html); upd(j); if (j.message) toast(j.message); }

  list.addEventListener("input", markDirty);
  list.addEventListener("change", function (e) {
    var t = e.target, row = t.closest(".row"); if (!row) return;
    if (t.type === "file") { if (previewFile(t, $(".thumb", row))) row.classList.add("dirty"); return; }
    if (t.dataset.act === "toggle") {
      api({ action: "activo", id: row.dataset.id, activo: t.checked ? "1" : "0" }).then(function (j) {
        row.classList.toggle("inactive", !t.checked); upd(j); toast(j.message);
      }).catch(function (err) { t.checked = !t.checked; fail(err); });
    }
  });
  list.addEventListener("click", function (e) {
    var b = e.target.closest("[data-act]"); if (!b || b.type === "checkbox") return;
    var row = b.closest(".row"), id = row.dataset.id, act = b.dataset.act;
    if (act === "pick") { $("input[type=file]", row).click(); }
    else if (act === "save") {
      busy(b, true, "Guardando…");
      api(collect(row, { action: "guardar", id: id })).then(function (j) { swapRow(row, j.row); upd(j); toast(j.message); })
        .catch(function (err) { fail(err); busy(b, false); });
    } else if (act === "up" || act === "down") {
      api({ action: "mover", id: id, dir: act }).then(all).catch(fail);
    } else if (act === "del") {
      confirmBox("Se quitará esta diapositiva y se borrará su imagen del servidor.", "Quitar", "¿Quitar diapositiva?").then(function (ok) {
        if (ok) api({ action: "eliminar", id: id }).then(all).catch(fail);
      });
    }
  });
  add.addEventListener("click", function () { busy(add, true); api({ action: "crear" }).then(all).catch(fail).then(function () { busy(add, false); upd({ total: $$(".row", list).length, max_total: +add.dataset.max }); }); });
  sortable(list, function (ids) {
    api({ action: "ordenar", ids: ids }).then(function (j) { toast(j.message); }).catch(function (err) { fail(err); location.reload(); });
  });
};
/* ---------- Servicios y productos ---------- */
pages.servicios = function () {
  var list = $("#list"), pager = $("#pager"), dlg = $("#itemDlg"), form = $("#itemForm");
  var st = { ftipo: "", fq: "", page: 1 };
  function req(extra) { return api(Object.assign({}, st, extra)); }
  function show(j, silent) {
    setHTML(list, j.html); setHTML(pager, j.pager); st.page = j.page;
    if (j.message && !silent) toast(j.message);
  }
  function reload() { return req({ action: "listar" }).then(function (j) { show(j, true); }).catch(fail); }

  $("#fTipo").addEventListener("change", function () { st.ftipo = this.value; st.page = 1; reload(); });
  $("#fQ").addEventListener("input", debounce(function () { st.fq = this.value.trim(); st.page = 1; reload(); }, 300));
  pager.addEventListener("click", function (e) {
    var b = e.target.closest("[data-page]"); if (!b || b.disabled) return;
    st.page = +b.dataset.page; reload(); window.scrollTo({ top: 0 });
  });

  /* lista */
  list.addEventListener("change", function (e) {
    var t = e.target, row = t.closest(".row"); if (!row || t.dataset.act !== "toggle") return;
    api({ action: "activo", id: row.dataset.id, activo: t.checked ? "1" : "0" }).then(function (j) {
      row.classList.toggle("inactive", !t.checked); row.dataset.activo = t.checked ? "1" : "0"; toast(j.message);
    }).catch(function (err) { t.checked = !t.checked; fail(err); });
  });
  list.addEventListener("click", function (e) {
    var b = e.target.closest("[data-act]"); if (!b || b.type === "checkbox") return;
    var row = b.closest(".row"), id = row.dataset.id, act = b.dataset.act;
    if (act === "edit") openDlg(row);
    else if (act === "up" || act === "down") req({ action: "mover", id: id, dir: act }).then(show).catch(fail);
    else if (act === "del") {
      confirmBox("Se eliminará «" + row.dataset.nombre + "» y se borrará su imagen del servidor. Las solicitudes ya recibidas conservarán el nombre.", "Eliminar", "¿Eliminar elemento?")
        .then(function (ok) { if (ok) req({ action: "eliminar", id: id }).then(show).catch(fail); });
    }
  });
  sortable(list, function (ids) {
    api({ action: "ordenar", ids: ids }).then(function (j) { toast(j.message); }).catch(function (err) { fail(err); reload(); });
  });

  /* diálogo de alta / edición */
  var prev = $("#i_prev"), file = $("#i_file"), PH = dlg.dataset.ph;
  bindPreview(file, prev, function () { $("#i_quitar").checked = false; });
  $("#i_pick").addEventListener("click", function () { file.click(); });
  $("#i_quitar").addEventListener("change", function () { if (this.checked) { prev.src = PH; file.value = ""; } });
  function openDlg(row) {
    form.reset(); fieldErrors(form, {});
    form.elements.id.value = row ? row.dataset.id : "0";
    $("#itemTitle").textContent = row ? "Editar elemento" : "Agregar elemento";
    form.elements.tipo.value = row ? row.dataset.tipo : "Servicio";
    form.elements.nombre.value = row ? row.dataset.nombre : "";
    form.elements.descripcion.value = row ? row.dataset.desc : "";
    form.elements.precio.value = row ? row.dataset.precio : "";
    form.elements.activo.checked = row ? row.dataset.activo === "1" : true;
    prev.src = row && row.dataset.img ? row.dataset.img : PH;
    dlg.showModal(); form.elements.nombre.focus();
  }
  $("#add").addEventListener("click", function () { openDlg(null); });
  $("#itemNo").addEventListener("click", function () { dlg.close(); });
  $("#itemX").addEventListener("click", function () { dlg.close(); });
  form.addEventListener("submit", function (e) {
    e.preventDefault(); fieldErrors(form, {});
    var btn = $("#itemGo"); busy(btn, true, "Guardando…");
    api(collect(form, Object.assign({ action: "guardar" }, st))).then(function (j) { dlg.close(); show(j); })
      .catch(function (err) { if (err.data && err.data.errors) fieldErrors(form, err.data.errors); fail(err); })
      .then(function () { busy(btn, false); });
  });
};
/* ---------- Nosotros: imágenes, valores e historia ---------- */
pages.nosotros = function () {
  /* imágenes */
  var imgs = $("#imgs");
  imgs.addEventListener("change", function (e) {
    var t = e.target, blk = t.closest(".img-block"); if (!blk) return;
    if (t.type === "file") { if (previewFile(t, $(".thumb", blk))) $("[name=quitar]", blk).checked = false; }
  });
  imgs.addEventListener("click", function (e) {
    var b = e.target.closest("[data-act]"); if (!b) return;
    var blk = b.closest(".img-block");
    if (b.dataset.act === "pick") $("input[type=file]", blk).click();
    else if (b.dataset.act === "save-img") {
      busy(b, true, "Guardando…");
      api(collect(blk, { action: "imagen", posicion: blk.dataset.pos })).then(function (j) {
        var t = D.createElement("div"); t.innerHTML = j.html; blk.replaceWith(t.firstElementChild); toast(j.message);
      }).catch(function (err) { fail(err); busy(b, false); });
    }
  });

  /* valores */
  var list = $("#list"), add = $("#add"), MAX = +add.dataset.max;
  function all(j) { setHTML(list, j.html); add.disabled = j.total >= MAX; if (j.message) toast(j.message); }
  list.addEventListener("input", markDirty);
  list.addEventListener("change", function (e) {
    var t = e.target, row = t.closest(".row"); if (!row) return;
    if (t.name === "icono") { $("[data-prev]", row).innerHTML = t.selectedOptions[0].dataset.svg; }
    if (t.dataset.act === "toggle") {
      api({ action: "activo", id: row.dataset.id, activo: t.checked ? "1" : "0" }).then(function (j) { row.classList.toggle("inactive", !t.checked); toast(j.message); })
        .catch(function (err) { t.checked = !t.checked; fail(err); });
    }
  });
  list.addEventListener("click", function (e) {
    var b = e.target.closest("[data-act]"); if (!b || b.type === "checkbox") return;
    var row = b.closest(".row"), id = row.dataset.id, act = b.dataset.act;
    if (act === "save") {
      busy(b, true, "Guardando…");
      api(collect(row, { action: "guardar", id: id })).then(function (j) { swapRow(row, j.row); toast(j.message); })
        .catch(function (err) { fail(err); busy(b, false); });
    } else if (act === "up" || act === "down") api({ action: "mover", id: id, dir: act }).then(all).catch(fail);
    else if (act === "del") confirmBox("Se eliminará este valor de la página Nosotros.", "Eliminar", "¿Eliminar valor?")
      .then(function (ok) { if (ok) api({ action: "eliminar", id: id }).then(all).catch(fail); });
  });
  add.addEventListener("click", function () { busy(add, true); api({ action: "crear" }).then(all).catch(fail).then(function () { busy(add, false); }); });
  sortable(list, function (ids) {
    api({ action: "ordenar", ids: ids }).then(function (j) { toast(j.message); }).catch(function (err) { fail(err); location.reload(); });
  });

  /* historia */
  $("#histForm").addEventListener("submit", function (e) {
    e.preventDefault();
    var btn = $("#histGo"); busy(btn, true, "Guardando…");
    api(collect(e.target, { action: "historia" })).then(function (j) { toast(j.message); }).catch(fail).then(function () { busy(btn, false); });
  });
};
/* ---------- Ajustes del sitio ---------- */
pages.ajustes = function () {
  var form = $("#setForm"), prev = $("#s_prev"), file = $("#s_file");
  bindPreview(file, prev, function () { $("#s_quitar").checked = false; });
  $("#s_pick").addEventListener("click", function () { file.click(); });
  $("#s_quitar").addEventListener("change", function () { if (this.checked) { prev.src = prev.dataset.ph; file.value = ""; } });
  form.addEventListener("submit", function (e) {
    e.preventDefault(); fieldErrors(form, {});
    var btn = $("#setGo"); busy(btn, true, "Guardando…");
    api(collect(form, { action: "guardar" })).then(function (j) {
      toast(j.message);
      file.value = ""; $("#s_quitar").checked = false;
      prev.src = j.logo || prev.dataset.ph;
    }).catch(function (err) { if (err.data && err.data.errors) fieldErrors(form, err.data.errors); fail(err); })
      .then(function () { busy(btn, false); });
  });
};
/* ---------- Solicitudes ---------- */
pages.solicitudes = function () {
  var rows = $("#rows"), pager = $("#pager"), dlg = $("#viewDlg"), exp = $("#exp");
  var st = { festado: "", fq: "", page: 1 };
  function req(extra) { return api(Object.assign({}, st, extra)); }
  function show(j, silent) {
    setHTML(rows, j.html); setHTML(pager, j.pager); st.page = j.page; setBadge(j.nuevas);
    if (j.message && !silent) toast(j.message);
  }
  function link() { exp.href = exp.dataset.base + "&festado=" + encodeURIComponent(st.festado) + "&fq=" + encodeURIComponent(st.fq); }
  function reload() { link(); return req({ action: "listar" }).then(function (j) { show(j, true); }).catch(fail); }

  $("#fEstado").addEventListener("change", function () { st.festado = this.value; st.page = 1; reload(); });
  $("#fQ").addEventListener("input", debounce(function () { st.fq = this.value.trim(); st.page = 1; reload(); }, 300));
  pager.addEventListener("click", function (e) {
    var b = e.target.closest("[data-page]"); if (!b || b.disabled) return;
    st.page = +b.dataset.page; reload(); window.scrollTo({ top: 0 });
  });

  rows.addEventListener("change", function (e) {
    var s = e.target, tr = s.closest("tr"); if (!tr || s.dataset.act !== "estado") return;
    api({ action: "estado", id: tr.dataset.id, estado: s.value }).then(function (j) {
      tr.classList.toggle("is-new", s.value === "nueva"); setBadge(j.nuevas); toast(j.message);
    }).catch(function (err) { fail(err); reload(); });
  });
  rows.addEventListener("click", function (e) {
    var b = e.target.closest("[data-act]"); if (!b || b.tagName !== "BUTTON") return;
    var tr = b.closest("tr"), id = tr.dataset.id;
    if (b.dataset.act === "ver") {
      api({ action: "ver", id: id }).then(function (j) {
        $("#viewTitle").textContent = j.title; setHTML($("#viewBody"), j.html);
        $("select", tr).value = j.estado; tr.classList.remove("is-new"); setBadge(j.nuevas);
        dlg.showModal();
      }).catch(fail);
    } else if (b.dataset.act === "del") {
      confirmBox("Se eliminará esta solicitud de forma permanente. Puedes exportar el CSV antes si necesitas un respaldo.", "Eliminar", "¿Eliminar solicitud?")
        .then(function (ok) { if (ok) req({ action: "eliminar", id: id }).then(show).catch(fail); });
    }
  });
  $("#viewX").addEventListener("click", function () { dlg.close(); });
  $("#viewOk").addEventListener("click", function () { dlg.close(); });
};
/* ---------- Usuarios ---------- */
pages.usuarios = function () {
  var rows = $("#rows"), pager = $("#pager"), dlg = $("#usrDlg"), form = $("#usrForm"), st = { page: 1 };
  function show(j, silent) { setHTML(rows, j.html); setHTML(pager, j.pager); st.page = j.page; if (j.message && !silent) toast(j.message); }
  function reload() { return api({ action: "listar", page: st.page }).then(function (j) { show(j, true); }).catch(fail); }
  pager.addEventListener("click", function (e) {
    var b = e.target.closest("[data-page]"); if (!b || b.disabled) return; st.page = +b.dataset.page; reload();
  });
  rows.addEventListener("change", function (e) {
    var t = e.target, tr = t.closest("tr"); if (!tr || t.dataset.act !== "toggle") return;
    api({ action: "activo", id: tr.dataset.id, activo: t.checked ? "1" : "0" }).then(function (j) { toast(j.message); })
      .catch(function (err) { t.checked = !t.checked; fail(err); });
  });
  rows.addEventListener("click", function (e) {
    var b = e.target.closest("button[data-act]"); if (!b) return;
    var tr = b.closest("tr");
    if (b.dataset.act === "edit") openDlg(tr);
    else if (b.dataset.act === "del") confirmBox("Se eliminará la cuenta de «" + tr.dataset.nombre + "». Esta acción no se puede deshacer.", "Eliminar", "¿Eliminar usuario?")
      .then(function (ok) { if (ok) api({ action: "eliminar", id: tr.dataset.id, page: st.page }).then(show).catch(fail); });
  });
  function openDlg(tr) {
    form.reset(); fieldErrors(form, {});
    var self = tr && tr.dataset.self === "1";
    form.elements.id.value = tr ? tr.dataset.id : "0";
    $("#usrTitle").textContent = tr ? "Editar usuario" : "Agregar usuario";
    form.elements.nombre.value = tr ? tr.dataset.nombre : "";
    form.elements.email.value = tr ? tr.dataset.email : "";
    form.elements.rol.value = tr ? tr.dataset.rol : "editor";
    form.elements.activo.checked = tr ? tr.dataset.activo === "1" : true;
    form.elements.rol.disabled = form.elements.activo.disabled = form.elements.password.disabled = !!self;
    $("#u_passLbl").textContent = tr ? "Nueva contraseña (opcional)" : "Contraseña";
    $("#u_hint").textContent = self ? "Para cambiar tu propia contraseña usa «Cambiar contraseña» en el menú." : (tr ? "Déjala vacía para no cambiarla. Si la cambias, deberá actualizarla al ingresar." : "Mínimo 10 caracteres, con letras y números. Deberá cambiarla en su primer ingreso.");
    dlg.showModal(); form.elements.nombre.focus();
  }
  $("#add").addEventListener("click", function () { openDlg(null); });
  $("#usrNo").addEventListener("click", function () { dlg.close(); });
  $("#usrX").addEventListener("click", function () { dlg.close(); });
  form.addEventListener("submit", function (e) {
    e.preventDefault(); fieldErrors(form, {});
    var btn = $("#usrGo"); busy(btn, true, "Guardando…");
    var fd = collect(form, { action: "guardar", page: st.page });
    ["rol", "activo", "password"].forEach(function (k) { if (form.elements[k].disabled) fd.delete(k); });
    api(fd).then(function (j) { dlg.close(); show(j); })
      .catch(function (err) { if (err.data && err.data.errors) fieldErrors(form, err.data.errors); fail(err); })
      .then(function () { busy(btn, false); });
  });
};
/* ---------- Listas genéricas (includes/listas.php): cifras, puntos, testimonios, preguntas y secciones ----------
   Cada <div class="lista" data-lista="..."> funciona por su cuenta; varias pueden convivir en la misma página. */
function initLista(box) {
  var key = box.dataset.lista, list = $(".list", box), add = $("[data-add]", box), MAX = +box.dataset.max;
  function req(data) { data.lista = key; return api(data); }
  function all(j) { setHTML(list, j.html); add.disabled = j.total >= MAX; if (j.message) toast(j.message); }
  list.addEventListener("input", markDirty);
  list.addEventListener("change", function (e) {
    var t = e.target, row = t.closest(".row"); if (!row) return;
    if (t.type === "file") { if (previewFile(t, $(".thumb", row))) { row.classList.add("dirty"); var q = $("[name=quitar_imagen]", row); if (q) q.checked = false; } return; }
    if (t.name === "icono") { $("[data-prev]", row).innerHTML = t.selectedOptions[0].dataset.svg; row.classList.add("dirty"); }
    if (t.name === "quitar_imagen" || t.tagName === "SELECT") row.classList.add("dirty");
    if (t.dataset.act === "toggle") {
      req({ action: "activo", id: row.dataset.id, activo: t.checked ? "1" : "0" }).then(function (j) { row.classList.toggle("inactive", !t.checked); toast(j.message); })
        .catch(function (err) { t.checked = !t.checked; fail(err); });
    }
  });
  list.addEventListener("click", function (e) {
    var b = e.target.closest("[data-act]"); if (!b || b.type === "checkbox") return;
    var row = b.closest(".row"), id = row.dataset.id, act = b.dataset.act;
    if (act === "pick") { $("input[type=file]", row).click(); }
    else if (act === "save") {
      busy(b, true, "Guardando…");
      var fd = collect(row, { action: "guardar", id: id, lista: key });
      api(fd).then(function (j) { swapRow(row, j.row); toast(j.message); })
        .catch(function (err) { fail(err); busy(b, false); });
    } else if (act === "up" || act === "down") req({ action: "mover", id: id, dir: act }).then(all).catch(fail);
    else if (act === "del") confirmBox("Se eliminará de forma permanente" + ($(".thumb-col", row) ? ", junto con su imagen." : "."), "Eliminar", "¿Eliminar?")
      .then(function (ok) { if (ok) req({ action: "eliminar", id: id }).then(all).catch(fail); });
  });
  add.addEventListener("click", function () { busy(add, true); req({ action: "crear" }).then(all).catch(fail).then(function () { busy(add, false); add.disabled = $$(".row", list).length >= MAX; }); });
  sortable(list, function (ids) {
    req({ action: "ordenar", ids: ids }).then(function (j) { toast(j.message); }).catch(function (err) { fail(err); location.reload(); });
  });
}
$$(".lista[data-lista]").forEach(initLista);

/* ---------- Inicio: cambiar la gráfica entre "Por semana" y "Por mes" ---------- */
$$(".seg [data-periodo]").forEach(function (b) {
  b.addEventListener("click", function () {
    var card = b.closest(".card");
    $$("[data-periodo]", card).forEach(function (x) { var on = x === b; x.classList.toggle("on", on); x.setAttribute("aria-pressed", on); });
    $$("[data-chart]", card).forEach(function (c) { c.hidden = c.dataset.chart !== b.dataset.periodo; });
  });
});

/* ---------- Formularios simples de textos (p. ej. Misión y visión) ---------- */
$$("form[data-action]").forEach(function (f) {
  f.addEventListener("submit", function (e) {
    e.preventDefault();
    var btn = $("button[type=submit]", f); busy(btn, true, "Guardando…");
    api(collect(f, { action: f.dataset.action })).then(function (j) { toast(j.message); }).catch(fail).then(function () { busy(btn, false); });
  });
});

/* ---------- arranque ---------- */
var page = D.body.getAttribute("data-page");
if (pages[page]) pages[page]();
})();
