<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-gradient-to-br from-gray-900 via-gray-800 to-slate-900 p-6 md:p-10 text-gray-100">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                        <div class="mb-1 flex h-12 w-12 items-center justify-center rounded-md bg-gradient-to-r from-gray-600 via-gray-500 to-gray-400 p-0.5">
                            <div class="flex h-full w-full items-center justify-center rounded-md bg-gray-900">
                                <AppLogoIcon class="size-8 fill-current text-gray-100" />
                            </div>
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium text-gray-100">{{ title }}</h1>
                        <p class="text-center text-sm text-gray-300/80">{{ description }}</p>
                    </div>
                </div>

                <!-- Contenedor del formulario con fondo semi-transparente -->
                <div class="rounded-lg bg-gray-800/30 backdrop-blur-sm border border-gray-600/30 p-6 shadow-xl">
                    <slot />
                </div>
            </div>
        </div>

        <!-- Elementos decorativos de fondo -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Círculos decorativos -->
            <div class="absolute top-20 left-10 w-32 h-32 bg-gray-700/10 rounded-full blur-xl"></div>
            <div class="absolute bottom-20 right-10 w-40 h-40 bg-gray-600/10 rounded-full blur-xl"></div>
            <div class="absolute top-1/2 left-1/4 w-24 h-24 bg-gray-500/10 rounded-full blur-xl"></div>

            <!-- Líneas decorativas -->
            <div class="absolute top-1/3 right-1/4 w-px h-32 bg-gradient-to-b from-transparent via-gray-500/20 to-transparent"></div>
            <div class="absolute bottom-1/3 left-1/3 w-32 h-px bg-gradient-to-r from-transparent via-gray-500/20 to-transparent"></div>
        </div>

        <!-- Iconos financieros decorativos en las esquinas -->
        <div class="absolute top-8 right-8 opacity-15">
            <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
            </svg>
        </div>

        <div class="absolute bottom-8 left-8 opacity-15">
            <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
            </svg>
        </div>
    </div>
</template>

<style scoped>
/* Estilos personalizados para los elementos del formulario */
:deep(.text-muted-foreground) {
    color: rgb(156 163 175 / 0.8) !important;
}

:deep(input) {
    background-color: rgba(75, 85, 99, 0.3) !important;
    border-color: rgba(156, 163, 175, 0.3) !important;
    color: rgb(243, 244, 246) !important;
}

:deep(input::placeholder) {
    color: rgba(156, 163, 175, 0.6) !important;
}

:deep(input:focus) {
    border-color: rgba(107, 114, 128, 0.6) !important;
    box-shadow: 0 0 0 2px rgba(107, 114, 128, 0.2) !important;
}

:deep(label) {
    color: rgb(243, 244, 246) !important;
}

:deep(button) {
    background: linear-gradient(to right, rgb(75, 85, 99), rgb(107, 114, 128), rgb(156, 163, 175)) !important;
    border: none !important;
    color: white !important;
}

:deep(button:hover) {
    background: linear-gradient(to right, rgb(55, 65, 81), rgb(75, 85, 99), rgb(107, 114, 128)) !important;
}

:deep(button:disabled) {
    background: rgba(156, 163, 175, 0.3) !important;
    color: rgba(156, 163, 175, 0.6) !important;
}

:deep(a) {
    color: rgb(209, 213, 219) !important;
}

:deep(a:hover) {
    color: rgb(243, 244, 246) !important;
}

:deep(.text-green-600) {
    color: rgb(156, 163, 175) !important;
}

/* Estilos para checkbox */
:deep([data-state="checked"]) {
    background-color: rgb(107, 114, 128) !important;
    border-color: rgb(107, 114, 128) !important;
}
</style>
