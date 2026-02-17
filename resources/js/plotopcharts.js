/**
 * Order-Parameter (OP) chart renderer for trajectory analysis views.
 *
 * Renders Chart.js scatter charts from JSON embedded in canvas `data-*` attributes.
 *
 * Expected DOM:
 * - One or more `<canvas data-opplot ...>` elements.
 * - Each canvas should provide:
 *   - `data-opplot`: JSON string of an array of series, where each series is an array of points.
 *     Each point is an object with at least: `{ C: string, H: string, OP: number, STD?: number }`.
 *   - `data-oplegend`: JSON string array of legend labels (one per series).
 *   - `data-optitle`: (optional) chart title.
 *
 * Notes:
 * - X-axis labels are derived from unique `(C, H)` pairs as `C[H]`.
 * - Data points are enriched with `x` (index into derived labels) and `y` (= OP).
 * - Missing `(C,H)` points in a series are normalized to null values so all series share the same x-axis.
 * - A custom whisker plugin draws error bars when `STD` is present.
 *
 * Example canvas:
 * ```html
 * <canvas
 *   id="OPChart_DPPC_G1"
 *   data-opplot='[
 *     [
 *       {"C":"C1","H":"H11","OP":-0.18,"STD":0.01},
 *       {"C":"C1","H":"H12","OP":-0.17,"STD":0.02}
 *     ],
 *     [
 *       {"C":"C1","H":"H11","OP":-0.10,"STD":0.01},
 *       {"C":"C1","H":"H12","OP":-0.12,"STD":0.02}
 *     ]
 *   ]'
 *   data-oplegend='["Simulation","Experiment"]'
 *   data-optitle="DPPC OP (G1)">
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

function makeLabel(point) {
    return point.C + '[' + point.H + ']';
}


// Function to build x-axis labels from data
// It extracts unique combinations of C and H values to form labels like 'C1H11'
function buildXaxisFromData(data) {
    const labelSet = new Set();
    data.datasets.forEach(dataset => {
        dataset.data.forEach(point => {
            if (point.C && point.H) {
                labelSet.add(makeLabel(point));               
            }
        });
    });
    return Array.from(labelSet);
}
// Function to add x,y indices to data based on labels
function enrichDataWithIndices(data) {
    const labels = buildXaxisFromData(data);
    data.labels = labels;
    
    data.datasets.forEach((dataset, index) => {
        dataset.data = dataset.data.map(point => ({
            ...point,
            C: point.C || '',
            H: point.H || '',
            x: labels.indexOf(makeLabel(point)),
            y: point.OP
        }));
        dataset.backgroundColor = colorList[index % colorList.length];
        dataset.borderColor = dataset.backgroundColor.replace('0.2', '1');
    });
    return data;
}

// Function to normalize datasets by inserting null values for missing x-labels
function normalizeDatasets(data) {
    if (!data.datasets || data.datasets.length === 0) return;
    
    // For each dataset, ensure all labels are present
    data.datasets.forEach(dataset => {
        const dataMap = new Map();
        
        // Map existing data points by their C+H key
        dataset.data.forEach(point => {
            if (point && point.C && point.H) {
                dataMap.set(makeLabel(point), point);
            }
        });
        
        // Rebuild data array with all labels
        dataset.data = data.labels.map(label => {
            if (dataMap.has(label)) {
                return dataMap.get(label);
            } else {
                // Extract C and H from the label
                const C = label.slice(0, label.indexOf('['));
                const H = label.slice(label.indexOf('[') + 1, label.indexOf(']'));
                return { C, H, OP: null, STD: null };
            }
        });
    });
    return data;
}


const whiskerPlugin = {
            id: 'whiskerPlugin',
            afterDataLimits(chart, _args, pluginOptions) {
                if (!pluginOptions || !pluginOptions.enabled) return;
                const yScale = chart?.scales?.y;
                if (!yScale) return;

                // Recompute y-axis data limits including the whisker extents (OP ± STD),
                // so the error bars never get clipped by the chart area.
                let min = Number.POSITIVE_INFINITY;
                let max = Number.NEGATIVE_INFINITY;

                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    const meta = chart.getDatasetMeta(datasetIndex);
                    if (!meta || meta.hidden) return;

                    (dataset.data || []).forEach(point => {
                        if (!point || point.OP == null) return;
                        const op = Number(point.OP);
                        if (!Number.isFinite(op)) return;
                        const stdRaw = Number(point.STD);
                        // If STD is missing/invalid, treat error as 0 (no whisker extension).
                        const err = Number.isFinite(stdRaw) ? Math.abs(stdRaw) : 0;
                        min = Math.min(min, op - err);
                        max = Math.max(max, op + err);
                    });
                });

                if (!Number.isFinite(min) || !Number.isFinite(max)) return;

                const range = max - min;
                const paddingRatio = Number.isFinite(pluginOptions.paddingRatio)
                    ? pluginOptions.paddingRatio
                    : 0.05;
                const minPadding = Number.isFinite(pluginOptions.minPadding)
                    ? pluginOptions.minPadding
                    : 0.02;

                // Add a small pad so caps don't sit exactly on the border.
                // Uses ratio of the data range, but also enforces a minimal absolute padding.
                const pad = Math.max((range === 0 ? 1 : range) * paddingRatio, minPadding);
                const paddedMin = min - pad;
                const paddedMax = max + pad;

                // Expand (never shrink) the computed scale limits for this update.
                // This avoids fighting with user-defined limits / other scale logic.
                yScale.min = Number.isFinite(yScale.min) ? Math.min(yScale.min, paddedMin) : paddedMin;
                yScale.max = Number.isFinite(yScale.max) ? Math.max(yScale.max, paddedMax) : paddedMax;
            },
            afterDatasetsDraw(chart, args, pluginOptions) {
                if (!pluginOptions || !pluginOptions.enabled) return;
                const { ctx, scales } = chart;
                const yScale = scales.y;
                if (!yScale) return;

                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    const meta = chart.getDatasetMeta(datasetIndex);
                    if (!meta || meta.hidden) return;

                    dataset.data.forEach((point, index) => {
                        if (!point || point.OP == null || point.STD == null) return;
                        const element = meta.data[index];
                        if (!element) return;

                        const x = element.x;
                        const yTop = yScale.getPixelForValue(point.OP + point.STD);
                        const yBottom = yScale.getPixelForValue(point.OP - point.STD);
                        const capWidth = pluginOptions.capWidth || 8;

                        ctx.save();
                        ctx.strokeStyle = dataset.borderColor || '#333';
                        ctx.lineWidth = pluginOptions.lineWidth || 1;

                        ctx.beginPath();
                        ctx.moveTo(x, yTop);
                        ctx.lineTo(x, yBottom);
                        ctx.stroke();

                        ctx.beginPath();
                        ctx.moveTo(x - capWidth / 2, yTop);
                        ctx.lineTo(x + capWidth / 2, yTop);
                        ctx.moveTo(x - capWidth / 2, yBottom);
                        ctx.lineTo(x + capWidth / 2, yBottom);
                        ctx.stroke();

                        ctx.restore();
                    });
                });
            }
        };

function drawOneChart(canvas, dataset, legend, title) {
    
    // 1. Get the data from the attributes
    
    let plotData = normalizeDatasets(enrichDataWithIndices(dataset));
    // 2. Normalize datasets to ensure all labels are present
    // This will insert null values for any missing x-labels in each dataset, ensuring consistent x-axis across all datasets
    // Configuration for the chart, including the custom whisker plugin for error bars

    const config = {
        type: 'scatter',
        data: plotData,
        plugins: [whiskerPlugin],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    bottom: 30,
                }
            },
            parsing: {
                yAxisKey: 'OP'
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#f8f5f5',
                        font: {
                            size: 14
                        }
                    },
                    
                },
                title: {
                    display: true,
                    text: title,
                    color: '#ffffff'
                },
                whiskerPlugin: {
                    enabled: true,
                    capWidth: 8,
                    lineWidth: 2,
                    paddingRatio: 0.05,
                    minPadding: 0.02
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            const dataPoint = context[0].raw;
                            return  dataPoint.C + '[' + dataPoint.H+']';
                        },
                        label: function(context) {
                            const dataPoint = context.raw;
                            return 'OP: ' + dataPoint.OP.toFixed(3) + ' ± ' + dataPoint.STD.toFixed(3);
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'category',
                    ticks: {
                        color: '#ffffff'
                    },
                    grid: {
                        color: '#676666',
                        drawBorder: false,
                        borderColor: '#fbfbfb'
                    }
                },
                
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#e1e1e1',
                        callback: function(value) {
                            return typeof value === 'number' ? value.toFixed(2) : value;
                        }
                    },
                    grid: {
                        color: '#a3a2a2',
                        drawBorder: true,
                        borderColor: '#ffffff'
                    }
                }
            }
        }
    };

    let myChart = new Chart(canvas, config);
    myChart.resize("20%", "20%");

}

document.addEventListener('DOMContentLoaded', () => {
    // Select all canvases that have the data-opplot attribute
    const chartElements = document.querySelectorAll('canvas[data-opplot]');   
    chartElements.forEach(canvas => {
        try {
            const rawData = JSON.parse(canvas.dataset.opplot);        
            const rawLegend = JSON.parse(canvas.dataset.oplegend);
            const title = canvas.dataset.optitle;
            // put each raw data in a dataset object with label and data properties
            let dataset = {
                datasets:rawData.map((data, index) => ({
                    label: rawLegend[index] || `Dataset ${index + 1}`,
                    showLine: false,
                    data: data,
                    borderWidth: 3
                }))
            };   
            // console.log("Rendering chart for element:", canvas.id, "with dataset:", dataset);

            drawOneChart(canvas, dataset, rawLegend, title);                      
        } catch (e) {
            console.error("Could not render chart for element:", canvas.id, e);
        }
    });
});
