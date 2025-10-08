import { dirname, resolve} from 'path';
import { fileURLToPath} from 'url';
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const __dirname = dirname(fileURLToPath(import.meta.url))
export default defineConfig({
  plugins: [react()],
  build: {
    lib: {
      entry: {
        'solitaire': resolve(__dirname, 'src/lib/solitaire.js'),
        // 'bye': resolve(__dirname, 'src/lib/goodbye.js'),
      },
      name: 'Boardgames',
    },
    rollupOptions: {
      external: [
          'react',
          'react-dom/client',
          'react/jsx-runtime',
      ],

    }
  }
})
