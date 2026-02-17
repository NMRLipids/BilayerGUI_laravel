/**
 * Area-per-Lipid (ApL) chart renderer.
 *
 * Renders a Chart.js line chart from JSON embedded in canvas `data-*` attributes.
 *
 * Expected DOM:
 * - One or more `<canvas data-apldata ...>` elements.
 * - Each canvas should provide:
 *   - `data-apldata`: JSON string representing a series of points.
 *     Supported point formats:
 *       - `{x: number, y: number}` objects, OR
 *       - `[x, y]` tuples.
 *   - `data-aptitle`: (optional) chart title.
 *
 * Axes:
 * - X: Time (ps)
 * - Y: Area per lipid (Å²)
 *
 * Example canvas:
 * ```html
 * <canvas
 *   id="ApLChart_1"
 *   data-aptitle="Area per lipid"
 *   data-apldata='[
 *     {"x": 0, "y": 62.1},
 *     {"x": 1000, "y": 61.8},
 *     {"x": 2000, "y": 62.5}
 *   ]'>
 * </canvas>
 * ```
 */

import Chart from 'chart.js/auto';

function drawApLChart(canvas, dataset, title) {

    let myChart = new Chart(canvas, {
        type: 'line',
        data: dataset,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false,
                },
                title: {
                    display: true,
                    text: title,
                },
            },
            scales: {
                x: {
                    type: 'linear',
                    position: 'bottom',
                    title: {
                        display: true,
                        text: 'Time (ps)',
                    },
                },
                y: {
                    title: {
                        display: true,
                        text: 'Area per lipid (Å²)',
                    },
                },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Select all canvases that have the data-opplot attribute
    const chartElements = document.querySelectorAll('canvas[data-apldata]');   
    chartElements.forEach(canvas => {
        try {
            let rawData = JSON.parse(canvas.dataset.apldata);
            if (typeof rawData === 'string') {
                rawData = JSON.parse(rawData);
            }
            const title = canvas.dataset.aptitle || 'Area per lipid'; // Default title if not provided
            // put each raw data in a dataset object with label and data properties
            let dataset = {
                datasets: [{
                    showLine: true,
                    data: rawData,
                    borderWidth: 1,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    pointHitRadius: 0
                }]
            };      
            // console.log("Rendering chart for element:", canvas.id, "with dataset:", dataset);
            drawApLChart(canvas, dataset, title);                      
        } catch (e) {
            console.error("Could not render chart for element:", canvas.id, e);
        }
    });
});