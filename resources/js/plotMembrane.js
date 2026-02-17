/**
 * Render membrane composition doughnut charts (upper + lower leaflet) using Chart.js.
 *
 * Expected DOM:
 * - A canvas with id="UpperLeafletChart" and `data-composition-ul` containing JSON.
 * - A canvas with id="LowerLeafletChart" and `data-composition-ll` containing JSON.
 *
 * Data format:
 * The `data-composition-*` values should decode to an object mapping component name -> value,
 * e.g. `{ "POPC": 120, "CHOL": 40 }`.
 *
 * Behavior:
 * - Colors are assigned deterministically by label position (index in the labels array).
 * - Clicking a slice highlights that component in both charts (if present).
 * - Legend / tooltip text color inherits the surrounding computed text color for dark themes.
 *
 * Example canvases:
 * ```html
 * <div class="row">
 *   <div class="col">
 *     <canvas
 *       id="UpperLeafletChart"
 *       data-composition-ul='{"POPC": 120, "CHOL": 40, "POPE": 20}'>
 *     </canvas>
 *   </div>
 *   <div class="col">
 *     <canvas
 *       id="LowerLeafletChart"
 *       data-composition-ll='{"POPC": 110, "CHOL": 50, "POPE": 20}'>
 *     </canvas>
 *   </div>
 * </div>
 * ```
 */

import Chart from 'chart.js/auto';


const colorList = [
	'#0026ff',
	'#ef1e1e',
	'#f56eff',
	'#1bf3a3',
	'#bd17c9',
	'#079df4',
	'#c47379',
	'#C7CEEA',
	'#ffff1f',
	'#c8ffeb',
	'#00f9f5',
	'#996c83'
];

function parseJsonData(value) {
	if (value == null) return null;
	if (typeof value === 'object') return value;
	if (typeof value !== 'string') return null;
	const trimmed = value.trim();
	if (!trimmed) return null;
	try {
		let parsed = JSON.parse(trimmed);
		if (typeof parsed === 'string') {
			parsed = JSON.parse(parsed);
		}
		return parsed;
	} catch {
		return null;
	}
}




function colorForIndex(index) {
	const safeIndex = Number.isFinite(index) ? index : 0;
	return colorList[safeIndex % colorList.length];
}

function normalizeComposition(raw) {
	const data = raw && typeof raw === 'object' ? raw : null;
	if (!data) return { labels: [], values: [] };

	const labels = [];
	const values = [];

	for (const [key, value] of Object.entries(data)) {
		const numeric = typeof value === 'number' ? value : Number.parseFloat(value);
		if (!Number.isFinite(numeric) || numeric <= 0) continue;
		labels.push(key);
		values.push(numeric);
	}

	return { labels, values };
}

function buildDoughnutChart(canvas, { labels, values }, title, onSelectLabel) {
	// Color is computed by fixed index (label position)
	const backgroundColor = labels.map((_label, index) => colorForIndex(index));

	// Legend/tooltip text color should inherit from the page so it stays readable
	const computedColor = (
		getComputedStyle(canvas).color ||
		(canvas.parentElement ? getComputedStyle(canvas.parentElement).color : '')
	);
	const legendColor = computedColor && computedColor !== 'rgba(0, 0, 0, 0)' ? computedColor : undefined;

	const dataset = {
		label: title,
		data: values,
		backgroundColor,
		borderWidth: 1,
		offset: labels.map(() => 0)
	};

	const chart = new Chart(canvas, {
		type: 'doughnut',
		data: {
			labels,
			datasets: [dataset]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			cutout: '60%',
			plugins: {
				title: {
					display: false,
					text: title
				},
				legend: {
					display: true,
					position: 'bottom',
					labels: {
						color: legendColor,
						boxWidth: 14,
						padding: 12,
						usePointStyle: true
					}
				},
				tooltip: {
					titleColor: legendColor,
					bodyColor: legendColor,
					callbacks: {
						label: context => {
							const label = context.label ?? '';
							const value = context.parsed ?? 0;
							return `${label}: ${value}`;
						}
					}
				}
			},
			onClick: (_evt, elements) => {
				if (!elements || elements.length === 0) return;
				const element = elements[0];
				const index = element.index;
				const selected = chart.data.labels?.[index];
				if (selected) onSelectLabel(selected);
			}
		}
	});

	return chart;
}

function applySelection(chart, selectedLabel) {
	const labels = chart.data.labels || [];
	const dataset = chart.data.datasets?.[0];
	if (!dataset) return;

	dataset.offset = labels.map(label => (label === selectedLabel ? 16 : 0));
	chart.update();
}

document.addEventListener('DOMContentLoaded', () => {
	const canvasUpper = document.getElementById('UpperLeafletChart');
	const canvasLower = document.getElementById('LowerLeafletChart');

	if (!canvasUpper && !canvasLower) return;

	const charts = [];
	let selectedLabel = null;

	const onSelectLabel = label => {
		selectedLabel = label;
		charts.forEach(c => applySelection(c, selectedLabel));
	};

	if (canvasUpper) {
		const raw = parseJsonData(canvasUpper.dataset.compositionUl);
		const comp = normalizeComposition(raw);
		if (comp.labels.length) {
			charts.push(buildDoughnutChart(canvasUpper, comp, 'Upper leaflet composition', onSelectLabel));
		}
	}

	if (canvasLower) {
		const raw = parseJsonData(canvasLower.dataset.compositionLl);
		const comp = normalizeComposition(raw);
		if (comp.labels.length) {
			charts.push(buildDoughnutChart(canvasLower, comp, 'Lower leaflet composition', onSelectLabel));
		}
	}
});

