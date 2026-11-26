/* Sitio público: navegación, tema claro/oscuro, carrusel, tarjetas y formulario (un solo archivo).
   El primer slide, los textos, los valores y las imágenes de Nosotros ya vienen del servidor;
   aquí se cargan el resto de slides, los servicios/productos y las opciones del formulario
   con una sola petición a api/content.php. */
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
function u(p) { return BASE + "/" + String(p).replace(/^\/+/, ""); }

/* Imagen de marcador generada (sin recursos externos ni texto), igual que placeholder_plain_uri() del servidor */
function ph(h) {
  return "data:image/svg+xml;utf8," + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="hsl(' + h + ',35%,40%)"/><stop offset="1" stop-color="hsl(' + (h + 40) + ',40%,22%)"/></linearGradient></defs><rect width="1600" height="600" fill="url(#g)"/><g transform="translate(1300 460)" fill="none" stroke="rgba(255,255,255,.28)" stroke-width="6" stroke-linejoin="round"><rect x="-60" y="-45" width="120" height="90" rx="10"/><circle cx="-25" cy="-15" r="11"/><path d="M-60 35l40-35 25 20 20-15 35 30"/></g></svg>'
  ).replace(/'/g, "%27");
}
function pic(img, webp, alt, w, h, o) {
  o = o || {};
  var at = ' width="' + w + '" height="' + h + '"' + (o.priority ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"');
  if (!img) return '<img src="' + esc(ph(o.hue || 200)) + '" alt="' + esc(alt) + '"' + at + ">";
  return "<picture>" + (webp ? '<source type="image/webp" srcset="' + esc(u(webp)) + '">' : "") +
    '<img src="' + esc(u(img)) + '" alt="' + esc(alt) + '"' + at + "></picture>";
}

var S = { slides: [], items: [], cur: 0 };
var timer = null;

/* ---------------- Navegación: menú hamburguesa y vistas por hash ---------------- */
var burger = $("#burger"), menu = $("#menu");
function closeMenu() { menu.classList.remove("open"); burger.setAttribute("aria-expanded", "false"); }
burger.addEventListener("click", function () {
  var o = menu.classList.toggle("open");
  burger.setAttribute("aria-expanded", o);
});
menu.addEventListener("click", function (e) { if (e.target.tagName === "A") closeMenu(); });
D.addEventListener("click", function (e) { if (!e.target.closest("nav")) closeMenu(); });

/* Cada enlace abre una vista distinta. Un #ancla dentro de una vista (ej. #svcSec)
   abre esa vista y baja hasta el ancla. */
var VIEWS = ["home", "nosotros", "servicios"];
var TITLES = { home: "Inicio", nosotros: "Nosotros", servicios: "Solicitar servicio" };
var EMPRESA = ($('meta[name="application-name"]') || {}).content || "";
function hash() { try { return decodeURIComponent(location.hash.slice(1)); } catch (e) { return ""; } }
function route() {
  var h = hash();
  if (VIEWS.indexOf(h) > -1) return h;
  var el = h && D.getElementById(h), v = el && el.closest(".view");
  return v ? v.id : "home";
}
function show() {
  var r = route(), h = hash();
  VIEWS.forEach(function (id) { D.getElementById(id).classList.toggle("on", id === r); });
  $$("nav a[data-v]").forEach(function (a) {
    var on = a.dataset.v === r;
    a.classList.toggle("on", on);
    on ? a.setAttribute("aria-current", "page") : a.removeAttribute("aria-current");
  });
  var target = h && VIEWS.indexOf(h) < 0 && D.getElementById(h);
  if (target) target.scrollIntoView(); else window.scrollTo(0, 0);
  D.title = TITLES[r] + (EMPRESA ? " | " + EMPRESA : "");
  r === "home" ? play() : stop();
  if (r === "servicios") getToken().catch(function () {}); // prepara el token CSRF del formulario
}
window.addEventListener("hashchange", show);

/* Sombra del encabezado al bajar */
var header = $("#top");
function onScroll() { header.classList.toggle("scrolled", window.scrollY > 8); }
window.addEventListener("scroll", onScroll, { passive: true });
onScroll();

/* ---------------- Modo oscuro ----------------
   El tema inicial lo pone js/tema-inicial.js. Aquí: el botón luna/sol guarda la elección;
   mientras el visitante no elija, se sigue el tema del sistema si cambia. */
var TKEY = "zarmex-tema", themeBtn = $("#themeBtn");
function theme() { return R.getAttribute("data-theme") === "dark" ? "dark" : "light"; }
function themeLabel() { themeBtn.setAttribute("aria-label", theme() === "dark" ? "Cambiar a modo claro" : "Cambiar a modo oscuro"); }
function savedTheme() { try { return localStorage.getItem(TKEY); } catch (e) { return null; } }
themeBtn.addEventListener("click", function () {
  var t = theme() === "dark" ? "light" : "dark";
  R.setAttribute("data-theme", t);
  try { localStorage.setItem(TKEY, t); } catch (e) {}
  themeLabel();
});
if (window.matchMedia) {
  var mq = window.matchMedia("(prefers-color-scheme: dark)");
  var onSys = function (e) { if (!savedTheme()) { R.setAttribute("data-theme", e.matches ? "dark" : "light"); themeLabel(); } };
  if (mq.addEventListener) mq.addEventListener("change", onSys); else if (mq.addListener) mq.addListener(onSys);
}
themeLabel();

/* ---------------- Aparición suave al hacer scroll ---------------- */
var io = "IntersectionObserver" in window ? new IntersectionObserver(function (en) {
  en.forEach(function (x) { if (x.isIntersecting) { x.target.classList.add("in"); io.unobserve(x.target); } });
}, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }) : null;
$$(".reveal").forEach(function (el) { if (io) io.observe(el); else el.classList.add("in"); });

/* ---------------- Carrusel ---------------- */
var hero = $("#hero");
/* Igual que slide_html() y slide_ctas() de includes/render.php */
function slideHTML(s, i, n) {
  var tag = i === 0 ? "h1" : "h2"; // un solo h1 por página
  var main = s.enlace
    ? '<a class="btn" href="' + esc(s.enlace) + '">Contáctanos</a>'
    : '<a class="btn" href="#servicios">Solicitar cotización</a>';
  return '<div class="slide" role="group" aria-label="' + (i + 1) + " de " + n + '" data-id="' + esc(s.id) + '">' +
    pic(s.imagen, s.webp, "", 1600, 600, { priority: i === 0, hue: 200 + i * 35 }) +
    '<div class="cap"><div class="wrap"><div class="cap-in"><' + tag + ' class="h1">' + esc(s.titulo) + "</" + tag + "><p>" + esc(s.subtitulo) + "</p>" +
    '<div class="ctas">' + main + '<a class="btn ghost" href="#svcSec">Ver servicios</a></div></div></div></div></div>';
}
function renderSlides() {
  if (!hero) return;
  var box = $("#slides"), L = S.slides.length, first = box.firstElementChild;
  if (!L) { hero.hidden = true; stop(); return; }
  hero.hidden = false;
  if (first && String(S.slides[0].id) === first.getAttribute("data-id")) {
    // El primer slide ya está pintado por el servidor: solo se añaden los demás (sin parpadeo)
    while (box.children.length > 1) box.removeChild(box.lastChild);
    box.insertAdjacentHTML("beforeend", S.slides.slice(1).map(function (s, i) { return slideHTML(s, i + 1, L); }).join(""));
    $$(".slide", box).forEach(function (el, i) { el.setAttribute("aria-label", (i + 1) + " de " + L); });
  } else {
    box.innerHTML = S.slides.map(function (s, i) { return slideHTML(s, i, L); }).join("");
    S.cur = 0;
  }
  if (S.cur >= L) S.cur = 0;
  go(S.cur);
  $("#dots").innerHTML = S.slides.map(function (_, i) {
    return '<button type="button" aria-label="Ir a la diapositiva ' + (i + 1) + '" data-i="' + i + '"></button>';
  }).join("");
  go(S.cur);
  $("#prev").hidden = $("#next").hidden = $("#dots").hidden = L < 2;
  play();
}
function go(n) {
  var L = S.slides.length || $$(".slide").length;
  if (!L) return;
  S.cur = (n + L) % L;
  $$(".slide").forEach(function (e, i) { e.classList.toggle("on", i === S.cur); });
  $$("#dots button").forEach(function (e, i) { e.classList.toggle("on", i === S.cur); });
}
function play() {
  stop();
  var L = S.slides.length || $$(".slide").length;
  if (L > 1 && route() === "home") timer = setInterval(function () { go(S.cur + 1); }, 6000);
}
function stop() { clearInterval(timer); }
if (hero) {
  var x0 = null;
  $("#prev").addEventListener("click", function () { go(S.cur - 1); play(); });
  $("#next").addEventListener("click", function () { go(S.cur + 1); play(); });
  $("#dots").addEventListener("click", function (e) { var b = e.target.closest("button"); if (b) { go(+b.dataset.i); play(); } });
  hero.addEventListener("mouseenter", stop);
  hero.addEventListener("mouseleave", play);
  hero.addEventListener("touchstart", function (e) { x0 = e.touches[0].clientX; }, { passive: true });
  hero.addEventListener("touchend", function (e) {
    if (x0 === null) return;
    var d = e.changedTouches[0].clientX - x0;
    if (Math.abs(d) > 40) { go(S.cur + (d < 0 ? 1 : -1)); play(); }
    x0 = null;
  }, { passive: true });
  D.addEventListener("visibilitychange", function () { D.hidden ? stop() : play(); });
}

/* ---------------- Servicios y productos (tarjetas + select del formulario) ---------------- */
var GROUPS = [["Servicio", "Nuestros servicios", "Servicios"], ["Producto", "Nuestros productos", "Productos"]];
function card(s, i) {
  return '<article class="card svc"><div class="im">' + pic(s.imagen, s.webp, s.nombre, 800, 500, { hue: 30 + i * 40 }) +
    '<span class="badge' + (s.tipo === "Producto" ? " prod" : "") + '">' + esc(s.tipo) + "</span></div>" +
    '<div class="bd"><h3>' + esc(s.nombre) + "</h3><p>" + esc(s.descripcion) + "</p>" +
    '<div class="ft"><span class="price">' + esc(s.precio || "") + "</span>" +
    '<a class="btn" href="#servicios" data-svc="' + esc(s.id) + '">Solicitar</a></div></div></article>';
}
function renderItems() {
  var html = GROUPS.map(function (g) {
    var items = S.items.map(function (x, i) { return [x, i]; }).filter(function (p) { return p[0].tipo === g[0]; });
    return items.length
      ? '<h3 class="gtitle">' + g[1] + '</h3><div class="grid gauto">' + items.map(function (p) { return card(p[0], p[1]); }).join("") + "</div>"
      : "";
  }).join("");
  $("#svcList").innerHTML = html || '<p class="empty">Aún no hay servicios ni productos publicados.</p>';

  var sel = $("#servicio"), v = sel.value;
  sel.innerHTML = '<option value="">Selecciona una opción</option>' + GROUPS.map(function (g) {
    var items = S.items.filter(function (x) { return x.tipo === g[0]; });
    return items.length
      ? '<optgroup label="' + g[2] + '">' + items.map(function (x) { return '<option value="' + esc(x.id) + '">' + esc(x.nombre) + "</option>"; }).join("") + "</optgroup>"
      : "";
  }).join("");
  sel.value = v;
}

function loadContent() {
  return fetch(u("api/content.php"), { headers: { Accept: "application/json" }, credentials: "same-origin" })
    .then(function (r) { if (!r.ok) throw new Error("http " + r.status); return r.json(); })
    .then(function (c) { S.slides = c.slides || []; S.items = c.items || []; renderSlides(); renderItems(); })
    .catch(function () {
      $("#svcList").innerHTML = '<p class="empty">No se pudo cargar el contenido. <button type="button" class="btn alt" id="retry">Reintentar</button></p>';
      $("#servicio").innerHTML = '<option value="">No disponible</option>';
    });
}
$("#svcList").addEventListener("click", function (e) {
  if (e.target.id === "retry") { $("#svcList").innerHTML = '<p class="empty">Cargando…</p>'; loadContent(); }
});
/* Botón "Solicitar" de cada tarjeta: deja elegido ese servicio en el formulario */
D.addEventListener("click", function (e) {
  var a = e.target.closest("a[data-svc]");
  if (!a) return;
  var sel = $("#servicio");
  sel.value = a.getAttribute("data-svc");
  setErr(sel, "");
});

/* ---------------- Formulario: validación en cliente + envío por fetch ---------------- */
var form = $("#form"), csrf = null;
function getToken(force) {
  if (csrf && !force) return Promise.resolve(csrf);
  return fetch(u("api/solicitud.php"), { credentials: "same-origin", headers: { Accept: "application/json" } })
    .then(function (r) { return r.json(); })
    .then(function (j) { if (!j.csrf) throw new Error("sin token"); return (csrf = j.csrf); });
}
function setErr(el, m) { el.closest(".f").querySelector(".err").textContent = m || ""; return !m; }
function banner(msg, ko) {
  var ok = $("#ok");
  ok.textContent = msg;
  ok.classList.toggle("ko", !!ko);
  ok.classList.add("show");
  ok.scrollIntoView({ block: "center" });
}
function send(fd, retry) {
  fd.set("csrf", csrf);
  return fetch(u("api/solicitud.php"), { method: "POST", body: fd, credentials: "same-origin", headers: { Accept: "application/json" } })
    .then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (r.status === 419 && !retry) { return getToken(true).then(function () { return send(fd, true); }); }
        return { status: r.status, body: j };
      });
    });
}
form.addEventListener("submit", function (e) {
  e.preventDefault();
  var g = function (id) { return $("#" + id); }, ok = true;
  $("#ok").classList.remove("show");
  ok = setErr(g("nombre"), g("nombre").value.trim().length < 3 ? "Escribe tu nombre completo." : "") && ok;
  ok = setErr(g("email"), /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(g("email").value) ? "" : "Escribe un correo válido.") && ok;
  ok = setErr(g("tel"), /^[+\d][\d\s()-]{6,}$/.test(g("tel").value.trim()) ? "" : "Escribe un teléfono con al menos 7 dígitos.") && ok;
  ok = setErr(g("servicio"), g("servicio").value ? "" : "Elige un servicio.") && ok;
  ok = setErr(g("cant"), !g("cant").value || +g("cant").value >= 1 ? "" : "Escribe una cantidad válida (1 o más).") && ok;
  ok = setErr(g("desc"), g("desc").value.trim().length < 10 ? "Cuéntanos un poco más (mínimo 10 caracteres)." : "") && ok;
  ok = setErr(g("terms"), g("terms").checked ? "" : "Debes aceptar los términos.") && ok;
  if (!ok) return;

  var btn = $("#send");
  btn.disabled = true; btn.textContent = "Enviando…";
  var fd = new FormData(form);
  getToken().then(function () { return send(fd, false); }).then(function (res) {
    var j = res.body;
    if (res.status === 200 && j.ok) {
      form.reset();
      banner(j.message || "¡Solicitud enviada! Te contactaremos pronto.", false);
    } else if (res.status === 422 && j.errors) {
      Object.keys(j.errors).forEach(function (id) { var el = g(id); if (el) setErr(el, j.errors[id]); });
      banner(j.error || "Revisa los campos marcados.", true);
    } else {
      banner(j.error || "No pudimos enviar tu solicitud. Inténtalo de nuevo.", true);
    }
  }).catch(function () {
    banner("No hay conexión con el servidor. Inténtalo de nuevo.", true);
  }).then(function () {
    btn.disabled = false; btn.textContent = "Enviar solicitud";
  });
});

/* ---------------- Arranque ---------------- */
show();
loadContent();
})();
