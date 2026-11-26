/* Carrusel: flechas, puntos, autoplay, pausa y deslizamiento táctil. */
(function(A){
"use strict";
var $=A.$,esc=A.esc,ph=A.ph;
A.cur=0;var timer;
function renderSlides(){
 var L=A.S.slides.length;
 if(A.cur>=L)A.cur=0;
 $("#slides").innerHTML=A.S.slides.map(function(s,i){var tag=i?"h2":"h1";return '<div class="slide'+(i===A.cur?" on":"")+'" role="group" aria-label="'+(i+1)+' de '+L+'"><img src="'+esc(s.img||ph("Imagen "+(i+1),200+i*35))+'" alt="" '+(i?'loading="lazy"':'fetchpriority="high"')+'><div class="cap"><div class="wrap"><div class="cap-in"><'+tag+' class="h1">'+esc(s.t)+'</'+tag+'><p>'+esc(s.s)+'</p><div class="ctas"><a class="btn" href="#servicios">Solicitar cotización</a><a class="btn ghost" href="#svcSec">Ver servicios</a></div></div></div></div></div>'}).join("");
 $("#dots").innerHTML=A.S.slides.map(function(_,i){return '<button type="button" aria-label="Ir a la diapositiva '+(i+1)+'" data-i="'+i+'" class="'+(i===A.cur?"on":"")+'"></button>'}).join("");
 var multi=L>1;$("#prev").hidden=$("#next").hidden=$("#dots").hidden=!multi;
}
function go(n){var L=A.S.slides.length;if(!L)return;A.cur=(n+L)%L;
 [].forEach.call(document.querySelectorAll(".slide"),function(e,i){e.classList.toggle("on",i===A.cur)});
 [].forEach.call(document.querySelectorAll("#dots button"),function(e,i){e.classList.toggle("on",i===A.cur)})}
function play(){stop();if(A.S.slides.length>1&&A.route()==='home')timer=setInterval(function(){go(A.cur+1)},6000)}
function stop(){clearInterval(timer)}
$("#prev").onclick=function(){go(A.cur-1);play()};
$("#next").onclick=function(){go(A.cur+1);play()};
$("#dots").onclick=function(e){var b=e.target.closest("button");if(b){go(+b.dataset.i);play()}};
var hero=$("#hero"),x0=null;
hero.addEventListener("mouseenter",stop);hero.addEventListener("mouseleave",play);
hero.addEventListener("touchstart",function(e){x0=e.touches[0].clientX},{passive:true});
hero.addEventListener("touchend",function(e){if(x0===null)return;var d=e.changedTouches[0].clientX-x0;if(Math.abs(d)>40){go(A.cur+(d<0?1:-1));play()}x0=null},{passive:true});
document.addEventListener("visibilitychange",function(){document.hidden?stop():play()});

A.renderSlides=renderSlides;A.play=play;A.stop=stop;
})(window.App);
