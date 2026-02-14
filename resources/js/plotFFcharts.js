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


    function drawOneChart(canvas, dataset, title) {
        let myChart = new Chart(canvas, {
            type: 'line',
            data: dataset,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
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
                            text: 'Q (Å⁻¹)',
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Form Factor (Å⁻¹)',
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
