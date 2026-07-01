import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import { globSync } from 'glob';

const pageEntries = Object.fromEntries(
  globSync('app/**/page.{ts,scss}', { windowsPathsNoEscape: true }).map((path) => [
    path.replace(/\\/g, '/'),
    resolve(__dirname, path),
  ]),
);

const componentEntries = Object.fromEntries(
  globSync('{components,app/**/components}/**/component.{ts,scss}', { windowsPathsNoEscape: true }).map((path) => [
    path.replace(/\\/g, '/'),
    resolve(__dirname, path),
  ]),
);

export default defineConfig({
  root: '.',
  publicDir: false,
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources'),
    },
  },
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: false,
    hmr: {
      host: '127.0.0.1',
    },
  },
  build: {
    manifest: 'manifest.json',
    sourcemap: true,
    outDir: 'public/assets',
    assetsDir: '',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/ts/app.ts'),
        styles: resolve(__dirname, 'resources/scss/app.scss'),
        ...pageEntries,
        ...componentEntries,
      },
    },
  },
});
