<script setup lang="ts">
import { computed } from 'vue';
import BarChart from '@/components/metrics/charts/BarChart.vue';
import LineChart from '@/components/metrics/charts/LineChart.vue';
import MetricCard from '@/components/metrics/charts/MetricCard.vue';

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

interface SelectedProject {
    id: number;
    project_name: string;
    start_period: number;
    setup_cost: number;
    total_reward: number;
    npv_contribution: number;
}

interface PeriodBalance {
    id: number;
    period: number;
    balance: number;
    discounted_balance: number;
}

interface PeriodCashFlow {
    id: number;
    period: number;
    cash_in: number;
    cash_out: number;
    net_cash_flow: number;
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
    url_status?: string;
    result?: OptimizationResult;
    selected_projects?: SelectedProject[];
    period_balances?: PeriodBalance[];
    period_cash_flows?: PeriodCashFlow[];
}

const props = defineProps<{
    optimization: Optimization;
}>();

const emit = defineEmits<{
    close: [];
}>();

// Datos computados para gráficos
const projectPerformanceData = computed(() => {
    if (!props.optimization.selected_projects) return [];

    return props.optimization.selected_projects.map(project => ({
        label: project.project_name,
        value: project.npv_contribution,
        cost: project.setup_cost,
        reward: project.total_reward,
    }));
});

const cashFlowData = computed(() => {
    if (!props.optimization.period_cash_flows) return [];

    return props.optimization.period_cash_flows.map(flow => ({
        period: `Período ${flow.period}`,
        cashIn: flow.cash_in,
        cashOut: flow.cash_out,
        netFlow: flow.net_cash_flow
    }));
});

const balanceEvolutionData = computed(() => {
    if (!props.optimization.period_balances) return [];

    return props.optimization.period_balances.map(balance => ({
        period: `Período ${balance.period}`,
        balance: balance.balance,
        discountedBalance: balance.discounted_balance
    }));
});
// Métricas avanzadas computadas
const advancedMetrics = computed(() => {
    const result = props.optimization.result;
    const projects = props.optimization.selected_projects || [];
    console.log('Projects:', projects);

    if (!result) return null;

    const totalInvestment = projects.reduce((sum, p) => sum + Number(p.setup_cost), 0);
    const totalRewards = projects.reduce((sum, p) => sum + Number(p.total_reward), 0);
    const avgProjectValue = projects.length > 0 ? result.npv / projects.length : 0;
    const capitalEfficiency = result.initial_balance > 0 ? (result.npv / result.initial_balance) * 100 : 0;

    // Calcular payback period promedio
    const avgPaybackPeriod = projects.length > 0 ?
        projects.reduce((sum, p) => {
            const payback = p.total_reward > 0 ? p.setup_cost / (p.total_reward / props.optimization.total_periods) : 0;
            return sum + payback;
        }, 0) / projects.length : 0;

    return {
        totalInvestment,
        totalRewards,
        avgProjectValue,
        capitalEfficiency,
        avgPaybackPeriod,
        netProfit: totalRewards - totalInvestment,
        investmentRatio: result.total_projects > 0 ? (result.projects_selected / result.total_projects) * 100 : 0
    };
});

const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatPercentage = (value: number): string => {
    return `${value.toFixed(1)}%`;
};

const formatNumber = (value: number): string => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }).format(value);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header del Dashboard -->
        <div class="flex items-center justify-between p-6 bg-white/5 rounded-xl border border-white/10">
            <div>
                <h3 class="text-2xl font-bold text-white mb-2">Métricas Avanzadas</h3>
                <p class="text-blue-200/80">Análisis detallado del rendimiento de la optimización</p>
            </div>
            <button
                @click="emit('close')"
                class="p-2 hover:bg-white/10 rounded-lg transition-colors text-blue-200 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Métricas KPI -->
        <div v-if="advancedMetrics" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <MetricCard
                title="Inversión Total"
                :value="formatCurrency(advancedMetrics.totalInvestment)"
                :subtitle="`En ${optimization.result?.projects_selected} proyectos`"
                trend="neutral"
                icon="credit-card"
            />

            <MetricCard
                title="Ganancia Neta"
                :value="formatCurrency(advancedMetrics.netProfit)"
                :subtitle="`Recompensas - Costos`"
                :trend="advancedMetrics.netProfit > 0 ? 'positive' : 'negative'"
                icon="dollar-sign"
            />

            <MetricCard
                title="Eficiencia de Capital"
                :value="formatPercentage(advancedMetrics.capitalEfficiency)"
                :subtitle="`VPN vs Capital Inicial`"
                :trend="advancedMetrics.capitalEfficiency > 0 ? 'positive' : 'negative'"
                icon="zap"
            />
        </div>

        <!-- Métricas secundarias -->
        <div v-if="advancedMetrics" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
            <MetricCard
                title="Valor Promedio/Proyecto"
                :value="formatCurrency(advancedMetrics.avgProjectValue)"
                :subtitle="`VPN distribuido`"
                trend="neutral"
                icon="bar-chart"
            />

            <MetricCard
                title="Período de Recuperación"
                :value="`${formatNumber(advancedMetrics.avgPaybackPeriod)} períodos`"
                :subtitle="`Promedio estimado`"
                trend="neutral"
                icon="clock"
            />

            <MetricCard
                title="Tasa de Selección"
                :value="formatPercentage(advancedMetrics.investmentRatio)"
                :subtitle="`Proyectos ejecutados`"
                trend="neutral"
                icon="check-circle"
            />
        </div>

        <!-- Gráficos -->
        <div class="grid grid-cols-1  gap-6">
            <!-- Rendimiento por Proyecto -->
            <div class="bg-white/5 rounded-xl p-6 border border-white/10">
                <h4 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Contribución VAN por Proyecto
                </h4>
                <BarChart
                    :data="projectPerformanceData"
                    :height="300"
                    color="#3B82F6"
                    value-format="currency"
                />
            </div>
        </div>

        <!-- Gráficos de flujo temporal -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Flujo de Caja -->
            <div class="bg-white/5 rounded-xl p-6 border border-white/10">
                <h4 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    Evolución del Flujo de Caja
                </h4>
                <LineChart
                    :data="cashFlowData"
                    :height="300"
                    :datasets="[
                        { key: 'cashIn', label: 'Entradas', color: '#10B981' },
                        { key: 'cashOut', label: 'Salidas', color: '#EF4444' },
                        { key: 'netFlow', label: 'Flujo Neto', color: '#3B82F6' }
                    ]"
                    value-format="currency"
                />
            </div>

            <!-- Evolución de Saldos -->
            <div class="bg-white/5 rounded-xl p-6 border border-white/10">
                <h4 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    Evolución de Saldos
                </h4>
                <LineChart
                    :data="balanceEvolutionData"
                    :height="300"
                    :datasets="[
                        { key: 'balance', label: 'Saldo Nominal', color: '#3B82F6' },
                        { key: 'discountedBalance', label: 'Saldo Descontado', color: '#8B5CF6' }
                    ]"
                    value-format="currency"
                />
            </div>
        </div>

        <!-- Análisis de Riesgo y Rendimiento -->
        <div class="bg-white/5 rounded-xl p-6 border border-white/10">
            <h4 class="text-lg font-semibold text-white mb-4">Análisis de Riesgo y Rendimiento por Proyecto</h4>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left py-3 px-4 text-blue-200 font-medium">Proyecto</th>
                            <th class="text-left py-3 px-4 text-blue-200 font-medium">Inversión</th>
                            <th class="text-left py-3 px-4 text-blue-200 font-medium">Retorno Total</th>
                            <th class="text-left py-3 px-4 text-blue-200 font-medium">VAN Contribución</th>
                            <th class="text-left py-3 px-4 text-blue-200 font-medium">Período Inicio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="project in projectPerformanceData" :key="project.label"
                            class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3 px-4 text-white font-medium">{{ project.label }}</td>
                            <td class="py-3 px-4 text-red-300">{{ formatCurrency(project.cost) }}</td>
                            <td class="py-3 px-4 text-green-300">{{ formatCurrency(project.reward) }}</td>
                            <td class="py-3 px-4" :class="project.value >= 0 ? 'text-green-400' : 'text-red-400'">
                                {{ formatCurrency(project.value) }}
                            </td>
                            <td class="py-3 px-4 text-blue-200">
                                {{ optimization.selected_projects?.find(p => p.project_name === project.label)?.start_period }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Resumen Ejecutivo -->
        <div class="bg-gradient-to-r from-blue-500/10 to-purple-500/10 rounded-xl p-6 border border-blue-500/20">
            <h4 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Resumen Ejecutivo
            </h4>

            <div v-if="advancedMetrics && optimization.result" class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <h5 class="font-medium text-blue-200 mb-3">Resultados Financieros</h5>
                    <ul class="space-y-2 text-blue-100">
                        <li>• VPN generado: <span class="font-medium text-green-400">{{ formatCurrency(optimization.result.npv) }}</span></li>
                        <li>• Inversión total: <span class="font-medium text-white">{{ formatCurrency(advancedMetrics.totalInvestment) }}</span></li>
                        <li>• Eficiencia de capital: <span class="font-medium text-blue-400">{{ formatPercentage(advancedMetrics.capitalEfficiency) }}</span></li>
                    </ul>
                </div>

                <div>
                    <h5 class="font-medium text-blue-200 mb-3">Decisiones de Cartera</h5>
                    <ul class="space-y-2 text-blue-100">
                        <li>• Proyectos seleccionados: <span class="font-medium text-white">{{ optimization.result.projects_selected }} de {{ optimization.result.total_projects }}</span></li>
                        <li>• Tasa de selección: <span class="font-medium text-purple-400">{{ formatPercentage(advancedMetrics.investmentRatio) }}</span></li>
                        <li>• Horizonte temporal: <span class="font-medium text-white">{{ optimization.total_periods }} períodos</span></li>
                        <li>• Estado de optimización: <span class="font-medium text-green-400">{{ optimization.result.status }}</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
