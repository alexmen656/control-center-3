import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      apis: path.resolve(__dirname, ".monaco_apis/index.js")
    }
  },
  server: {
    port: 3000
  }
});
