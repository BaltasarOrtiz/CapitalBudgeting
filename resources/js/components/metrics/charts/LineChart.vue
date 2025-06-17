<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';

interface LineChartData {
    period: string;
    [key: string]: any;
}

interface Dataset {
    key: string;
    label: string;
    color: string;
}

interface Props {
    data: LineChartData[];
    datasets: Dataset[];
    height?: number;
    valueFormat?: 'currency' | 'percentage' | 'number';
}

const props = withDefaults(defineProps<Props>(), {
    height: 300,
    valueFormat: 'number'
});

const chartRef = ref<HTMLCanvasElement>();
let chartInstance: any = null;

const formatValue = (value: number): string => {
    switch (props.valueFormat) {
        case 'currency':
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(value);
        case 'percentage':
            return `${value.toFixed(1)}%`;
        default:
            return new Intl.NumberFormat('en-US').format(value);
    }
};

const createChart = async () => {
    if (!chartRef.value || props.data.length === 0) return;

    // Importar Chart.js dinámicamente
    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);

    const ctx = chartRef.value.getContext('2d');
    if (!ctx) return;

    // Destruir gráfico existente si existe
    if (chartInstance) {
        chartInstance.destroy();
    }

    const datasets = props.datasets.map(dataset => ({
        label: dataset.label,
        data: props.data.map(item => item[dataset.key]),
        borderColor: dataset.color,
        backgroundColor: dataset.color + '20', // 12% opacity
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: dataset.color,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 6,
        pointHoverRadius: 8,
    }));

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.data.map(item => item.period),
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: props.datasets.length > 1,
                    position: 'top',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.2)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context: any) {
                            return `${context.dataset.label}: ${formatValue(context.raw)}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)',
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)',
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)',
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)',
                        font: {
                            size: 12
                        },
                        callback: function(value: any) {
                            return formatValue(value);
                        }
                    }
                }
            },
            elements: {
                point: {
                    borderWidth: 2,
                    hoverBorderWidth: 3
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeInOutQuart'
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
};

onMounted(() => {
    createChart();
});

onUnmounted(() => {
    if (chartInstance) {
        chartInstance.destroy();
    }
});

watch(() => props.data, () => {
    createChart();
}, { deep: true });

watch(() => props.datasets, () => {
    createChart();
}, { deep: true });
</script>

<template>
    <div class="relative">
        <div v-if="data.length === 0" class="flex items-center justify-center h-64 text-blue-200/60">
            <div class="text-center">
                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                <p class="text-sm">No hay datos disponibles</p>
            </div>
        </div>

        <canvas
            v-else
            ref="chartRef"
            :height="height"
            class="max-w-full"
        ></canvas>
    </div>
</template>
