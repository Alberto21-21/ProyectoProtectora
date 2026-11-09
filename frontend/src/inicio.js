// REUTILIZAR LOS FORMULARIOS CONECTADOS CON LARAVEL
import "./login.js";
import "./registro.js";

// OBTENER LOS MODALES
const modalLogin = document.querySelector("#modalLogin");
const modalRegistro = document.querySelector("#modalRegistro");

// BOTONES DE LA PORTADA
const abrirLogin = document.querySelector("#abrirLogin");
const comenzarRegistro = document.querySelector("#comenzarRegistro");

// BOTONES PARA CERRAR
const cerrarLogin = document.querySelector("#cerrarLogin");
const cerrarRegistro = document.querySelector("#cerrarRegistro");

// BOTONES PARA CAMBIAR ENTRE MODALES
const irRegistro = document.querySelector("#irRegistro");
const irLogin = document.querySelector("#irLogin");

// ABRIR LOGIN
abrirLogin.addEventListener("click", function () {
    modalLogin.showModal();
});

// COMENZAR TAMBIEN ABRE EL REGISTRO
comenzarRegistro.addEventListener("click", function () {
    modalRegistro.showModal();
});

// CERRAR LOGIN
cerrarLogin.addEventListener("click", function () {
    modalLogin.close();
});

// CERRAR REGISTRO
cerrarRegistro.addEventListener("click", function () {
    modalRegistro.close();
});

// PASAR DEL LOGIN AL REGISTRO
irRegistro.addEventListener("click", function () {
    modalLogin.close();
    modalRegistro.showModal();
});

// PASAR DEL REGISTRO AL LOGIN
irLogin.addEventListener("click", function () {
    modalRegistro.close();
    modalLogin.showModal();
});