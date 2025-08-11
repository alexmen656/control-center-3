import { defineConfig } from "vite";
import path from "path";

export default defineConfig({
    resolve: {
        alias: {
            apis: path.resolve(__dirname, ".monaco_apis/index.js")
        }
    },
    server: {
        port: 3000
    }
});
