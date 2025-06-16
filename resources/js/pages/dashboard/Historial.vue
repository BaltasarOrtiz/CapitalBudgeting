<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

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

interface HistorialItem {
    fecha: string;
    parametros: string;
    resumen: string;
}

const historial = ref<HistorialItem[]>([
    {
        fecha: '2024-01-15 10:30',
        parametros: 'T: 3, Rate: 0.04, InitBal: 5000',
        resumen: '5 proyectos, 3 períodos'
    },
    {
        fecha: '2024-01-14 14:45',
        parametros: 'T: 4, Rate: 0.05, InitBal: 8000',
        resumen: '3 proyectos, 4 períodos'
    },
    {
        fecha: '2024-01-13 09:15',
        parametros: 'T: 2, Rate: 0.03, InitBal: 3000',
        resumen: '4 proyectos, 2 períodos'
    }
]);
</script>

<template>
    <Head title="Historial - Capital Budgeting" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="mb-6">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-emerald-400 bg-clip-text text-transparent mb-2">
                    Historial de Optimizaciones
                </h1>
                <p class="text-blue-100/80">
                    Revisa el historial de modelos ejecutados y sus configuraciones
                </p>
            </div>

            <div class="relative rounded-xl border border-white/20 bg-white/5 backdrop-blur-sm p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Historial</h2>

                <div class="bg-white/5 rounded-lg overflow-hidden border border-white/10">
                    <table class="w-full">
                        <thead class="bg-white/10">
                            <tr>
                                <th class="px-6 py-4 text-left text-blue-200">Fecha/Hora</th>
                                <th class="px-6 py-4 text-left text-blue-200">Parámetros</th>
                                <th class="px-6 py-4 text-left text-blue-200">Resumen de Datos</th>
                                <th class="px-6 py-4 text-left text-blue-200">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in historial" :key="index" class="border-t border-white/10 hover:bg-white/5">
                                <td class="px-6 py-4 text-blue-200">{{ item.fecha }}</td>
                                <td class="px-6 py-4 text-blue-200">{{ item.parametros }}</td>
                                <td class="px-6 py-4 text-blue-200">{{ item.resumen }}</td>
                                <td class="px-6 py-4">
                                    <button class="text-blue-300 hover:text-white transition-colors">
                                        Ver Detalles
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
