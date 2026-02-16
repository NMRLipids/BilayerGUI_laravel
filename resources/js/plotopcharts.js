/**
 * Chart utility functions for displaying data visualizations
 * Used in trajectory analysis views
 */

/** 
 * This file contains utility functions for processing data and building charts using Chart.js.
 * It includes functions to build x-axis labels from the dataset and to enrich the dataset with indices for plotting.
 */

// Function to build x-axis labels from data
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
                    lineWidth: 2
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
