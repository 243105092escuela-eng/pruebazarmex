/* Formulario: validación y envío (conectar backend aquí). */
(function(A){
"use strict";
var $=A.$;
var form=$("#form"),okMsg=$("#ok");
function setErr(el,m){el.closest(".f").querySelector(".err").textContent=m||"";return !m}
/* Botón "Solicitar" de cada tarjeta: deja elegido ese servicio en el formulario */
document.addEventListener("click",function(e){var a=e.target.closest("a[data-svc]");if(!a)return;var sel=$("#servicio");sel.value=a.dataset.svc;setErr(sel,"")});
/* Si vuelven a escribir, se oculta el mensaje de enviado */
form.addEventListener("input",function(){okMsg.style.display="none"});
form.addEventListener("submit",function(e){
 e.preventDefault();
 okMsg.style.display="none";
 var ok=true,g=function(id){return $("#"+id)};
 ok=setErr(g("nombre"),g("nombre").value.trim().length<3?"Escribe tu nombre completo.":"")&&ok;
 ok=setErr(g("email"),/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(g("email").value)?"":"Escribe un correo válido.")&&ok;
 ok=setErr(g("tel"),/^[+\d][\d\s()-]{6,}$/.test(g("tel").value.trim())?"":"Escribe un teléfono con al menos 7 dígitos.")&&ok;
 ok=setErr(g("servicio"),g("servicio").value?"":"Elige un servicio.")&&ok;
 ok=setErr(g("desc"),g("desc").value.trim().length<10?"Cuéntanos un poco más (mínimo 10 caracteres).":"")&&ok;
 ok=setErr(g("terms"),g("terms").checked?"":"Debes aceptar los términos.")&&ok;
 if(!ok){var first=form.querySelector(".err:not(:empty)");if(first)first.closest(".f").scrollIntoView({block:"center"});return}
 var btn=$("#send");btn.disabled=true;btn.textContent="Enviando…";
 var data={nombre:g("nombre").value,email:g("email").value,tel:g("tel").value,empresa:g("empresa").value,servicio:g("servicio").value,plazo:g("plazo").value,cantidad:g("cant").value,descripcion:g("desc").value};
 // AQUÍ conectar el backend real (Formspree, EmailJS o tu API) usando "data".
 setTimeout(function(){form.reset();btn.disabled=false;btn.textContent="Enviar solicitud";okMsg.style.display="block";okMsg.scrollIntoView({block:"center"})},700);
});
})(window.App);
