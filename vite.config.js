import { defineConfig } from 'vite'; 
import laravel from 'laravel-vite-plugin';
export default defineConfig({
    plugins: [
        laravel({
            input: [
                    'resources/js/app.js', 
                    'resources/js/plotopcharts.js', 
                    'resources/js/plotApLchart.js',
                    'resources/js/plotFFcharts.js',
                    'resources/js/plotMembrane.js'
                ],
            refresh: true,
        }),
    ],
});