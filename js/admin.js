/* Panel de administración: carrusel, servicios/productos e imágenes. */
(function(A){
"use strict";
var $=A.$,esc=A.esc,ph=A.ph;
var MAXS=5;
/* Admin */
var dlg=$("#admin"),tab="car";
$("#openAdmin").onclick=function(e){e.preventDefault();dlg.showModal();$("#pin").focus()};
$("#closeAdmin").onclick=function(){dlg.close()};
$("#pinGo").onclick=function(){if($("#pin").value==="1234"){$("#login").hidden=true;$("#panel").hidden=false;$("#pin").value="";$("#pinErr").textContent="";drawTab()}else $("#pinErr").textContent="PIN incorrecto."};
$("#pin").addEventListener("keydown",function(e){if(e.key==="Enter")$("#pinGo").click()});
$("#tabs").onclick=function(e){var b=e.target.closest("button");if(!b)return;tab=b.dataset.t;[].forEach.call(this.children,function(x){x.classList.toggle("on",x===b)});drawTab()};

function pick(cb){var i=document.createElement("input");i.type="file";i.accept="image/*";i.onchange=function(){var f=i.files[0];if(!f)return;var r=new FileReader();r.onload=function(){var im=new Image();im.onload=function(){var w=Math.min(1400,im.width),c=document.createElement("canvas");c.width=w;c.height=im.height*w/im.width;c.getContext("2d").drawImage(im,0,0,c.width,c.height);cb(c.toDataURL("image/jpeg",.72))};im.src=r.result};r.readAsDataURL(f)};i.click()}
function commit(){A.save();A.renderAll()}

function drawTab(){
 var b=$("#tabBody"),h="";
 if(tab==="car"){
  h='<p class="hint">Máximo '+MAXS+' imágenes. Se recomienda formato horizontal (1600×600).</p>'+A.S.slides.map(function(s,i){return '<div class="row"><img src="'+(s.img||ph("Imagen "+(i+1),200+i*35))+'" alt=""><div class="in"><input data-k="slides" data-i="'+i+'" data-f="t" value="'+esc(s.t)+'" aria-label="Título"><input data-k="slides" data-i="'+i+'" data-f="s" value="'+esc(s.s)+'" aria-label="Subtítulo"><button class="btn alt sm" data-img="slides" data-i="'+i+'">Cambiar imagen</button></div><button class="btn alt sm" data-del="slides" data-i="'+i+'">Quitar</button></div>'}).join("")+(A.S.slides.length<MAXS?'<button class="btn" id="addSlide">+ Agregar imagen</button>':'<p class="hint">Llegaste al máximo de '+MAXS+'.</p>');
 }else if(tab==="svc"){
  h='<p class="hint">Cada elemento aparece en Home (agrupado como servicio o producto) y en el menú desplegable del formulario.</p>'+A.S.services.map(function(s,i){return '<div class="row"><img src="'+(s.img||ph(esc(s.name),30+i*40))+'" alt=""><div class="in"><select data-k="services" data-i="'+i+'" data-f="type" aria-label="Tipo"><option'+(s.type==="Servicio"?" selected":"")+'>Servicio</option><option'+(s.type==="Producto"?" selected":"")+'>Producto</option></select><input data-k="services" data-i="'+i+'" data-f="name" value="'+esc(s.name)+'" aria-label="Nombre"><textarea rows="2" data-k="services" data-i="'+i+'" data-f="desc" aria-label="Descripción">'+esc(s.desc)+'</textarea><input data-k="services" data-i="'+i+'" data-f="price" value="'+esc(s.price||"")+'" placeholder="Precio (opcional)" aria-label="Precio"><button class="btn alt sm" data-img="services" data-i="'+i+'">Cambiar imagen</button></div><button class="btn alt sm" data-del="services" data-i="'+i+'">Eliminar</button></div>'}).join("")+'<button class="btn" id="addSvc">+ Agregar servicio</button> <button class="btn" id="addProd">+ Agregar producto</button>';
 }else{
  h='<p class="hint">Las dos imágenes estáticas de Nosotros usan el mismo alto que el carrusel.</p>'+["superior","inferior"].map(function(n,i){return '<div class="row"><img src="'+(A.S.about[i]||ph("Imagen "+n,160+i*100))+'" alt=""><div class="in"><strong>Imagen '+n+'</strong><button class="btn alt sm" data-img="about" data-i="'+i+'">Cambiar imagen</button></div><span></span></div>'}).join("");
 }
 h+='<hr style="border:0;border-top:1px solid var(--line);margin:20px 0"><button class="btn alt sm" id="reset">Restaurar valores de ejemplo</button>';
 b.innerHTML=h;
}
$("#tabBody").addEventListener("input",function(e){var t=e.target,k=t.dataset.k;if(!k)return;A.S[k][+t.dataset.i][t.dataset.f]=t.value;A.save();A.renderSlides();A.renderServices();A.play()});
$("#tabBody").addEventListener("click",function(e){
 var t=e.target.closest("button");if(!t)return;var i=+t.dataset.i;
 if(t.dataset.img){pick(function(u){var k=t.dataset.img;if(k==="about")A.S.about[i]=u;else A.S[k][i].img=u;commit();drawTab()})}
 else if(t.dataset.del){var k=t.dataset.del;if(k==="slides"&&A.S.slides.length<2){alert("Deja al menos una imagen en el carrusel.");return}A.S[k].splice(i,1);A.cur=0;commit();drawTab()}
 else if(t.id==="addSlide"){A.S.slides.push({img:"",t:"Nuevo título",s:"Nuevo subtítulo"});commit();drawTab()}
 else if(t.id==="addSvc"){A.S.services.push({id:"s"+Date.now(),type:"Servicio",name:"Nuevo servicio",desc:"Describe el servicio.",price:"",img:""});commit();drawTab()}
 else if(t.id==="addProd"){A.S.services.push({id:"p"+Date.now(),type:"Producto",name:"Nuevo producto",desc:"Describe el producto.",price:"",img:""});commit();drawTab()}
 else if(t.id==="reset"){if(confirm("¿Restaurar todo el contenido de ejemplo?")){A.S=A.def();A.cur=0;commit();drawTab()}}
});


})(window.App);