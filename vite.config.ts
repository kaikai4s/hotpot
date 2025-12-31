import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
    },
  },
  publicDir: false,
  build: {
    outDir: 'public/build',
    manifest: true,
    rollupOptions: {
      input: resolve(__dirname, 'resources/js/main.ts'),
    },
  },
  server: {
    host: '0.0.0.0', // 允许外部访问
    port: 5173,
    strictPort: true,
    hmr: {
      // HMR使用客户端连接的主机名，自动适配
      clientPort: 5173,
    },
    proxy: {
      '/api': {
        // 使用环境变量或默认localhost（同一机器部署时）
        target: process.env.VITE_API_TARGET || 'http://localhost:8000',
        changeOrigin: true,
        // 如果前后端分离部署，需要配置这个
        // rewrite: (path) => path.replace(/^\/api/, ''),
      },
    },
  },
});

