/* Formulario: validación y envío (conectar backend aquí). */
(function(A){
"use strict";
var $=A.$,esc=A.esc,ph=A.ph;
/* Formulario */
var form=$("#form");
function setErr(el,m){el.closest(".f").querySelector(".err").textContent=m||"";return !m}
form.addEventListener("submit",function(e){
 e.preventDefault();
 var ok=true,g=function(id){return $("#"+id)};
 ok=setErr(g("nombre"),g("nombre").value.trim().length<3?"Escribe tu nombre completo.":"")&&ok;
 ok=setErr(g("email"),/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(g("email").value)?"":"Escribe un correo válido.")&&ok;
 ok=setErr(g("tel"),/^[+\d][\d\s()-]{6,}$/.test(g("tel").value.trim())?"":"Escribe un teléfono con al menos 7 dígitos.")&&ok;
 ok=setErr(g("servicio"),g("servicio").value?"":"Elige un servicio.")&&ok;
 ok=setErr(g("desc"),g("desc").value.trim().length<10?"Cuéntanos un poco más (mínimo 10 caracteres).":"")&&ok;
 ok=setErr(g("terms"),g("terms").checked?"":"Debes aceptar los términos.")&&ok;
 if(!ok)return;
 var btn=$("#send");btn.disabled=true;btn.textContent="Enviando…";
 var data={nombre:g("nombre").value,email:g("email").value,tel:g("tel").value,empresa:g("empresa").value,servicio:g("servicio").value,plazo:g("plazo").value,cantidad:g("cant").value,descripcion:g("desc").value};
 // AQUÍ conectar el backend real (Formspree, EmailJS o tu API) usando "data".
 setTimeout(function(){form.reset();btn.disabled=false;btn.textContent="Enviar solicitud";$("#ok").style.display="block";$("#ok").scrollIntoView({block:"center"})},700);
});



})(window.App);