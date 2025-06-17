<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 42" :class="className" v-bind="$attrs">
    <!-- Gráfico de barras (representando análisis financiero) -->
    <rect x="4" y="28" width="3" height="10" fill="currentColor" opacity="0.8"/>
    <rect x="8" y="24" width="3" height="14" fill="currentColor" opacity="0.9"/>
    <rect x="12" y="20" width="3" height="18" fill="currentColor"/>
    <rect x="16" y="16" width="3" height="22" fill="currentColor"/>

    <!-- Moneda/Símbolo de dinero central -->
    <circle cx="28" cy="15" r="8" fill="none" stroke="currentColor" stroke-width="2"/>
    <path d="M25 15h6M28 12v6M26 13h4M26 17h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>

    <!-- Flecha de crecimiento -->
    <path d="M20 32L32 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M28 20h4v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

    <!-- Elementos decorativos (puntos que representan datos/proyecciones) -->
    <circle cx="6" cy="12" r="1.5" fill="currentColor" opacity="0.6"/>
    <circle cx="10" cy="8" r="1.5" fill="currentColor" opacity="0.7"/>
    <circle cx="14" cy="6" r="1.5" fill="currentColor" opacity="0.8"/>
    <circle cx="18" cy="4" r="1.5" fill="currentColor"/>

    <!-- Base/línea de tiempo -->
    <line x1="2" y1="38" x2="20" y2="38" stroke="currentColor" stroke-width="1" opacity="0.5"/>
</svg>
</template>
