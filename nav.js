/* Navegación: menú hamburguesa y vistas separadas (Home, Nosotros, Servicios). */
(function(A){
"use strict";
var $=A.$,esc=A.esc,ph=A.ph;
/* Nav */
var burger=$("#burger"),menu=$("#menu");
burger.onclick=function(){var o=menu.classList.toggle("open");burger.setAttribute("aria-expanded",o)};
menu.addEventListener("click",function(e){if(e.target.tagName==="A")menu.classList.remove("open")});
/* Vistas: cada botón abre una sección distinta */
var VIEWS=["home","nosotros","servicios"];
function route(){var h=location.hash.replace("#","");return VIEWS.indexOf(h)>-1?h:"home"}
function show(){
 var r=route();
 VIEWS.forEach(function(id){document.getElementById(id).classList.toggle("on",id===r)});
 [].forEach.call(document.querySelectorAll("nav a[data-v]"),function(a){var on=a.dataset.v===r;a.classList.toggle("on",on);on?a.setAttribute("aria-current","page"):a.removeAttribute("aria-current")});
 window.scrollTo(0,0);
 document.title=({home:"Home",nosotros:"Nosotros",servicios:"Servicios y productos"})[r]+" | [Nombre de la empresa]";
 r==="home"?A.play():A.stop();
}
window.addEventListener("hashchange",show);


A.route=route;A.show=show;
})(window.App);