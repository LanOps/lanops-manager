// Admin charts (Chart.js). A view adds <canvas data-chart="doughnut|bar"> with the
// data as JSON in data-chart-labels / data-chart-values; colours follow the
// light/dark colour mode and the charts redraw when it changes.
import {
    Chart,
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';

Chart.register(ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Legend, LinearScale, Tooltip);

// LanOps orange first, then hues that stay clear on both light and dark cards
const PALETTE = ['#ffa500', '#3d8fd6', '#2fb380', '#e05a87', '#8f6fd8', '#20b2c0', '#e8743b', '#9aa84a'];

function themeColours() {
    const style = getComputedStyle(document.documentElement);
    const read = (name) => style.getPropertyValue(name).trim();
    return {
        text: read('--bs-body-color'),
        muted: read('--bs-secondary-color'),
        grid: read('--bs-border-color-translucent'),
        background: read('--bs-body-bg'),
        tooltipBg: read('--bs-body-bg'),
        border: read('--bs-border-color'),
        font: read('--bs-body-font-family'),
    };
}

function readJson(canvas, key) {
    try {
        return JSON.parse(canvas.dataset[key] || '[]');
    } catch (e) {
        return [];
    }
}

function formatValue(value, format, wholePounds = false) {
    if (format === 'currency') {
        return new Intl.NumberFormat('en-GB', {
            style: 'currency',
            currency: 'GBP',
            minimumFractionDigits: wholePounds ? 0 : 2,
            maximumFractionDigits: wholePounds ? 0 : 2,
        }).format(value);
    }
    return new Intl.NumberFormat('en-GB').format(value);
}

// Long category names go on several lines rather than slanting under the bars
function wrapLabel(label, width = 14) {
    const lines = [];
    String(label).split(' ').forEach((word) => {
        const last = lines[lines.length - 1];
        if (last && (last + ' ' + word).length <= width) {
            lines[lines.length - 1] = last + ' ' + word;
        } else {
            lines.push(word);
        }
    });
    return lines;
}

function tooltipOptions(colours, format, seriesLabel) {
    return {
        backgroundColor: colours.tooltipBg,
        titleColor: colours.text,
        bodyColor: colours.text,
        borderColor: colours.border,
        borderWidth: 1,
        padding: 10,
        cornerRadius: 6,
        boxPadding: 4,
        callbacks: {
            label: (item) => {
                const value = formatValue(item.raw, format);
                return seriesLabel ? ` ${seriesLabel}: ${value}` : ` ${value}`;
            },
        },
    };
}

// Total in the middle of a doughnut
const centreTotal = {
    id: 'centreTotal',
    afterDraw(chart, args, options) {
        if (!options.display) {
            return;
        }
        const { ctx, chartArea } = chart;
        const total = chart.data.datasets[0].data.reduce((sum, value) => sum + value, 0);
        const x = (chartArea.left + chartArea.right) / 2;
        const y = (chartArea.top + chartArea.bottom) / 2;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = options.color;
        ctx.font = `600 28px ${options.font}`;
        ctx.fillText(formatValue(total, options.format), x, y - 8);
        ctx.fillStyle = options.mutedColor;
        ctx.font = `14px ${options.font}`;
        ctx.fillText(options.label, x, y + 18);
        ctx.restore();
    },
};

function buildConfig(canvas, colours) {
    const type = canvas.dataset.chart;
    const labels = readJson(canvas, 'chartLabels');
    const values = readJson(canvas, 'chartValues');
    const format = canvas.dataset.chartFormat || 'number';
    const seriesLabel = canvas.dataset.chartSeries || '';
    const colourFor = labels.map((label, index) => PALETTE[index % PALETTE.length]);
    // Gaps between doughnut slices take the colour of the card behind them
    const card = canvas.closest('.card');
    const background = card ? getComputedStyle(card).backgroundColor : colours.background;

    if (type === 'doughnut') {
        return {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colourFor,
                    borderColor: background,
                    borderWidth: 3,
                    hoverOffset: 6,
                }],
            },
            plugins: [centreTotal],
            options: {
                maintainAspectRatio: false,
                cutout: '68%',
                layout: { padding: 6 },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: colours.text, font: { family: colours.font }, usePointStyle: true, padding: 16 },
                    },
                    tooltip: tooltipOptions(colours, format, seriesLabel),
                    centreTotal: {
                        display: true,
                        label: canvas.dataset.chartTotalLabel || 'Total',
                        format,
                        color: colours.text,
                        mutedColor: colours.muted,
                        font: colours.font,
                    },
                },
            },
        };
    }

    return {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: seriesLabel,
                data: values,
                backgroundColor: canvas.dataset.chartColour === 'single' ? PALETTE[0] : colourFor,
                borderRadius: 4,
                maxBarThickness: 64,
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: tooltipOptions(colours, format, seriesLabel),
            },
            scales: {
                x: {
                    ticks: {
                        color: colours.muted,
                        font: { family: colours.font },
                        maxRotation: 0,
                        callback(value) {
                            return wrapLabel(this.getLabelForValue(value));
                        },
                    },
                    grid: { display: false },
                    border: { color: colours.grid },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: colours.muted,
                        font: { family: colours.font },
                        precision: 0,
                        callback: (value) => formatValue(value, format, true),
                    },
                    grid: { color: colours.grid },
                    border: { display: false },
                },
            },
        },
    };
}

const charts = new Map();

function drawAll() {
    const colours = themeColours();
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        if (charts.has(canvas)) {
            charts.get(canvas).destroy();
        }
        charts.set(canvas, new Chart(canvas, buildConfig(canvas, colours)));
    });
}

export function initAdminCharts() {
    const start = () => {
        drawAll();
        // Redraw with the new colours when the colour mode changes
        new MutationObserver(drawAll).observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-bs-theme'],
        });
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}
