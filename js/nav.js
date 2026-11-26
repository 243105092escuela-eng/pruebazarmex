/* Navegación: menú hamburguesa, vistas separadas, sombra del encabezado, modo oscuro y animaciones al hacer scroll. */
(function(A){
"use strict";
var $=A.$;
/* Menú móvil */
var burger=$("#burger"),menu=$("#menu");
function closeMenu(){menu.classList.remove("open");burger.setAttribute("aria-expanded","false")}
burger.onclick=function(){var o=menu.classList.toggle("open");burger.setAttribute("aria-expanded",o)};
menu.addEventListener("click",function(e){if(e.target.tagName==="A")closeMenu()});
document.addEventListener("click",function(e){if(!e.target.closest("nav"))closeMenu()});
/* Vistas: cada enlace abre una sección distinta. Un #ancla dentro de Inicio (ej. #svcSec) abre Inicio y baja a ella. */
var VIEWS=["home","nosotros","servicios"],NAMES={home:"Inicio",nosotros:"Nosotros",servicios:"Solicitar servicio"};
function hash(){return location.hash.replace("#","")}
function route(){var h=hash();if(VIEWS.indexOf(h)>-1)return h;var el=h&&document.getElementById(h);var v=el&&el.closest(".view");return v?v.id:"home"}
function setTitle(){document.title=NAMES[route()]+" | "+(A.S.brand.name||"")}
function show(){
 var r=route(),h=hash();
 VIEWS.forEach(function(id){document.getElementById(id).classList.toggle("on",id===r)});
 [].forEach.call(document.querySelectorAll("nav a[data-v]"),function(a){var on=a.dataset.v===r;a.classList.toggle("on",on);on?a.setAttribute("aria-current","page"):a.removeAttribute("aria-current")});
 var target=h&&VIEWS.indexOf(h)<0&&document.getElementById(h);
 if(target)target.scrollIntoView();else window.scrollTo(0,0);
 setTitle();
 r==="home"?A.play():A.stop();
}
window.addEventListener("hashchange",show);
/* Enlaces "#" vacíos (aviso de privacidad, admin) no deben cambiar de vista */
document.addEventListener("click",function(e){var a=e.target.closest('a[href="#"]');if(a)e.preventDefault()});
/* Sombra del encabezado al bajar */
var header=$("#top");
function onScroll(){header.classList.toggle("scrolled",window.scrollY>8)}
window.addEventListener("scroll",onScroll,{passive:true});onScroll();
/* Modo oscuro: respeta el sistema la primera vez y recuerda la elección del usuario */
var root=document.documentElement,TKEY="modelo-pagina-tema";
if(!root.getAttribute("data-theme"))root.setAttribute("data-theme",window.matchMedia&&matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light");
function themeLabel(){$("#themeBtn").setAttribute("aria-label",root.getAttribute("data-theme")==="dark"?"Cambiar a modo claro":"Cambiar a modo oscuro")}
$("#themeBtn").onclick=function(){var t=root.getAttribute("data-theme")==="dark"?"light":"dark";root.setAttribute("data-theme",t);try{localStorage.setItem(TKEY,t)}catch(e){}themeLabel()};
themeLabel();
/* Aparición suave al hacer scroll */
function reveal(){
 var els=document.querySelectorAll(".reveal:not(.in)");
 if(!("IntersectionObserver" in window)){[].forEach.call(els,function(e){e.classList.add("in")});return}
 var io=new IntersectionObserver(function(en){en.forEach(function(x){if(x.isIntersecting){x.target.classList.add("in");io.unobserve(x.target)}})},{threshold:.12,rootMargin:"0px 0px -40px 0px"});
 [].forEach.call(els,function(e){io.observe(e)});
}

A.route=route;A.show=show;A.setTitle=setTitle;A.reveal=reveal;
})(window.App);
