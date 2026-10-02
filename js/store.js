/* Datos y utilidades: estado, guardado en localStorage e imágenes de ejemplo. */
(function(){
"use strict";
var A=window.App=window.App||{};
var KEY="modelo-pagina-v1",MAXS=5;
var $=function(s,r){return (r||document).querySelector(s)};
var esc=function(s){return String(s).replace(/[&<>"]/g,function(c){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]})};
/* Imagen placeholder generada (sin recursos externos) */
function ph(label,h){return "data:image/svg+xml;utf8,"+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="hsl('+h+',45%,38%)"/><stop offset="1" stop-color="hsl('+(h+40)+',50%,24%)"/></linearGradient></defs><rect width="1600" height="600" fill="url(#g)"/><text x="800" y="320" fill="rgba(255,255,255,.7)" font-family="sans-serif" font-size="56" text-anchor="middle">'+label+'</text></svg>')}
var def=function(){return{
slides:[1,2,3].map(function(i){return{img:"",t:"Título de la diapositiva "+i,s:"Subtítulo breve que explica la propuesta."}}),
services:[1,2,3].map(function(i){return{id:"s"+i,type:"Servicio",name:"Servicio "+i,desc:"Descripción del servicio "+i+". Edítala desde el panel.",price:"",img:""}}).concat([1,2].map(function(i){return{id:"p"+i,type:"Producto",name:"Producto "+i,desc:"Descripción del producto "+i+". Edítala desde el panel.",price:"$0.00",img:""}})),
about:["",""]}};
var S;
try{S=JSON.parse(localStorage.getItem(KEY))}catch(e){S=null}
if(!S||!S.slides)S=def();
S.services.forEach(function(x){if(!x.type)x.type="Servicio"});
var warned=false;
function save(){try{localStorage.setItem(KEY,JSON.stringify(S))}catch(e){if(!warned){warned=true;alert("No hay espacio para guardar: usa imágenes más ligeras.")}}}


A.$=$;A.esc=esc;A.ph=ph;A.def=def;A.save=save;A.S=S;A.cur=0;
})();