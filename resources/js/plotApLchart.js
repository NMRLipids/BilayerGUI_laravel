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