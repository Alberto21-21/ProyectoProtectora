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

// =========================================================================
// AÑADIDO: GESTIÓN DEL CATÁLOGO DE ANIMALES USANDO SOLO MÉTODOS DEL DOM EN JS
// =========================================================================

const gridAnimales = document.querySelector("#gridAnimales");
const modalDetalleAnimal = document.querySelector("#modalDetalleAnimal");
const cerrarDetalleAnimal = document.querySelector("#cerrarDetalleAnimal");
const contenidoDetalleAnimal = document.querySelector("#contenidoDetalleAnimal");

if (cerrarDetalleAnimal && modalDetalleAnimal) {
    cerrarDetalleAnimal.addEventListener("click", function () {
        modalDetalleAnimal.close();
    });
}

async function cargarAnimales() {
    if (!gridAnimales) return;

    try {
        const respuesta = await fetch("http://localhost:8000/api/animales");
        if (!respuesta.ok) throw new Error("Error API");
        const animales = await respuesta.json();
        renderizarAnimales(animales);
    } catch (error) {
        console.warn("Cargando datos provisionales de prueba:", error);
        const datosPrueba = [
            {
                id: 1,
                nombre: "Max",
                especie: "Perro",
                raza: "Labrador",
                sexo: "Macho",
                estado: "Disponible",
                caracter: "Muy cariñoso y juguetón.",
                imagen: "https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=500&q=80"
            },
            {
                id: 2,
                nombre: "Luna",
                especie: "Gato",
                raza: "Común Europeo",
                sexo: "Hembra",
                estado: "En acogida",
                caracter: "Tranquila y mimosa.",
                imagen: "https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=500&q=80"
            }
        ];
        renderizarAnimales(datosPrueba);
    }
}

function renderizarAnimales(lista) {
    gridAnimales.replaceChildren(); // Limpia el contenedor

    lista.forEach((animal) => {
        // Crear elemento tarjeta
        const tarjeta = document.createElement("article");
        tarjeta.className = "tarjeta-animal";

        // Imagen
        const img = document.createElement("img");
        img.src = animal.imagen || "/src/assets/imagen_portada.png";
        img.alt = animal.nombre;

        // Div info
        const infoDiv = document.createElement("div");
        infoDiv.className = "info-tarjeta";

        // Nombre
        const h3 = document.createElement("h3");
        h3.textContent = animal.nombre;

        // Especie
        const pEspecie = document.createElement("p");
        const strongEspecie = document.createElement("strong");
        strongEspecie.textContent = "Especie: ";
        pEspecie.appendChild(strongEspecie);
        pEspecie.appendChild(document.createTextNode(animal.especie));

        // Raza
        const pRaza = document.createElement("p");
        const strongRaza = document.createElement("strong");
        strongRaza.textContent = "Raza: ";
        pRaza.appendChild(strongRaza);
        pRaza.appendChild(document.createTextNode(animal.raza || "Mestizo"));

        // Botón
        const btnFicha = document.createElement("button");
        btnFicha.type = "button";
        btnFicha.className = "boton-ver-ficha";
        btnFicha.textContent = "Ver ficha";

        // Ensamblar infoDiv
        infoDiv.appendChild(h3);
        infoDiv.appendChild(pEspecie);
        infoDiv.appendChild(pRaza);
        infoDiv.appendChild(btnFicha);

        // Ensamblar tarjeta
        tarjeta.appendChild(img);
        tarjeta.appendChild(infoDiv);

        // Evento de clic
        tarjeta.addEventListener("click", function () {
            abrirFichaAnimal(animal);
        });

        gridAnimales.appendChild(tarjeta);
    });
}

function abrirFichaAnimal(animal) {
    if (!modalDetalleAnimal || !contenidoDetalleAnimal) return;

    contenidoDetalleAnimal.replaceChildren(); // Limpia el contenido previo

    const titulo = document.createElement("h2");
    titulo.textContent = animal.nombre;

    const img = document.createElement("img");
    img.src = animal.imagen || "/src/assets/imagen_portada.png";
    img.alt = animal.nombre;

    const pEspecie = crearParrafoConFormato("Especie: ", animal.especie);
    const pRaza = crearParrafoConFormato("Raza: ", animal.raza || "Mestizo");
    const pSexo = crearParrafoConFormato("Sexo: ", animal.sexo || "No especificado");
    const pEstado = crearParrafoConFormato("Estado: ", animal.estado || "Disponible");
    const pCaracter = crearParrafoConFormato("Carácter: ", animal.caracter || "Sin descripción.");

    const btnAdopcion = document.createElement("button");
    btnAdopcion.type = "button";
    btnAdopcion.className = "boton-portada";
    btnAdopcion.style.marginTop = "15px";
    btnAdopcion.style.width = "100%";
    btnAdopcion.textContent = "Solicitar adopción";

    contenidoDetalleAnimal.appendChild(titulo);
    contenidoDetalleAnimal.appendChild(img);
    contenidoDetalleAnimal.appendChild(pEspecie);
    contenidoDetalleAnimal.appendChild(pRaza);
    contenidoDetalleAnimal.appendChild(pSexo);
    contenidoDetalleAnimal.appendChild(pEstado);
    contenidoDetalleAnimal.appendChild(pCaracter);
    contenidoDetalleAnimal.appendChild(btnAdopcion);

    modalDetalleAnimal.showModal();
}

function crearParrafoConFormato(etiqueta, valor) {
    const p = document.createElement("p");
    const strong = document.createElement("strong");
    strong.textContent = etiqueta;
    p.appendChild(strong);
    p.appendChild(document.createTextNode(valor));
    return p;
}

// Cargar catálogo al inicio
cargarAnimales();