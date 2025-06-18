<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { type SharedData, type User } from '@/types';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user as User);
</script>

<template>

	<Head title="Capital Budgeting">
		<link rel="preconnect" href="https://rsms.me/" />
		<link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
	</Head>
	<div
		class="flex min-h-screen flex-col items-center bg-gradient-to-br from-gray-900 via-gray-800 to-slate-900 p-6 text-gray-100 lg:justify-center lg:p-8">
		<!-- Contenido principal -->
		<div class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow">
			<main class="flex w-full max-w-4xl flex-col items-center justify-center text-center px-6">
				<!-- Título principal -->
				<h1
					class="mb-6 text-5xl lg:text-7xl font-bold">
					Capital Budgeting
				</h1>

				<!-- Descripción -->
				<p class="mb-8 text-lg lg:text-xl text-gray-300 max-w-3xl leading-relaxed">
					Toma decisiones de inversión inteligentes con nuestras herramientas avanzadas de presupuesto de
					capitales.
					Analiza proyectos, evalúa riesgos y maximiza el valor de tu empresa con métodos financieros probados.
				</p>

				<!-- Iconos financieros decorativos -->
				<div class="flex items-center justify-center gap-8 mb-8 opacity-50">
					<!-- Icono de gráfico -->
					<svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
						<path
							d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
					</svg>

					<!-- Icono de dinero -->
					<svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
						<path
							d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
						<path fill-rule="evenodd"
							d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"
							clip-rule="evenodd" />
					</svg>

					<!-- Icono de calculadora -->
					<svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd"
							d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm1 2a1 1 0 000 2h6a1 1 0 100-2H7zm6 7a1 1 0 112 0v3a1 1 0 11-2 0v-3zm-3 3a1 1 0 100 2h.01a1 1 0 100-2H10zm-4 1a1 1 0 011-1h.01a1 1 0 110 2H7a1 1 0 01-1-1zm1-4a1 1 0 100 2h.01a1 1 0 100-2H7zm2 0a1 1 0 100 2h.01a1 1 0 100-2H9zm4 0a1 1 0 100 2h.01a1 1 0 100-2H13zm-6-7a1 1 0 011-1h.01a1 1 0 110 2H8a1 1 0 01-1-1zm1 0a1 1 0 100 2h.01a1 1 0 100-2H9zm4 0a1 1 0 100 2h.01a1 1 0 100-2H13z"
							clip-rule="evenodd" />
					</svg>
				</div>

				<!-- Botones de Login y Register centrados y uno debajo del otro -->
				<div v-if="!user">
					<div class="flex flex-col items-center space-y-4">
						<Link :href="route('login')"
							class="inline-block rounded-sm border px-8 py-2 text-sm leading-normal text-gray-100 border-gray-600 hover:bg-gray-400/50 transition-colors">
						Iniciar sesión
						</Link>
						<Link :href="route('register')"
							class="inline-block rounded-sm border px-8 py-2 text-sm leading-normal text-gray-100 border-gray-600 hover:bg-gray-400/50 transition-colors">
						Registrarse
						</Link>
					</div>
				</div>
                <div v-else>
                    <p class="text-lg text-gray-300 mb-4">Bienvenido, {{ user.name }}!</p>
                    <Link :href="route('dashboard.inicio')"
                        class="inline-block rounded-sm border px-8 py-2 text-sm leading-normal text-gray-100 border-gray-600 hover:bg-gray-200/50 transition-colors">
                    Ir al Dashboard
                    </Link>
                </div>
				<!-- Elementos decorativos de fondo -->
				<div class="absolute inset-0 overflow-hidden pointer-events-none">
					<!-- Círculos decorativos -->
					<div class="absolute top-20 left-10 w-32 h-32 bg-gray-700/10 rounded-full blur-xl"></div>
					<div class="absolute bottom-20 right-10 w-40 h-40 bg-gray-600/10 rounded-full blur-xl"></div>
					<div class="absolute top-1/2 left-1/4 w-24 h-24 bg-gray-500/10 rounded-full blur-xl"></div>

					<!-- Líneas decorativas -->
					<div
						class="absolute top-1/3 right-1/4 w-px h-32 bg-gradient-to-b from-transparent via-gray-500/20 to-transparent">
					</div>
					<div
						class="absolute bottom-1/3 left-1/3 w-32 h-px bg-gradient-to-r from-transparent via-gray-500/20 to-transparent">
					</div>
				</div>
			</main>
		</div>
	</div>
</template>
