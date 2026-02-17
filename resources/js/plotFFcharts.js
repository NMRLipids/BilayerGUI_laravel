/**
 * Form Factor (FF) chart renderer.
 *
 * Renders Chart.js line charts from JSON embedded in canvas `data-*` attributes.
 *
 * Expected DOM:
 * - One or more `<canvas data-ffdata ...>` elements.
 * - Each canvas should provide:
 *   - `data-ffdata`: JSON string of an array of series.
 *     Each series is an array of points in one of these forms:
 *       - `[x, y]` tuples, e.g. `[[0.1, 12.3], [0.2, 11.9]]`, OR
 *       - `{x: number, y: number}` objects.
 *   - `data-fflegend`: JSON string array of legend labels (one per series).
 *   - `data-fftitle`: (optional) chart title.
 * - Optional normalization toggle:
 *   - an `<input type="checkbox">` with `data-ffnormalize-target="<canvasId>"`.
 *
 * Notes:
 * - When normalization is enabled, each series is min-max scaled to [0, 1].
 * - Axis ticks/titles and legend/title text colors inherit from computed DOM text color
 *   so charts remain readable on dark backgrounds.
 *
 * Example canvas:
 * ```html
 * <input type="checkbox" id="FormFactorChart_1_normalize"
 *  checked data-ffnormalize-target="FormFactorChart_1" />
 * <canvas
 *   id="FormFactorChart_1"
 *   data-fftitle="Normalized Form Factor"
 *   data-fflegend='["Simulation","Experiment"]'
 *   data-ffdata='[
 *     [[0.10, 12.3], [0.20, 11.9], [0.30, 11.2]],
 *     [[0.10, 10.1], [0.20, 10.4], [0.30, 10.0]]
 *   ]'>
 * </canvas>
 * ```
 */

import Chart from 'chart.js/auto';
const colorList = [
        '#fcfcfc',
        '#ef1e1e',
        '#6e8bff',       
        '#1bf3a3',
        '#bd17c9',
        '#07f452',
        '#c47379',
        '#C7CEEA',
        '#ffff1f',
        '#c8ffeb',
        '#00f9f5',
        '#996c83'
    ];

function getComputedTextColor(el) {
    if (!el) return undefined;
    const own = getComputedStyle(el).color;
    if (own && own !== 'rgba(0, 0, 0, 0)') return own;
    if (el.parentElement) {
        const parent = getComputedStyle(el.parentElement).color;
        if (parent && parent !== 'rgba(0, 0, 0, 0)') return parent;
    }
    return undefined;
}

function withAlpha(color, alpha) {
    if (!color) return undefined;
    const rgb = color.match(/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i);
    if (rgb) {
        const r = Number(rgb[1]);
        const g = Number(rgb[2]);
        const b = Number(rgb[3]);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    const rgba = color.match(/^rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([\d.]+)\s*\)$/i);
    if (rgba) {
        const r = Number(rgba[1]);
        const g = Number(rgba[2]);
        const b = Number(rgba[3]);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    return color;
}


    function drawOneChart(canvas, dataset, title) {
        const textColor = getComputedTextColor(canvas);
        const gridColor = withAlpha(textColor, 0.18);

        let myChart = new Chart(canvas, {
            type: 'line',
            data: dataset,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            color: textColor,
                        }
                    },
                    title: {
                        display: true,
                        text: title,
                        color: textColor,
                    },
                },
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        startAtZero: false,
                        ticks: {
                            color: textColor,
                        },
                        grid: {
                            color: gridColor,
                        },
                        border: {
                            color: gridColor,
                        },
                        title: {
                            display: true,
                            text: 'Q (Å⁻¹)',
                            color: textColor,
                        },
                    },
                    y: {
                        ticks: {
                            color: textColor,
                        },
                        grid: {
                            color: gridColor,
                        },
                        border: {
                            color: gridColor,
                        },
                        title: {
                            display: true,
                            text: 'Form Factor (Å⁻¹)',
                            color: textColor,
                        },
                    },
                },
            },
        });

        return myChart;
    }

    function normalizeSeries(data) {
        if (!Array.isArray(data) || data.length === 0) {
            return data;
        }

        const values = data
            .map(point => {
                if (typeof point === 'number') {
                    return point;
                }
                if (Array.isArray(point) && point.length >= 2) {
                    return point[1];
                }
                if (point && typeof point === 'object' && Number.isFinite(point.y)) {
                    return point.y;
                }
                return null;
            })
            .filter(value => Number.isFinite(value));

        if (values.length === 0) {
            return data;
        }

        const min = Math.min(...values);
        const max = Math.max(...values);
        const range = max - min;

        const normalizeValue = value => (range === 0 ? 0 : (value - min) / range);

        return data.map(point => {
            if (typeof point === 'number') {
                return normalizeValue(point);
            }
            if (Array.isArray(point) && point.length >= 2) {
                return [point[0], normalizeValue(point[1])];
            }
            if (point && typeof point === 'object' && Number.isFinite(point.y)) {
                return {
                    ...point,
                    y: normalizeValue(point.y),
                };
            }
            return point;
        });
    }



    function parseJsonData(value) {
        let parsed = JSON.parse(value);
        if (typeof parsed === 'string') {
            parsed = JSON.parse(parsed);
        }
        return parsed;
    }

    function buildDataset(seriesList, legendList) {
        return {
            datasets: seriesList.map((data, index) => ({
                label: legendList[index] || `Dataset ${index + 1}`,
                showLine: true,
                data: data,
                borderWidth: 1,
                pointRadius: 0,
                pointHoverRadius: 0,
                pointHitRadius: 0,
                backgroundColor: colorList[index % colorList.length],
                borderColor: colorList[index % colorList.length].replace('0.2', '1')
            }))
        };
    }

    function setYAxisTitle(chart, isNormalized) {
        chart.options.scales.y.title.text = isNormalized
            ? 'Form Factor (0-1)'
            : 'Form Factor (Å⁻¹)';
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Select all canvases that have the data-ffdata attribute
        const chartElements = document.querySelectorAll('canvas[data-ffdata]');
        const chartsById = new Map();

        chartElements.forEach(canvas => {
            try {
                const rawData = parseJsonData(canvas.dataset.ffdata);
                const rawLegend = parseJsonData(canvas.dataset.fflegend);
                const title = canvas.dataset.fftitle || 'Form Factor'; // Default title if not provided
                const toggle = document.querySelector(`input[data-ffnormalize-target="${canvas.id}"]`);
                const isNormalized = toggle ? toggle.checked : true;
                const seriesList = isNormalized
                    ? rawData.map(series => normalizeSeries(series))
                    : rawData;
                const dataset = buildDataset(seriesList, rawLegend);
                const chart = drawOneChart(canvas, dataset, title);

                setYAxisTitle(chart, isNormalized);
                chart.update();

                chartsById.set(canvas.id, {
                    chart,
                    rawData,
                    rawLegend,
                    title,
                });

                if (toggle) {
                    toggle.addEventListener('change', event => {
                        const nextNormalized = event.target.checked;
                        const nextSeriesList = nextNormalized
                            ? rawData.map(series => normalizeSeries(series))
                            : rawData;
                        chart.data = buildDataset(nextSeriesList, rawLegend);
                        setYAxisTitle(chart, nextNormalized);
                        chart.update();
                    });
                }
            } catch (e) {
                console.error("Could not render chart for element:", canvas.id, e);
            }
        });
    });
