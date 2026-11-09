import { defineConfig } from "vite";

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                registro: "index.html",
                login: "login.html",
                perfil: "perfil.html"
            }
        }
    }
});