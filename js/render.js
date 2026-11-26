/* Vista pública: marca, contacto, cifras, Nosotros, servicios/productos y secciones extra. */
(function(A){
"use strict";
var $=A.$,esc=A.esc,ph=A.ph,paras=A.paras;
function svg(p){return '<svg viewBox="0 0 24 24" aria-hidden="true">'+p+'</svg>'}
var I={
 phone:'<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
 mail:'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
 pin:'<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
 clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 target:'<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
 eye:'<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>'
};
var WA='<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm5.8 15.7c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7 0a8.7 8.7 0 0 1-4.3-3.8c-.3-.6.3-.5.9-1.7a.6.6 0 0 0 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.7 6.3 6.3 0 0 0 1.3 3.3 14.4 14.4 0 0 0 5.5 4.9c2 .9 2.8.9 3.9.8a3.3 3.3 0 0 0 2.1-1.5 2.7 2.7 0 0 0 .2-1.5c-.1-.1-.3-.2-.7-.4z"/></svg>';

/* Íconos que el admin puede elegir para "¿Por qué elegirnos?" y los valores: [nombre visible, dibujo] */
var ICONS={
 check:["Palomita",'<path d="M5 12l5 5L20 7"/>'],
 estrella:["Estrella",'<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>'],
 corazon:["Corazón",'<path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/>'],
 escudo:["Escudo",'<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>'],
 foco:["Foco (idea)",'<path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"/>'],
 equipo:["Equipo",'<circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-3.5 2.7-6 6-6s6 2.5 6 6M15 14c3 0 6 2 6 5"/>'],
 reloj:["Reloj",'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
 lista:["Lista",'<path d="M4 7h16M4 12h10M4 17h7"/>'],
 cohete:["Cohete",'<path d="M5 15c-1.5 1.5-2 5-2 5s3.5-.5 5-2M9 15l-3-3a12 12 0 0 1 11-9c0 4-1.5 8-9 12z"/><circle cx="15" cy="9" r="1.5"/>'],
 camion:["Camión (envíos)",'<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>'],
 grafica:["Gráfica",'<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'],
 dinero:["Dinero",'<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/>'],
 globo:["Mundo",'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>'],
 telefono:["Teléfono",'<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>']
};
function icon(k){return svg((ICONS[k]||ICONS.check)[1])}
/* Redes sociales: [nombre visible, dibujo, usa contorno] */
var SOCIAL={
 facebook:["Facebook",'<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v8h4v-8h3l1-4h-4V8.5c0-.3.2-.5.5-.5z"/>'],
 instagram:["Instagram",'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/>',true],
 tiktok:["TikTok",'<path d="M16.5 3c.4 2.2 1.8 3.7 4 4v3.2a7.6 7.6 0 0 1-4-1.2V15a6 6 0 1 1-6-6h.6v3.3a2.8 2.8 0 1 0 2.2 2.7V3z"/>'],
 linkedin:["LinkedIn",'<path d="M4 9h4v12H4zM6 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM10 9h4v1.7c.6-1 1.9-2 3.8-2 3.2 0 4.2 2 4.2 5V21h-4v-6.3c0-1.5-.3-2.7-1.8-2.7S14 13.2 14 14.7V21h-4z"/>'],
 youtube:["YouTube",'<path fill-rule="evenodd" d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.6 12 5.6 12 5.6s-6 0-7.9.5A3 3 0 0 0 2 8.2 31 31 0 0 0 1.6 12 31 31 0 0 0 2 15.8a3 3 0 0 0 2.1 2.1c1.9.5 7.9.5 7.9.5s6 0 7.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .4-3.8 31 31 0 0 0-.4-3.8zM10 15V9l5.2 3z"/>'],
 x:["X (Twitter)",'<path d="M3 3h5l4.2 5.8L17.5 3H21l-7.2 8.2L21.5 21h-5l-4.6-6.3L6.3 21H3l7.6-8.6z"/>']
};
function socialHTML(){return Object.keys(SOCIAL).filter(function(k){return /^https?:\/\//i.test(String(A.S.social[k]||"").trim())}).map(function(k){var s=SOCIAL[k];return '<a href="'+esc(A.S.social[k].trim())+'" target="_blank" rel="noopener" aria-label="'+s[0]+'" title="'+s[0]+'"><svg viewBox="0 0 24 24"'+(s[2]?' class="st"':'')+' aria-hidden="true">'+s[1]+'</svg></a>'}).join("")}
function iconCards(list,cls){return list.map(function(x){return '<div class="'+cls+'"><span class="ico">'+icon(x.icon)+'</span><h3>'+esc(x.t)+'</h3><p>'+esc(x.d)+'</p></div>'}).join("")}
function renderFeatures(){$("#features").innerHTML=iconCards(A.S.features,"feat");$("#features").closest(".sec").hidden=!A.S.features.length}
function renderValues(){$("#values").innerHTML=iconCards(A.S.values,"card val");$("#values").closest(".sec").hidden=!A.S.values.length}
function initials(n){return String(n||"").trim().split(/\s+/).slice(0,2).map(function(w){return w.charAt(0).toUpperCase()}).join("")||"?"}
var QUOTE='<svg class="q" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 18v-5.5C4 8.4 6.2 6 9.5 5.5l.6 1.9C8.3 8 7.5 9.4 7.4 11H10v7zm10 0v-5.5c0-4.1 2.2-6.5 5.5-7l.6 1.9c-1.8.6-2.6 2-2.7 3.6H20v7z"/></svg>';
function renderTestimonials(){
 var L=A.S.testimonials;
 $("#tst").innerHTML=L.map(function(t){return '<figure class="card tst">'+QUOTE+'<blockquote>'+esc(t.text)+'</blockquote><figcaption class="who"><span class="av">'+(t.img?'<img src="'+esc(t.img)+'" alt="" loading="lazy">':esc(initials(t.name)))+'</span><div><b>'+esc(t.name)+'</b><span>'+esc(t.role)+'</span></div></figcaption></figure>'}).join("");
 $("#tstSec").hidden=!L.length;
}
function renderFaq(){
 var L=A.S.faqs.filter(function(f){return String(f.q||"").trim()});
 $("#faqList").innerHTML=L.map(function(f){return '<details><summary>'+esc(f.q)+'</summary><div class="ans">'+paras(f.a)+'</div></details>'}).join("");
 $("#faqSec").hidden=!L.length;
}
/* Ícono de la pestaña del navegador: el logo si existe; si no, la inicial con los colores de la marca */
function renderFavicon(){
 var b=A.S.brand,href;
 if(b.logo)href=b.logo;
 else{var ch=esc(brandInitial(b.name));href="data:image/svg+xml;utf8,"+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#16324a"/><rect x="14" y="48" width="36" height="5" rx="2.5" fill="#e0861a"/><text x="32" y="41" font-family="Poppins,Segoe UI,sans-serif" font-weight="800" font-size="34" fill="#fff" text-anchor="middle">'+ch+'</text></svg>')}
 $("#favicon").setAttribute("href",href);$("#favicon").removeAttribute("type");
}

/* Marca: logo o, si no hay, inicial + nombre */
function brandInitial(n){return String(n||"").replace(/[^\wÁÉÍÓÚÑáéíóúñ]/g,"").charAt(0).toUpperCase()||"E"}
function brandHTML(){var b=A.S.brand,n=b.name||"Empresa";
 return b.logo?'<img src="'+esc(b.logo)+'" alt="'+esc(n)+'">':'<span class="mark">'+esc(brandInitial(n))+'</span><span class="lname">'+esc(n)+'</span>'}
function waUrl(){var c=A.S.contact,num=String(c.whatsapp||"").replace(/\D/g,"");return num?"https://wa.me/"+num+(c.waMsg?"?text="+encodeURIComponent(c.waMsg):""):""}
function renderBrand(){
 $("#brand").innerHTML=brandHTML();$("#footBrand").innerHTML=brandHTML();
 $("#footTag").textContent=A.S.brand.tagline||"";
 $("#footName").textContent=A.S.brand.name||"";
 $("#year").textContent=new Date().getFullYear();
 var soc=socialHTML();$("#footSocial").innerHTML=soc;$("#footSocialCol").hidden=!soc;
 renderFavicon();
 if(A.setTitle)A.setTitle();
}
function renderContact(){
 var c=A.S.contact,tel=String(c.phone||"").replace(/[^\d+]/g,""),wa=waUrl();
 var items=[
  c.phone&&['phone','Teléfono','<a href="tel:'+esc(tel)+'">'+esc(c.phone)+'</a>'],
  c.email&&['mail','Correo','<a href="mailto:'+esc(c.email)+'">'+esc(c.email)+'</a>'],
  c.address&&['pin','Ubicación',esc(c.address)],
  c.hours&&['clock','Horario',esc(c.hours)]].filter(Boolean);
 $("#contactInfo").innerHTML='<h3>Hablemos</h3><p>¿Prefieres contactarnos directamente? Estamos para ayudarte.</p><ul>'+items.map(function(x){return '<li><span class="ico">'+svg(I[x[0]])+'</span><div><small>'+x[1]+'</small>'+x[2]+'</div></li>'}).join("")+'</ul>'+(function(s){return s?'<div class="social">'+s+'</div>':''})(socialHTML())+(wa?'<a class="btn wa-btn" href="'+esc(wa)+'" target="_blank" rel="noopener">'+WA+' Escríbenos por WhatsApp</a>':'');
 $("#footContact").innerHTML=items.map(function(x){return '<li>'+x[2]+'</li>'}).join("");
 var w=$("#wa");if(wa){w.href=wa;w.hidden=false}else w.hidden=true;
}
function renderStats(){
 var st=A.S.stats.filter(function(s){return s.n||s.l});
 $("#stats").innerHTML=st.map(function(s){return '<div class="stat"><b>'+esc(s.n)+'</b><span>'+esc(s.l)+'</span></div>'}).join("");
 $("#stats").hidden=!st.length;
}
function renderAbout(){
 $("#abTop").innerHTML='<img src="'+esc(A.S.about[0]||ph("Imagen de portada",200))+'" alt="">'+'<div class="ov"><div class="wrap"><h1>Nosotros</h1><p>'+esc(A.S.brand.tagline||"")+'</p></div></div>';
 $("#abBottom").innerHTML='<img src="'+esc(A.S.about[1]||ph("Imagen del equipo",260))+'" alt="Nuestro equipo" loading="lazy">';
 $("#storyText").innerHTML=paras(A.S.story);
 $("#mv").innerHTML=[["target","Misión",A.S.mission],["eye","Visión",A.S.vision]].filter(function(x){return String(x[2]||"").trim()}).map(function(x){return '<div class="card mv"><h3><span class="ico">'+svg(I[x[0]])+'</span>'+x[1]+'</h3>'+paras(x[2])+'</div>'}).join("");
}
var GROUPS=[["Servicio","Nuestros servicios","Servicios"],["Producto","Nuestros productos","Productos"]];
function card(s,i){return '<article class="card svc"><div class="im"><img src="'+esc(s.img||ph(esc(s.name),30+i*40))+'" alt="'+esc(s.name)+'" loading="lazy"><span class="badge'+(s.type==="Producto"?" prod":"")+'">'+esc(s.type)+'</span></div><div class="bd"><h3>'+esc(s.name)+'</h3><p>'+esc(s.desc)+'</p><div class="ft"><span class="price">'+esc(s.price||"")+'</span><a class="btn" href="#servicios" data-svc="'+esc(s.name)+'">Solicitar</a></div></div></article>'}
function renderServices(){
 var html=GROUPS.map(function(g){var items=A.S.services.map(function(x,i){return[x,i]}).filter(function(p){return p[0].type===g[0]});
  return items.length?'<h3 class="gtitle">'+g[1]+'</h3><div class="grid gauto">'+items.map(function(p){return card(p[0],p[1])}).join("")+'</div>':""}).join("");
 $("#svcList").innerHTML=html||'<p class="empty">Aún no hay servicios ni productos publicados.</p>';
 var sel=$("#servicio"),v=sel.value;
 sel.innerHTML='<option value="">Selecciona una opción</option>'+GROUPS.map(function(g){var items=A.S.services.filter(function(x){return x.type===g[0]});return items.length?'<optgroup label="'+g[2]+'">'+items.map(function(x){return '<option>'+esc(x.name)+'</option>'}).join("")+'</optgroup>':""}).join("");
 sel.value=v;
}
/* Secciones extra creadas desde el admin */
function renderBlocks(){
 /* p[2]: con qué fondo empieza, para alternar con la sección anterior */
 [["home","#blocksHome",0],["nosotros","#blocksAbout",1]].forEach(function(p){
  var list=A.S.blocks.filter(function(b){return b.page===p[0]});
  $(p[1]).innerHTML=list.map(function(b,i){return '<div class="sec'+((i+p[2])%2?" soft":"")+'"><div class="wrap blk'+(b.img?" has-img"+(b.side==="left"?" img-left":""):"")+'"><div class="blk-txt">'+(b.title?'<h2>'+esc(b.title)+'</h2>':'')+paras(b.text)+'</div>'+(b.img?'<div class="blk-img"><img src="'+esc(b.img)+'" alt="'+esc(b.title||"")+'" loading="lazy"></div>':'')+'</div></div>'}).join("");
 });
}
function renderAll(){renderBrand();renderContact();renderStats();A.renderSlides();renderFeatures();renderAbout();renderValues();renderServices();renderTestimonials();renderFaq();renderBlocks();A.play()}

A.ICONS=ICONS;A.SOCIAL=SOCIAL;A.renderAbout=renderAbout;A.renderServices=renderServices;A.renderAll=renderAll;
})(window.App);
