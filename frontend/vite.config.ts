import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { TanStackRouterVite } from '@tanstack/router-vite-plugin'
import path from 'path'

const proxyTarget = process.env.PROXY_TARGET || 'http://localhost:8080'
// Solo activar HMR vía ngrok cuando se trabaja explícitamente por el túnel.
// Si NGROK_FRONT_URL está en .env pero abres localhost:5173, el HMR wss falla y Vite recarga en bucle.
const ngrokFrontUrl =
  process.env.VITE_HMR_NGROK === '1' ? process.env.NGROK_FRONT_URL || '' : ''

export default defineConfig({
  plugins: [TanStackRouterVite(), react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    watch: {
      usePolling: true,
      interval: 1000,
    },
    hmr: ngrokFrontUrl
      ? {
          protocol: 'wss',
          host: new URL(ngrokFrontUrl).hostname,
          clientPort: 443,
        }
      : {
          host: 'localhost',
          port: 5173,
          clientPort: 5173,
        },
    allowedHosts: ngrokFrontUrl
      ? [new URL(ngrokFrontUrl).hostname, '.ngrok-free.dev', '.ngrok-free.app']
      : true,
    proxy: {
      '/api': {
        target: proxyTarget,
        changeOrigin: true,
        timeout: 300_000,
        proxyTimeout: 300_000,
      },
      '/.well-known/mercure': {
        target: process.env.MERCURE_PROXY_TARGET || 'http://localhost:3000',
        changeOrigin: true,
        configure: (proxy) => {
          proxy.on('proxyReq', (proxyReq, req) => {
            if (!req.url) return;
            try {
              const url = new URL(req.url, 'http://vite.local');
              const auth = url.searchParams.get('authorization');
              if (auth && !proxyReq.getHeader('authorization')) {
                proxyReq.setHeader(
                  'Authorization',
                  auth.startsWith('Bearer ') ? auth : `Bearer ${auth}`,
                );
              }
            } catch {
              // ignore malformed URLs
            }
          });
        },
      },
    },
  },
})
