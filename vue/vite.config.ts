import { defineConfig } from "vite"
import vue from "@vitejs/plugin-vue"
import vueJsx from "@vitejs/plugin-vue-jsx"
import tailwindcss from "@tailwindcss/vite"

export default defineConfig({
    plugins: [vue(), vueJsx(), tailwindcss()],
    server: {
        proxy: {
            "/api": {
                target: "http://localhost:8080",
                changeOrigin: true,
            },
        },
    },
})