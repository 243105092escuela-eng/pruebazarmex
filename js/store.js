/* Datos y utilidades: estado, guardado en localStorage e imágenes de ejemplo. */
(function(){
"use strict";
var A=window.App=window.App||{};
var KEY="modelo-pagina-v1";
var $=function(s,r){return (r||document).querySelector(s)};
var esc=function(s){return String(s==null?"":s).replace(/[&<>"']/g,function(c){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]})};
/* Texto con saltos de línea -> párrafos */
var paras=function(s){return String(s||"").split(/\n+/).filter(function(x){return x.trim()}).map(function(x){return "<p>"+esc(x)+"</p>"}).join("")};
/* Imagen placeholder generada (sin recursos externos): degradado con un ícono discreto, sin texto
   para que no se encime con los títulos. El primer parámetro se conserva por compatibilidad. */
function ph(label,h){return "data:image/svg+xml;utf8,"+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="hsl('+h+',35%,40%)"/><stop offset="1" stop-color="hsl('+(h+40)+',40%,22%)"/></linearGradient></defs><rect width="1600" height="900" fill="url(#g)"/><g transform="translate(1440 760)" fill="none" stroke="rgba(255,255,255,.28)" stroke-width="6" stroke-linejoin="round"><rect x="-60" y="-45" width="120" height="90" rx="10"/><circle cx="-25" cy="-15" r="11"/><path d="M-60 35l40-35 25 20 20-15 35 30"/></g></svg>')}
var def=function(){return{
brand:{name:"[Nombre de la empresa]",logo:"",tagline:"Soluciones profesionales a la medida de tu empresa."},
contact:{phone:"+00 000 000 0000",email:"contacto@ejemplo.com",address:"Ciudad, Estado, México",hours:"Lunes a viernes, 9:00 a 18:00 h",whatsapp:"5210000000000",waMsg:"Hola, me gustaría recibir información sobre sus servicios."},
social:{facebook:"https://www.facebook.com/",instagram:"https://www.instagram.com/",tiktok:"",linkedin:"https://www.linkedin.com/",youtube:"",x:""},
features:[{icon:"reloj",t:"Respuesta rápida",d:"Atendemos tu solicitud el mismo día hábil."},{icon:"lista",t:"A tu medida",d:"Cada propuesta se adapta a tus necesidades y presupuesto."},{icon:"escudo",t:"Calidad garantizada",d:"Cuidamos cada detalle y respondemos por el resultado."},{icon:"equipo",t:"Equipo experto",d:"Personal capacitado y con experiencia en el ramo."}],
values:[{icon:"corazon",t:"Compromiso",d:"Cumplimos lo que prometemos."},{icon:"estrella",t:"Calidad",d:"Cuidamos cada detalle del trabajo."},{icon:"foco",t:"Innovación",d:"Buscamos mejores formas de hacerlo."},{icon:"escudo",t:"Confianza",d:"Relaciones claras y transparentes."},{icon:"equipo",t:"Trabajo en equipo",d:"Juntos llegamos más lejos."},{icon:"check",t:"Responsabilidad",d:"Respondemos por nuestros resultados."}],
testimonials:[1,2,3].map(function(i){return{name:"Nombre del cliente "+i,role:"Puesto, Empresa "+i,text:"Aquí va la opinión del cliente sobre el servicio que recibió. Edítala desde el panel de administración.",img:""}}),
faqs:[{q:"¿Cómo solicito una cotización?",a:"Llena el formulario de «Solicitar servicio» o escríbenos por WhatsApp. Te respondemos con una propuesta a la medida."},{q:"¿Cuánto tardan en responder?",a:"Normalmente respondemos el mismo día hábil."},{q:"¿Qué formas de pago aceptan?",a:"Escribe aquí las formas de pago que acepta la empresa."},{q:"¿Dan factura?",a:"Escribe aquí la respuesta."}],
stats:[{n:"+10",l:"años de experiencia"},{n:"+200",l:"clientes satisfechos"},{n:"24 h",l:"tiempo de respuesta"},{n:"100%",l:"cotizaciones a la medida"}],
slides:[1,2,3].map(function(i){return{img:"",t:"Título de la diapositiva "+i,s:"Subtítulo breve que explica la propuesta de valor de la empresa."}}),
services:[1,2,3].map(function(i){return{id:"s"+i,type:"Servicio",name:"Servicio "+i,desc:"Descripción del servicio "+i+". Edítala desde el panel.",price:"",img:""}}).concat([1,2].map(function(i){return{id:"p"+i,type:"Producto",name:"Producto "+i,desc:"Descripción del producto "+i+". Edítala desde el panel.",price:"$0.00",img:""}})),
about:["",""],
mission:"Ofrecer a nuestros clientes soluciones de calidad, con atención cercana y precios justos, que les ayuden a crecer.",
vision:"Ser la empresa de referencia en nuestro ramo, reconocida por la confianza y las relaciones de largo plazo con nuestros clientes.",
story:"Aquí va el texto del origen de la empresa: cómo empezó, quiénes la fundaron y qué problema querían resolver.\nUn segundo párrafo para contar la trayectoria, los logros y hacia dónde va la empresa.",
blocks:[]
}};
var S;
try{S=JSON.parse(localStorage.getItem(KEY))}catch(e){S=null}
var D=def();
if(!S||!S.slides)S=D;
/* Completa campos nuevos si había datos guardados de una versión anterior */
Object.keys(D).forEach(function(k){if(S[k]==null)S[k]=D[k]});
["brand","contact","social"].forEach(function(k){Object.keys(D[k]).forEach(function(f){if(S[k][f]==null)S[k][f]=D[k][f]})});
S.services.forEach(function(x){if(!x.type)x.type="Servicio"});
var warned=false;
/* Guarda siempre A.S (así funciona también después de "Restaurar valores de ejemplo") */
function save(){try{localStorage.setItem(KEY,JSON.stringify(A.S))}catch(e){if(!warned){warned=true;alert("No hay espacio para guardar: usa imágenes más ligeras.")}}}

A.$=$;A.esc=esc;A.paras=paras;A.ph=ph;A.def=def;A.save=save;A.S=S;A.cur=0;
})();
