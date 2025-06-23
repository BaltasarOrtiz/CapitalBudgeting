<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Historial',
        href: '/dashboard/historial',
    },
];

// Interfaces
interface OptimizationResult {
    id: number;
    npv: number;
    final_balance: number;
    initial_balance: number;
    total_periods: number;
    total_projects: number;
    projects_selected: number;
    status: string;
}

interface Optimization {
    id: number;
    description: string;
    status: 'pending' | 'running' | 'completed' | 'failed';
    created_at: string;
    completed_at?: string;
    total_periods: number;
    discount_rate: number;
    initial_balance: number;
    nb_must_take_one: number;
    result?: OptimizationResult;
}

interface PaginatedOptimizations {
    data: Optimization[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    has_more_pages: boolean;
    has_pages: boolean;
}

// Props
const props = defineProps<{
    optimizations: PaginatedOptimizations;
}>();

// Estado para el modal
const showModal = ref(false);
const selectedOptimization = ref<Optimization | null>(null);

// Funciones de formato simples
const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleString('es-AR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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
        case 'running': return 'bg-yellow-500/20 text-yellow-400';
        case 'completed': return 'bg-green-500/20 text-green-400';
        case 'failed': return 'bg-red-500/20 text-red-400';
        case 'pending': return 'bg-blue-500/20 text-blue-400';
        default: return 'bg-gray-500/20 text-gray-400';
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

const truncateText = (text: string, maxLength: number = 40): string => {
    if (text.length <= maxLength) return text;
    return text.slice(0, maxLength) + '...';
};

// Función para abrir modal
const openModal = (optimization: Optimization) => {
    selectedOptimization.value = optimization;
    showModal.value = true;
};

// Función para cerrar modal
const closeModal = () => {
    showModal.value = false;
    selectedOptimization.value = null;
};

// Función para navegar entre páginas
const goToPage = (page: number) => {
    if (page >= 1 && page <= props.optimizations.last_page) {
        router.get('/dashboard/historial', { page }, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

// Función para ir a la página anterior
const goToPrevPage = () => {
    if (props.optimizations.current_page > 1) {
        goToPage(props.optimizations.current_page - 1);
    }
};

// Función para ir a la página siguiente
const goToNextPage = () => {
    if (props.optimizations.current_page < props.optimizations.last_page) {
        goToPage(props.optimizations.current_page + 1);
    }
};

// Computeds
const hasOptimizations = computed(() => props.optimizations.data.length > 0);
</script>

<template>

    <Head title="Historial - Capital Budgeting" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="mb-6">
                <h1
                    class="text-3xl font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-emerald-400 bg-clip-text text-transparent mb-2">
                    Historial de Optimizaciones
                </h1>
                <p class="text-blue-100/80">
                    Revisa el historial de modelos ejecutados y sus configuraciones
                </p>
            </div>

            <div class="relative rounded-xl border border-white/20 bg-white/5 backdrop-blur-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">Historial</h2>
                    <div class="text-blue-200/60 text-sm">
                        {{ optimizations.total }} optimizaciones en total
                    </div>
                </div>

                <!-- Sin optimizaciones -->
                <div v-if="!hasOptimizations" class="text-center text-blue-200 py-12">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-blue-300 mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Sin optimizaciones</h3>
                    <p class="text-blue-200/80">
                        Aún no has ejecutado ninguna optimización. Ve a Inicio para crear una nueva.
                    </p>
                </div>

                <!-- Tabla de optimizaciones -->
                <div v-else class="bg-white/5 rounded-lg overflow-hidden border border-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-white/10">
                                <tr>
                                    <th class="px-6 py-4 text-left text-blue-200 font-medium">Fecha/Hora</th>
                                    <th class="px-6 py-4 text-left text-blue-200 font-medium">Descripción</th>
                                    <th class="px-6 py-4 text-left text-blue-200 font-medium">Estado</th>
                                    <th class="px-6 py-4 text-left text-blue-200 font-medium">Parámetros</th>
                                    <th class="px-6 py-4 text-left text-blue-200 font-medium">Resultados</th>
                                    <th class="px-6 py-4 text-left text-blue-200 font-medium">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="optimization in optimizations.data" :key="optimization.id"
                                    class="border-t border-white/10 hover:bg-white/5 transition-colors">

                                    <!-- Fecha/Hora -->
                                    <td class="px-6 py-4">
                                        <div class="text-blue-200 text-sm">
                                            {{ formatDate(optimization.created_at) }}
                                        </div>
                                        <div v-if="optimization.completed_at" class="text-blue-300/60 text-xs mt-1">
                                            Completado: {{ formatDate(optimization.completed_at) }}
                                        </div>
                                    </td>

                                    <!-- Descripción -->
                                    <td class="px-6 py-4">
                                        <div class="text-white font-medium" :title="optimization.description">
                                            {{ truncateText(optimization.description || 'Sin descripción', 40) }}
                                        </div>
                                        <div class="text-blue-200/60 text-xs mt-1">
                                            ID: #{{ optimization.id }}
                                        </div>
                                    </td>

                                    <!-- Estado -->
                                    <td class="px-6 py-4">
                                        <span
                                            :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusColor(optimization.status)]">
                                            {{ getStatusText(optimization.status) }}
                                        </span>
                                    </td>

                                    <!-- Parámetros -->
                                    <td class="px-6 py-4">
                                        <div class="text-blue-200 text-sm space-y-1">
                                            <div>Periodo: {{ optimization.total_periods }}</div>
                                            <div>Tasa de Descuento: {{ formatPercentage(optimization.discount_rate) }}</div>
                                            <div>Balance Inicial: {{ formatCurrency(optimization.initial_balance) }}</div>
                                            <div v-if="optimization.nb_must_take_one > 0" class="text-blue-300/60">
                                                Grupos: {{ optimization.nb_must_take_one }}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Resultados -->
                                    <td class="px-6 py-4">
                                        <div v-if="optimization.result" class="text-sm space-y-1">
                                            <div class="text-green-400 font-medium">
                                                VAN: {{ formatCurrency(optimization.result.npv) }}
                                            </div>
                                            <div class="text-blue-200">
                                                {{ optimization.result.projects_selected }}/{{
                                                optimization.result.total_projects }} proyectos
                                            </div>
                                            <div class="text-blue-300/60">
                                                Final: {{ formatCurrency(optimization.result.final_balance) }}
                                            </div>
                                        </div>
                                        <div v-else class="text-gray-400 text-sm">
                                            Sin resultados
                                        </div>
                                    </td>

                                    <!-- Acciones -->
                                    <td class="px-6 py-4">
                                        <button @click="openModal(optimization)"
                                            class="text-blue-300 cursor-pointer hover:text-white transition-colors text-sm font-medium">
                                            Ver Detalles
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación simple -->
                    <div class="border-t border-white/10 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <!-- Información -->
                            <div class="text-sm text-blue-200">
                                <span v-if="optimizations.from && optimizations.to">
                                    Mostrando {{ optimizations.from }} a {{ optimizations.to }} de {{
                                    optimizations.total }} optimizaciones
                                </span>
                                <span v-else>
                                    {{ optimizations.total }} optimizaciones en total
                                </span>
                            </div>

                            <!-- Navegación simple -->
                            <div v-if="optimizations.has_pages" class="flex items-center space-x-4">
                                <button @click="goToPrevPage" :disabled="optimizations.current_page === 1"
                                    class="flex cursor-pointer items-center space-x-2 px-4 py-2 text-sm font-medium text-blue-200 bg-white/5 border border-white/10 rounded-lg hover:bg-white/10 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    <span>Anterior</span>
                                </button>

                                <span class="text-sm text-blue-200">
                                    Página {{ optimizations.current_page }} de {{ optimizations.last_page }}
                                </span>

                                <button @click="goToNextPage"
                                    :disabled="optimizations.current_page === optimizations.last_page"
                                    class="flex cursor-pointer items-center space-x-2 px-4 py-2 text-sm font-medium text-blue-200 bg-white/5 border border-white/10 rounded-lg hover:bg-white/10 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                    <span>Siguiente</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalles -->
        <div v-if="showModal"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-gray-900 border border-white/20 rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Header del Modal -->
                <div class="flex items-center justify-between p-6 border-b border-white/10">
                    <h3 class="text-xl font-bold text-white">Detalles de la Optimización</h3>
                    <button @click="closeModal" class="text-gray-400 cursor-pointer hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Contenido del Modal -->
                <div v-if="selectedOptimization" class="p-6 space-y-6">
                    <!-- Información General -->
                    <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                        <h4 class="text-lg font-semibold text-white mb-3">Información General</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-blue-200">ID:</span>
                                <span class="text-white ml-2">#{{ selectedOptimization.id }}</span>
                            </div>
                            <div>
                                <span class="text-blue-200">Estado:</span>
                                <span
                                    :class="['ml-2 px-2 py-1 rounded text-xs font-medium', getStatusColor(selectedOptimization.status)]">
                                    {{ getStatusText(selectedOptimization.status) }}
                                </span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-blue-200">Descripción:</span>
                                <span class="text-white ml-2">{{ selectedOptimization.description || 'Sin descripción'
                                    }}</span>
                            </div>
                            <div>
                                <span class="text-blue-200">Creado:</span>
                                <span class="text-white ml-2">{{ formatDate(selectedOptimization.created_at) }}</span>
                            </div>
                            <div v-if="selectedOptimization.completed_at">
                                <span class="text-blue-200">Completado:</span>
                                <span class="text-white ml-2">{{ formatDate(selectedOptimization.completed_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Parámetros del Modelo -->
                    <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                        <h4 class="text-lg font-semibold text-white mb-3">Parámetros del Modelo</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-blue-200">Períodos:</span>
                                <span class="text-white ml-2">{{ selectedOptimization.total_periods }}</span>
                            </div>
                            <div>
                                <span class="text-blue-200">Tasa descuento:</span>
                                <span class="text-white ml-2">{{ formatPercentage(selectedOptimization.discount_rate)
                                    }}</span>
                            </div>
                            <div>
                                <span class="text-blue-200">Saldo inicial:</span>
                                <span class="text-white ml-2">{{ formatCurrency(selectedOptimization.initial_balance)
                                    }}</span>
                            </div>
                            <div>
                                <span class="text-blue-200">Grupos exclusivos:</span>
                                <span class="text-white ml-2">{{ selectedOptimization.nb_must_take_one }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Resultados -->
                    <div v-if="selectedOptimization.result" class="bg-white/5 rounded-lg p-4 border border-white/10">
                        <h4 class="text-lg font-semibold text-white mb-3">Resultados de la Optimización</h4>

                        <!-- Métricas principales -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-green-400">{{
                                    formatCurrency(selectedOptimization.result.npv) }}</div>
                                <div class="text-green-200 text-sm">Valor Actual Neto</div>
                            </div>
                            <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-blue-400">{{
                                    formatCurrency(selectedOptimization.result.final_balance) }}</div>
                                <div class="text-blue-200 text-sm">Saldo Final</div>
                            </div>
                            <div class="bg-purple-500/10 border border-purple-500/20 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-purple-400">
                                    {{ selectedOptimization.result.projects_selected }}/{{
                                    selectedOptimization.result.total_projects }}
                                </div>
                                <div class="text-purple-200 text-sm">Proyectos Seleccionados</div>
                            </div>
                        </div>

                        <!-- Detalles adicionales -->
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-blue-200">Saldo inicial:</span>
                                <span class="text-white ml-2">{{
                                    formatCurrency(selectedOptimization.result.initial_balance) }}</span>
                            </div>
                            <div>
                                <span class="text-blue-200">Estado del modelo:</span>
                                <span class="text-white ml-2">{{ selectedOptimization.result.status }}</span>
                            </div>
                            <div>
                                <span class="text-blue-200">Eficiencia:</span>
                                <span class="text-white ml-2">
                                    {{ ((selectedOptimization.result.projects_selected /
                                        selectedOptimization.result.total_projects) * 100).toFixed(1) }}%
                                </span>
                            </div>
                            <div>
                                <span class="text-blue-200">Ganancia neta:</span>
                                <span class="text-white ml-2">
                                    {{ formatCurrency(selectedOptimization.result.final_balance -
                                    selectedOptimization.result.initial_balance) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Sin resultados -->
                    <div v-else class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-4 text-center">
                        <div class="text-yellow-400 text-lg font-medium mb-2">Sin Resultados</div>
                        <div class="text-yellow-200/80 text-sm">
                            Esta optimización aún no ha completado su ejecución o falló durante el proceso.
                        </div>
                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="border-t border-white/10 px-6 py-4">
                    <div class="flex justify-end">
                        <button @click="closeModal"
                            class="px-4 py-2 cursor-pointer bg-gray-600 hover:bg-gray-700 text-white rounded transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
