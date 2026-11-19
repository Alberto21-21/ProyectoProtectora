import { defineConfig } from "vite";

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                inicio: "index.html",
                registro: "registro.html",
                login: "login.html",
                perfil: "perfil.html",
                hogar: "hogar.html",
                usuarios: "usuarios.html",
                animales: "animales.html",
                editarAnimal: "editarAnimal.html",
                misAnimales: "misAnimales.html",
                historial: "historial.html",
                disponibilidad: "disponibilidad.html",
                instalaciones: "instalaciones.html",
                habitaculos: "habitaculos.html"
            }
        }
    }
});