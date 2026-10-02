/* Vista pública: valores, imágenes de Nosotros y tarjetas de servicios/productos. */
(function(A){
"use strict";
var $=A.$,esc=A.esc,ph=A.ph;
/* Valores */
var icons={Compromiso:'<path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/>',Calidad:'<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',Innovación:'<path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"/>',Confianza:'<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',"Trabajo en equipo":'<circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-3.5 2.7-6 6-6s6 2.5 6 6M15 14c3 0 6 2 6 5"/>',Responsabilidad:'<path d="M5 12l5 5L20 7"/>'};
var vtxt={Compromiso:"Cumplimos lo que prometemos.",Calidad:"Cuidamos cada detalle del trabajo.",Innovación:"Buscamos mejores formas de hacerlo.",Confianza:"Relaciones claras y transparentes.","Trabajo en equipo":"Juntos llegamos más lejos.",Responsabilidad:"Respondemos por nuestros resultados."};
$("#values").innerHTML=Object.keys(icons).map(function(k){return '<div class="card val"><svg viewBox="0 0 24 24" aria-hidden="true">'+icons[k]+'</svg><h3>'+k+'</h3><p>'+vtxt[k]+'</p></div>'}).join("");

function renderAbout(){
 $("#abTop").innerHTML='<img src="'+(A.S.about[0]||ph("Imagen superior",160))+'" alt="Imagen de la empresa" loading="lazy">';
 $("#abBottom").innerHTML='<img src="'+(A.S.about[1]||ph("Imagen inferior",260))+'" alt="Imagen del equipo" loading="lazy">';
}
var GROUPS=[["Servicio","Nuestros servicios","Servicios"],["Producto","Nuestros productos","Productos"]];
function card(s,i){return '<article class="card svc"><img src="'+(s.img||ph(esc(s.name),30+i*40))+'" alt="'+esc(s.name)+'" loading="lazy"><div><h3>'+esc(s.name)+'</h3><p>'+esc(s.desc)+'</p>'+(s.price?'<p style="margin-top:8px;color:var(--ink)"><strong>'+esc(s.price)+'</strong></p>':'')+'</div></article>'}
function renderServices(){
 var html=GROUPS.map(function(g,gi){var items=A.S.services.map(function(x,i){return[x,i]}).filter(function(p){return p[0].type===g[0]});
  return items.length?'<h2 style="margin:'+(gi?'40px':'0')+' 0 16px">'+g[1]+'</h2><div class="grid">'+items.map(function(p){return card(p[0],p[1])}).join("")+'</div>':""}).join("");
 $("#svcList").innerHTML=html||'<p style="color:var(--ink2)">Aún no hay servicios ni productos publicados.</p>';
 var sel=$("#servicio"),v=sel.value;
 sel.innerHTML='<option value="">Selecciona una opción</option>'+GROUPS.map(function(g){var items=A.S.services.filter(function(x){return x.type===g[0]});return items.length?'<optgroup label="'+g[2]+'">'+items.map(function(x){return '<option>'+esc(x.name)+'</option>'}).join("")+'</optgroup>':""}).join("");
 sel.value=v;
}
function renderAll(){A.renderSlides();renderAbout();renderServices();A.play()}


A.renderAbout=renderAbout;A.renderServices=renderServices;A.renderAll=renderAll;
})(window.App);