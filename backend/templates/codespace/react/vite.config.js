import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      apis: path.resolve(__dirname, ".monaco_apis/index.js")
    }
  },
  server: {
    port: 3000
  }
});
