import { fileURLToPath, URL } from 'node:url';

import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',  // Lauscht auf allen Netzwerkadressen
    port: 5173,        // Hier kannst du auch den Port anpassen
    strictPort: true,  // Verhindert, dass Vite automatisch einen anderen Port wählt, wenn der angegebene bereits belegt ist
  },
  devServer: {
    port: 9999,
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  define: {
    __APP_VERSION__: JSON.stringify(process.env.npm_package_version),
  },
});
