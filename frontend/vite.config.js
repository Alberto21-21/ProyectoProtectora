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
                instalaciones: "instalaciones.html",
                habitaculos: "habitaculos.html",
                compatibilidad: "compatibilidad.html",
                misSolicitudes: "misSolicitudes.html",
                revisionSolicitudes: "revisionSolicitudes.html",
                sobreNosotros: "sobreNosotros.html",
                contacto: "contacto.html",
            }
        }
    }
});