/* Se carga en <head> SIN defer, antes de las hojas de estilo, para que la página
   aparezca desde el primer instante en el tema correcto (sin parpadeo).
   - Marca <html class="js">: las animaciones de aparición solo se activan con JavaScript.
   - Tema: el que eligió el visitante (guardado en este navegador) o, si nunca eligió,
     el del sistema operativo. El botón de luna/sol está en app.js. */
(function () {
  "use strict";
  var root = document.documentElement, t = null;
  root.className += " js";
  try { t = localStorage.getItem("zarmex-tema"); } catch (e) {}
  if (t !== "dark" && t !== "light") {
    t = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
  }
  root.setAttribute("data-theme", t);
})();
