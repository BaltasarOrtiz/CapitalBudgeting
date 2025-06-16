<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

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

            <div class="relative rounded-xl border border-white/20 bg-white/5 backdrop-blur-sm p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Resultados obtenidos</h2>

                <div class="text-center text-blue-200 py-12">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-blue-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Sin resultados disponibles</h3>
                    <p class="text-blue-200/80">
                        Los resultados se mostrarán aquí después de procesar el modelo de optimización.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
