<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';

interface BarChartData {
    label: string;
    value: number;
    [key: string]: any;
}

interface Props {
    data: BarChartData[];
    height?: number;
    color?: string;
    valueFormat?: 'currency' | 'percentage' | 'number';
}

const props = withDefaults(defineProps<Props>(), {
    height: 300,
    color: '#3B82F6',
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

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.data.map(item => item.label),
            datasets: [{
                label: 'Valor',
                data: props.data.map(item => item.value),
                backgroundColor: props.color + '80', // 50% opacity
                borderColor: props.color,
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: props.color,
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
                        },
                        maxRotation: 45,
                        minRotation: 0
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
                bar: {
                    borderWidth: 2,
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
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

watch(() => props.color, () => {
    createChart();
});
</script>

<template>
    <div class="relative">
        <div v-if="data.length === 0" class="flex items-center justify-center h-64 text-blue-200/60">
            <div class="text-center">
                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
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
