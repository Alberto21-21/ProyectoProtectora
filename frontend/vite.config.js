import { defineConfig } from "vite";

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                inicio: "index.html",
                registro: "registro.html",
                login: "login.html",
                perfil: "perfil.html",
                animales: "animales.html"
            }
        }
    }
});