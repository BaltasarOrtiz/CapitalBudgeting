<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface Optimization {
    id: number;
    description: string;
    status: 'pending' | 'running' | 'completed' | 'failed';
    created_at: string;
    completed_at?: string;
    total_periods: number;
    discount_rate: number;
    initial_balance: number;
    result?: OptimizationResult;
    selectedProjects?: SelectedProject[];
    periodBalances?: PeriodBalance[];
    periodCashFlows?: PeriodCashFlow[];
}

interface OptimizationResult {
    npv: number;
    final_balance: number;
    initial_balance: number;
    total_periods: number;
    total_projects: number;
    projects_selected: number;
    status: string;
}

interface SelectedProject {
    project_name: string;
    start_period: number;
    setup_cost: number;
    total_reward: number;
    npv_contribution: number;
}

interface PeriodBalance {
    period: number;
    balance: number;
    discounted_balance: number;
}

interface PeriodCashFlow {
    period: number;
    cash_in: number;
    cash_out: number;
    net_cash_flow: number;
}

const props = defineProps<{
    currentOptimization?: Optimization;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Resultados',
        href: '/dashboard/resultados',
    },
];

const optimization = ref<Optimization | null>(props.currentOptimization || null);
const pollingInterval = ref<number | null>(null);
const loadingMessages = ref<string[]>([
    'Procesando modelo de optimización...',
    'Analizando proyectos y restricciones...',
    'Calculando combinaciones óptimas...',
    'Aplicando restricciones de liquidez...',
    'Optimizando valor presente neto...',
    'Finalizando cálculos...'
]);
const currentMessageIndex = ref(0);
const messageInterval = ref<number | null>(null);

const isRunning = computed(() => optimization.value?.status === 'running');
const isCompleted = computed(() => optimization.value?.status === 'completed');
const isFailed = computed(() => optimization.value?.status === 'failed');
const hasOptimization = computed(() => !!optimization.value);

const startPolling = () => {
    if (!optimization.value || !isRunning.value) return;

    pollingInterval.value = window.setInterval(async () => {
        try {
            const response = await fetch(`/optimizations/${optimization.value!.id}/status`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    optimization.value = data.optimization;

                    // Si completó o falló, detener polling
                    if (data.status === 'completed' || data.status === 'failed') {
                        stopPolling();
                        stopMessageRotation();

                        if (data.status === 'completed') {
                            // Recargar la página para obtener los resultados completos
                            router.reload();
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error polling optimization status:', error);
        }
    }, 5000); // Cada 5 segundos
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

const startMessageRotation = () => {
    messageInterval.value = window.setInterval(() => {
        currentMessageIndex.value = (currentMessageIndex.value + 1) % loadingMessages.value.length;
    }, 3000); // Cambiar mensaje cada 3 segundos
};

const stopMessageRotation = () => {
    if (messageInterval.value) {
        clearInterval(messageInterval.value);
        messageInterval.value = null;
    }
};

const goToHistorial = () => {
    router.visit('/dashboard/historial');
};

const goToInicio = () => {
    router.visit('/dashboard/inicio');
};

const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatPercentage = (value: number): string => {
    return `${(value * 100).toFixed(2)}%`;
};

const getStatusColor = (status: string): string => {
    switch (status) {
        case 'running': return 'text-yellow-400';
        case 'completed': return 'text-green-400';
        case 'failed': return 'text-red-400';
        default: return 'text-blue-400';
    }
};

const getStatusText = (status: string): string => {
    switch (status) {
        case 'running': return 'Ejecutándose';
        case 'completed': return 'Completado';
        case 'failed': return 'Fallido';
        case 'pending': return 'Pendiente';
        default: return 'Desconocido';
    }
};

onMounted(() => {
    if (isRunning.value) {
        startPolling();
        startMessageRotation();
    }
});

onUnmounted(() => {
    stopPolling();
    stopMessageRotation();
});
</script>

<template>
    <Head title="Resultados - Capital Budgeting" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="mb-6">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-emerald-400 bg-clip-text text-transparent mb-2">
                    Resultados de Optimización
                </h1>
                <p class="text-blue-100/80">
                    Visualiza los resultados del análisis de capital budgeting
                </p>
            </div>

            <!-- Sin optimización -->
            <div v-if="!hasOptimization" class="relative rounded-xl border border-white/20 bg-white/5 backdrop-blur-sm p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Resultados obtenidos</h2>

                <div class="text-center text-blue-200 py-12">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-blue-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Sin resultados disponibles</h3>
                    <p class="text-blue-200/80 mb-6">
                        No hay optimizaciones en ejecución. Crea una nueva optimización o revisa el historial.
                    </p>
                    <div class="flex gap-4 justify-center">
                        <button @click="goToInicio"
                            class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                            Nueva Optimización
                        </button>
                        <button @click="goToHistorial"
                            class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                            Ver Historial
                        </button>
                    </div>
                </div>
            </div>

            <!-- Optimización en ejecución -->
            <div v-else-if="isRunning" class="relative rounded-xl border border-white/20 bg-white/5 backdrop-blur-sm p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Optimización en Progreso</h2>

                <!-- Información de la optimización -->
                <div v-if="optimization" class="mb-8 p-4 bg-white/5 rounded-lg border border-white/10">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-white">{{ optimization.description }}</h3>
                        <span :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusColor(optimization.status)]">
                            {{ getStatusText(optimization.status) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-blue-200">Períodos:</span>
                            <span class="text-white ml-2">{{ optimization.total_periods }}</span>
                        </div>
                        <div>
                            <span class="text-blue-200">Tasa de descuento:</span>
                            <span class="text-white ml-2">{{ formatPercentage(optimization.discount_rate) }}</span>
                        </div>
                        <div>
                            <span class="text-blue-200">Saldo inicial:</span>
                            <span class="text-white ml-2">{{ formatCurrency(optimization.initial_balance) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Loader con mensajes rotativos -->
                <div class="text-center py-12">
                    <div class="mb-6">
                        <!-- Spinner animado -->
                        <div class="inline-flex items-center justify-center w-16 h-16 border-4 border-blue-200 border-t-blue-500 rounded-full animate-spin mb-4"></div>
                    </div>

                    <h3 class="text-xl font-semibold text-white mb-4">Procesando Optimización</h3>

                    <!-- Mensaje rotativo -->
                    <div class="transition-all duration-500 ease-in-out">
                        <p class="text-blue-200 text-lg mb-6">
                            {{ loadingMessages[currentMessageIndex] }}
                        </p>
                    </div>

                    <!-- Barra de progreso indeterminada -->
                    <div class="w-full max-w-md mx-auto">
                        <div class="bg-white/10 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-full rounded-full animate-pulse"></div>
                        </div>
                    </div>

                    <p class="text-blue-200/60 text-sm mt-4">
                        Este proceso puede tomar varios minutos dependiendo de la complejidad del modelo
                    </p>
                </div>
            </div>

            <!-- Optimización completada -->
            <div v-else-if="optimization && isCompleted" class="space-y-6">
                <!-- Header con información general -->
                <div class="relative rounded-xl border border-green-500/20 bg-green-500/5 backdrop-blur-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold text-white">Optimización Completada</h2>
                        <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-sm font-medium">
                            ✓ Completado
                        </span>
                    </div>

                    <h3 class="text-lg text-white mb-2">{{ optimization.description }}</h3>
                    <p class="text-green-200/80 text-sm">
                        Completado el {{ new Date(optimization.completed_at!).toLocaleString('es-AR') }}
                    </p>
                </div>

                <!-- Métricas principales -->
                <div v-if="optimization.result" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white/5 rounded-xl p-6 border border-white/10">
                        <h4 class="text-sm font-medium text-blue-200 mb-2">Valor Presente Neto</h4>
                        <p class="text-3xl font-bold text-green-400">{{ formatCurrency(optimization.result.npv) }}</p>
                    </div>

                    <div class="bg-white/5 rounded-xl p-6 border border-white/10">
                        <h4 class="text-sm font-medium text-blue-200 mb-2">Saldo Final</h4>
                        <p class="text-3xl font-bold text-white">{{ formatCurrency(optimization.result.final_balance) }}</p>
                    </div>

                    <div class="bg-white/5 rounded-xl p-6 border border-white/10">
                        <h4 class="text-sm font-medium text-blue-200 mb-2">Proyectos Seleccionados</h4>
                        <p class="text-3xl font-bold text-purple-400">
                            {{ optimization.result.projects_selected }}
                        </p>
                    </div>
                </div>

                <!-- Proyectos seleccionados -->
                <div  class="bg-white/5 rounded-xl p-6 border border-white/10">
                    <h4 class="text-lg font-semibold text-white mb-4">Proyectos Seleccionados</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left py-3 px-4 text-blue-200 font-medium">Proyecto</th>
                                    <th class="text-left py-3 px-4 text-blue-200 font-medium">Período de Inicio</th>
                                    <th class="text-left py-3 px-4 text-blue-200 font-medium">Costo Setup</th>
                                    <th class="text-left py-3 px-4 text-blue-200 font-medium">Recompensa Total</th>
                                    <th class="text-left py-3 px-4 text-blue-200 font-medium">Contribución NPV</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="project in optimization.selectedProjects" :key="project.project_name"
                                    class="border-b border-white/5">
                                    <td class="py-3 px-4 text-white font-medium">{{ project.project_name }}</td>
                                    <td class="py-3 px-4 text-blue-200">{{ project.start_period }}</td>
                                    <td class="py-3 px-4 text-red-300">{{ formatCurrency(project.setup_cost) }}</td>
                                    <td class="py-3 px-4 text-green-300">{{ formatCurrency(project.total_reward) }}</td>
                                    <td class="py-3 px-4" :class="project.npv_contribution >= 0 ? 'text-green-400' : 'text-red-400'">
                                        {{ formatCurrency(project.npv_contribution) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Flujo de caja por período -->
                <div v-if="optimization.periodCashFlows?.length" class="bg-white/5 rounded-xl p-6 border border-white/10">
                    <h4 class="text-lg font-semibold text-white mb-4">Flujo de Caja por Período</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left py-3 px-4 text-blue-200 font-medium">Período</th>
                                    <th class="text-left py-3 px-4 text-blue-200 font-medium">Entradas</th>
                                    <th class="text-left py-3 px-4 text-blue-200 font-medium">Salidas</th>
                                    <th class="text-left py-3 px-4 text-blue-200 font-medium">Flujo Neto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cashFlow in optimization.periodCashFlows" :key="cashFlow.period"
                                    class="border-b border-white/5">
                                    <td class="py-3 px-4 text-white font-medium">{{ cashFlow.period }}</td>
                                    <td class="py-3 px-4 text-green-300">{{ formatCurrency(cashFlow.cash_in) }}</td>
                                    <td class="py-3 px-4 text-red-300">{{ formatCurrency(cashFlow.cash_out) }}</td>
                                    <td class="py-3 px-4" :class="cashFlow.net_cash_flow >= 0 ? 'text-green-400' : 'text-red-400'">
                                        {{ formatCurrency(cashFlow.net_cash_flow) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Optimización fallida -->
            <div v-else-if="isFailed" class="relative rounded-xl border border-red-500/20 bg-red-500/5 backdrop-blur-sm p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Error en la Optimización</h2>

                <div class="text-center text-red-200 py-12">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">La optimización falló</h3>
                    <p class="text-red-200/80 mb-6">
                        Hubo un error al procesar el modelo. Por favor, revisa los parámetros e intenta nuevamente.
                    </p>
                    <div class="flex gap-4 justify-center">
                        <button @click="goToInicio"
                            class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                            Intentar Nuevamente
                        </button>
                        <button @click="goToHistorial"
                            class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                            Ver Historial
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
