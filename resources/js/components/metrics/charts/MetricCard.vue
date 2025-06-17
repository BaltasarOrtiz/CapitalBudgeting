<script setup lang="ts">
interface Props {
    title: string;
    value: string;
    subtitle?: string;
    trend?: 'positive' | 'negative' | 'neutral';
    icon?: string;
}

withDefaults(defineProps<Props>(), {
    trend: 'neutral'
});

const getTrendColor = (trend: string) => {
    switch (trend) {
        case 'positive': return 'text-green-400';
        case 'negative': return 'text-red-400';
        default: return 'text-blue-400';
    }
};

const getTrendBgColor = (trend: string) => {
    switch (trend) {
        case 'positive': return 'bg-green-500/10 border-green-500/20';
        case 'negative': return 'bg-red-500/10 border-red-500/20';
        default: return 'bg-blue-500/10 border-blue-500/20';
    }
};

const getIcon = (iconName: string) => {
    const icons = {
        'trending-up': 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'dollar-sign': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
        'zap': 'M13 10V3L4 14h7v7l9-11h-7z',
        'bar-chart': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'clock': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    };
    return icons[iconName as keyof typeof icons] || icons['bar-chart'];
};
</script>

<template>
    <div class="rounded-xl p-6 border transition-all duration-200 hover:scale-105"
         :class="getTrendBgColor(trend)">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-blue-200/80 mb-1">{{ title }}</p>
                <p class="text-2xl font-bold text-white mb-1">{{ value }}</p>
                <p v-if="subtitle" class="text-xs text-blue-200/60">{{ subtitle }}</p>
            </div>

            <div v-if="icon" class="ml-4">
                <div class="p-2 rounded-lg" :class="getTrendBgColor(trend)">
                    <svg class="w-5 h-5" :class="getTrendColor(trend)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(icon)"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Indicador de tendencia -->
        <div v-if="trend !== 'neutral'" class="mt-3 flex items-center gap-1">
            <svg v-if="trend === 'positive'" class="w-3 h-3 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
            <svg v-else class="w-3 h-3 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
            </svg>
            <span class="text-xs font-medium" :class="getTrendColor(trend)">
                {{ trend === 'positive' ? 'Positivo' : 'Negativo' }}
            </span>
        </div>
    </div>
</template>
