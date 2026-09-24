import { existsSync, rmSync } from 'node:fs';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Build-only guard: `npm run dev` writes a `public/hot` file pointing at the local Vite dev
 * server. If that file gets uploaded to production, Laravel serves every page's CSS/JS from
 * http://localhost:5173 instead of the compiled bundle in `public/build` — the classic "works
 * locally, no styles/scripts after cPanel upload" failure. Production builds must never ship it.
 */
function removeHotFile() {
    return {
        name: 'remove-hot-file',
        apply: 'build',
        buildStart() {
            const hotFile = resolve('public/hot');
            if (existsSync(hotFile)) {
                rmSync(hotFile);
                console.log('[deploy] removed stale public/hot (dev-server pointer)');
            }
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        removeHotFile(),
    ],
});
