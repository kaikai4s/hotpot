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
      // 【修复】添加/storage代理，让前端可以访问Laravel存储的文件
      '/storage': {
        target: process.env.VITE_API_TARGET || 'http://localhost:8000',
        changeOrigin: true,
        // 不需要rewrite，直接转发到后端的/storage路径
      },
    },
  },
});

