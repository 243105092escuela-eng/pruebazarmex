/* Panel de administración: empresa, carrusel, Inicio, servicios/productos, testimonios, preguntas frecuentes, Nosotros y secciones extra. */
(function(A){
"use strict";
var $=A.$,esc=A.esc,ph=A.ph;
var MAXS=5;
var dlg=$("#admin"),tab="emp";
$("#openAdmin").onclick=function(e){e.preventDefault();dlg.showModal();if(!$("#login").hidden)$("#pin").focus()};
$("#closeAdmin").onclick=function(){dlg.close()};
$("#pinGo").onclick=function(){if($("#pin").value==="1234"){$("#login").hidden=true;$("#panel").hidden=false;$("#pin").value="";$("#pinErr").textContent="";drawTab()}else $("#pinErr").textContent="PIN incorrecto."};
$("#pin").addEventListener("keydown",function(e){if(e.key==="Enter")$("#pinGo").click()});
$("#tabs").onclick=function(e){var b=e.target.closest("button");if(!b)return;tab=b.dataset.t;[].forEach.call(this.children,function(x){x.classList.toggle("on",x===b)});drawTab()};

/* Elegir imagen: se reduce de tamaño para que quepa en el guardado del navegador.
   El logo se guarda en PNG para conservar la transparencia. */
function pick(cb,opt){opt=opt||{};var i=document.createElement("input");i.type="file";i.accept="image/*";i.onchange=function(){var f=i.files[0];if(!f)return;var r=new FileReader();r.onload=function(){var im=new Image();im.onload=function(){var w=Math.min(opt.max||1400,im.width),c=document.createElement("canvas");c.width=w;c.height=Math.round(im.height*w/im.width);c.getContext("2d").drawImage(im,0,0,c.width,c.height);cb(opt.png?c.toDataURL("image/png"):c.toDataURL("image/jpeg",.72))};im.src=r.result};r.readAsDataURL(f)};i.click()}
function commit(){A.save();A.renderAll()}

/* Ayudantes para construir campos. data-k = sección, data-i = posición en la lista, data-f = campo */
function at(k,i,f){return 'data-k="'+k+'"'+(i!=null?' data-i="'+i+'"':'')+(f?' data-f="'+f+'"':'')}
function inp(label,k,i,f,v,ph2){return '<label class="fl"><span>'+label+'</span><input '+at(k,i,f)+' value="'+esc(v)+'"'+(ph2?' placeholder="'+esc(ph2)+'"':'')+'></label>'}
function area(label,k,i,f,v,rows){return '<label class="fl"><span>'+label+'</span><textarea rows="'+(rows||3)+'" '+at(k,i,f)+'>'+esc(v)+'</textarea></label>'}
function sel(label,k,i,f,v,opts){return '<label class="fl"><span>'+label+'</span><select '+at(k,i,f)+'>'+opts.map(function(o){return '<option value="'+esc(o[0])+'"'+(o[0]===v?" selected":"")+'>'+esc(o[1])+'</option>'}).join("")+'</select></label>'}
function imgBtns(key,i,has){return '<button type="button" class="btn alt sm" data-img="'+key+'"'+(i!=null?' data-i="'+i+'"':'')+'>Cambiar imagen</button>'+(has?'<button type="button" class="btn alt sm" data-rm="'+key+'"'+(i!=null?' data-i="'+i+'"':'')+'>Quitar imagen</button>':'')}
/* Botones de subir, bajar y eliminar para cualquier lista */
function listBtns(k,i,len,delLabel){return '<button type="button" class="btn alt sm" data-mv="-1" data-l="'+k+'" data-i="'+i+'"'+(i?"":" disabled")+'>↑ Subir</button><button type="button" class="btn alt sm" data-mv="1" data-l="'+k+'" data-i="'+i+'"'+(i<len-1?"":" disabled")+'>↓ Bajar</button><button type="button" class="btn alt sm" data-del="'+k+'" data-i="'+i+'">'+(delLabel||"Eliminar")+'</button>'}
function addBtn(k,label){return '<button type="button" class="btn" data-add="'+k+'">+ '+label+'</button>'}
function iconOpts(){return Object.keys(A.ICONS).map(function(k){return[k,A.ICONS[k][0]]})}
/* Lista de tarjetas con ícono, título y descripción (Por qué elegirnos y Valores) */
function iconList(k,addLabel){var L=A.S[k];
 return (L.length?"":'<p class="hint"><b>No hay elementos: la sección se oculta.</b></p>')+L.map(function(x,i){return '<div class="row"><span class="ico" style="margin:0">'+'<svg viewBox="0 0 24 24" aria-hidden="true">'+(A.ICONS[x.icon]||A.ICONS.check)[1]+'</svg></span><div class="in"><div class="pair">'+sel("Ícono",k,i,"icon",x.icon,iconOpts())+inp("Título",k,i,"t",x.t)+'</div>'+inp("Descripción",k,i,"d",x.d)+'</div><div class="acts">'+listBtns(k,i,L.length)+'</div></div>'}).join("")+addBtn(k,addLabel)}

/* Plantillas para los botones "+ Agregar" */
var NEW={
 features:function(){return{icon:"check",t:"Nuevo punto",d:"Descripción breve."}},
 values:function(){return{icon:"estrella",t:"Nuevo valor",d:"Descripción breve."}},
 testimonials:function(){return{name:"Nombre del cliente",role:"Puesto, Empresa",text:"Escribe aquí la opinión del cliente.",img:""}},
 faqs:function(){return{q:"Nueva pregunta",a:"Escribe aquí la respuesta."}},
 stats:function(){return{n:"0",l:"Nuevo dato"}},
 slides:function(){return{img:"",t:"Nuevo título",s:"Nuevo subtítulo"}},
 svc:function(){return{id:"s"+Date.now(),type:"Servicio",name:"Nuevo servicio",desc:"Describe el servicio.",price:"",img:""}},
 prod:function(){return{id:"p"+Date.now(),type:"Producto",name:"Nuevo producto",desc:"Describe el producto.",price:"",img:""}},
 blocks:function(){return{id:"b"+Date.now(),page:"home",title:"Nueva sección",text:"Escribe aquí el contenido de la sección.",img:"",side:"right"}}
};

function drawTab(){
 var b=$("#tabBody"),h="",S=A.S;
 if(tab==="emp"){
  h='<h4>Empresa</h4>'+
   '<div class="pair">'+inp("Nombre de la empresa","brand",null,"name",S.brand.name)+inp("Frase o eslogan","brand",null,"tagline",S.brand.tagline)+'</div>'+
   '<div class="row"><img class="contain" src="'+esc(S.brand.logo||ph("",210))+'" alt=""><div class="in"><strong>Logo</strong><p class="hint" style="margin:0">Se recomienda PNG con fondo transparente, horizontal. También se usa como ícono de la pestaña del navegador. Si no hay logo se muestra la inicial y el nombre.</p></div><div class="acts">'+(S.brand.logo?'<button type="button" class="btn alt sm" data-img="logo">Cambiar logo</button><button type="button" class="btn alt sm" data-rm="logo">Quitar logo</button>':'<button type="button" class="btn sm" data-img="logo">Subir logo</button>')+'</div></div>'+
   '<h4>Contacto</h4>'+
   '<div class="pair">'+inp("Teléfono","contact",null,"phone",S.contact.phone)+inp("Correo","contact",null,"email",S.contact.email)+inp("Dirección","contact",null,"address",S.contact.address)+inp("Horario","contact",null,"hours",S.contact.hours)+'</div>'+
   '<h4>WhatsApp</h4><p class="hint">Solo números, con código de país. México: 52 + 1 + 10 dígitos (ej. 5215512345678). Déjalo vacío para ocultar el botón.</p>'+
   '<div class="pair">'+inp("Número de WhatsApp","contact",null,"whatsapp",S.contact.whatsapp,"5215512345678")+inp("Mensaje inicial","contact",null,"waMsg",S.contact.waMsg)+'</div>'+
   '<h4>Redes sociales</h4><p class="hint">Pega el enlace completo de cada perfil (que empiece con https://). Deja vacías las que no usen.</p>'+
   '<div class="pair">'+Object.keys(A.SOCIAL).map(function(k){return inp(A.SOCIAL[k][0],"social",null,k,S.social[k],"https://")}).join("")+'</div>';
 }else if(tab==="car"){
  h='<p class="hint">Máximo '+MAXS+' imágenes. Se recomienda formato horizontal (1600×900).</p>'+S.slides.map(function(s,i){return '<div class="row"><img src="'+esc(s.img||ph("",200+i*35))+'" alt=""><div class="in">'+inp("Título","slides",i,"t",s.t)+inp("Subtítulo","slides",i,"s",s.s)+'</div><div class="acts">'+imgBtns("slides",i,false)+listBtns("slides",i,S.slides.length,"Quitar")+'</div></div>'}).join("")+(S.slides.length<MAXS?addBtn("slides","Agregar imagen"):'<p class="hint">Llegaste al máximo de '+MAXS+'.</p>');
 }else if(tab==="ini"){
  h='<h4>Cifras</h4><p class="hint">Aparecen debajo del carrusel. Deja ambos campos vacíos para ocultar una.</p>'+
   S.stats.map(function(s,i){return '<div class="pair">'+inp("Número","stats",i,"n",s.n)+inp("Texto","stats",i,"l",s.l)+'</div>'}).join("")+
   '<h4>¿Por qué elegirnos?</h4>'+iconList("features","Agregar punto");
 }else if(tab==="svc"){
  h='<p class="hint">Cada elemento aparece en Inicio (agrupado como servicio o producto) y en el menú desplegable del formulario.</p>'+S.services.map(function(s,i){return '<div class="row"><img src="'+esc(s.img||ph("",30+i*40))+'" alt=""><div class="in"><select '+at("services",i,"type")+' aria-label="Tipo"><option'+(s.type==="Servicio"?" selected":"")+'>Servicio</option><option'+(s.type==="Producto"?" selected":"")+'>Producto</option></select><input '+at("services",i,"name")+' value="'+esc(s.name)+'" aria-label="Nombre"><textarea rows="2" '+at("services",i,"desc")+' aria-label="Descripción">'+esc(s.desc)+'</textarea><input '+at("services",i,"price")+' value="'+esc(s.price||"")+'" placeholder="Precio (opcional)" aria-label="Precio"></div><div class="acts">'+imgBtns("services",i,false)+listBtns("services",i,S.services.length)+'</div></div>'}).join("")+addBtn("svc","Agregar servicio")+' '+addBtn("prod","Agregar producto");
 }else if(tab==="tst"){
  h='<p class="hint">Opiniones de clientes. La foto es opcional: si no hay, se muestran sus iniciales. Si borras todos, la sección se oculta.</p>'+
   S.testimonials.map(function(t,i){return '<div class="row"><img src="'+esc(t.img||ph("",200+i*40))+'" alt=""><div class="in"><div class="pair">'+inp("Nombre","testimonials",i,"name",t.name)+inp("Puesto / empresa","testimonials",i,"role",t.role)+'</div>'+area("Opinión","testimonials",i,"text",t.text,3)+'</div><div class="acts">'+imgBtns("testimonials",i,!!t.img)+listBtns("testimonials",i,S.testimonials.length)+'</div></div>'}).join("")+
   addBtn("testimonials","Agregar testimonio");
 }else if(tab==="faq"){
  h='<p class="hint">Preguntas que se abren al dar clic, en Inicio. Si borras todas, la sección se oculta. Para separar párrafos en la respuesta, usa Enter.</p>'+
   S.faqs.map(function(f,i){return '<div class="row" style="grid-template-columns:1fr auto"><div class="in">'+inp("Pregunta","faqs",i,"q",f.q)+area("Respuesta","faqs",i,"a",f.a,3)+'</div><div class="acts">'+listBtns("faqs",i,S.faqs.length)+'</div></div>'}).join("")+
   addBtn("faqs","Agregar pregunta");
 }else if(tab==="abt"){
  h='<h4>Imágenes</h4>'+["Portada (arriba, ancha)","Historia (junto al texto)"].map(function(n,i){return '<div class="row"><img src="'+esc(S.about[i]||ph("",160+i*100))+'" alt=""><div class="in"><strong>'+n+'</strong></div><div class="acts">'+imgBtns("about",i,!!S.about[i])+'</div></div>'}).join("")+
   '<h4>Textos</h4><p class="hint">Para separar párrafos, usa Enter.</p>'+
   area("Misión","mission",null,null,S.mission)+area("Visión","vision",null,null,S.vision)+area("Nuestra historia","story",null,null,S.story,5)+
   '<h4>Valores</h4>'+iconList("values","Agregar valor");
 }else{
  h='<p class="hint">Crea secciones libres con título, texto e imagen (opcional) en Inicio o en Nosotros. Aparecen en el orden de esta lista.</p>'+
   (S.blocks.length?"":'<p class="hint"><b>Todavía no hay secciones extra.</b></p>')+
   S.blocks.map(function(bk,i){return '<div class="row"><img src="'+esc(bk.img||ph("",120+i*50))+'" alt=""><div class="in">'+
    '<div class="pair">'+sel("Página","blocks",i,"page",bk.page,[["home","Inicio"],["nosotros","Nosotros"]])+sel("Lado de la imagen","blocks",i,"side",bk.side==="left"?"left":"right",[["right","Derecha"],["left","Izquierda"]])+'</div>'+
    inp("Título","blocks",i,"title",bk.title)+area("Texto","blocks",i,"text",bk.text,4)+
    '</div><div class="acts">'+imgBtns("blocks",i,!!bk.img)+listBtns("blocks",i,S.blocks.length)+'</div></div>'}).join("")+
   addBtn("blocks","Agregar sección");
 }
 h+='<hr style="border:0;border-top:1px solid var(--line);margin:22px 0"><button type="button" class="btn alt sm" id="reset">Restaurar valores de ejemplo</button>';
 b.innerHTML=h;
}
/* Escribir en un campo actualiza la página al momento */
function setVal(t){var k=t.dataset.k,i=t.dataset.i,f=t.dataset.f;
 if(i!==undefined)A.S[k][+i][f]=t.value;else if(f)A.S[k][f]=t.value;else A.S[k]=t.value}
$("#tabBody").addEventListener("input",function(e){var t=e.target;if(!t.dataset.k)return;setVal(t);commit();if(t.dataset.f==="icon")drawTab()});
/* Imágenes: dónde se guarda cada una */
function setImg(k,i,u){if(k==="about")A.S.about[i]=u;else if(k==="logo")A.S.brand.logo=u;else A.S[k][i].img=u}
var IMGOPT={logo:{max:600,png:true},testimonials:{max:300}};
$("#tabBody").addEventListener("click",function(e){
 var t=e.target.closest("button");if(!t||t.disabled)return;var i=+t.dataset.i,d=t.dataset;
 if(d.img){var k=d.img;pick(function(u){setImg(k,i,u);commit();drawTab()},IMGOPT[k])}
 else if(d.rm){setImg(d.rm,i,"");commit();drawTab()}
 else if(d.mv){var L=A.S[d.l],j=i+(+d.mv);if(j<0||j>=L.length)return;var tmp=L[i];L[i]=L[j];L[j]=tmp;if(d.l==="slides")A.cur=0;commit();drawTab()}
 else if(d.del){var kd=d.del;if(kd==="slides"&&A.S.slides.length<2){alert("Deja al menos una imagen en el carrusel.");return}if(!confirm("¿Eliminar este elemento?"))return;A.S[kd].splice(i,1);A.cur=0;commit();drawTab()}
 else if(d.add){var key=d.add==="svc"||d.add==="prod"?"services":d.add;A.S[key].push(NEW[d.add]());commit();drawTab()}
 else if(t.id==="reset"){if(confirm("¿Restaurar todo el contenido de ejemplo? Se perderán los cambios.")){A.S=A.def();A.cur=0;commit();drawTab()}}
});
})(window.App);
