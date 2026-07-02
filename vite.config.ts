/// <reference types="vitest" />

import legacy from "@vitejs/plugin-legacy";
import vue from "@vitejs/plugin-vue";
import path from "path";
import { defineConfig } from "vite";

import { version } from "./package.json";

// https://vitejs.dev/config/
export default defineConfig({
  define: {
    "import.meta.env.VITE_APP_VERSION": JSON.stringify(version),
  },
  plugins: [vue(), legacy()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  test: {
    globals: true,
    environment: "jsdom",
  },
  build: {
    outDir: "dist",
    minify: "esbuild",
    sourcemap: true,
    target: "es2015",
    rollupOptions: {
      input: {
        main: "./index.html"
      },
    },
  },
});
