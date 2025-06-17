<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Inicio',
        href: '/dashboard/inicio',
    },
];

const currentStep = ref<number>(1);
const isSubmitting = ref<boolean>(false);

// Interfaces simplificadas
interface GlobalParams {
    numPeriodos: string;
    tasaDescuento: string;
    saldoInicial: string;
    nbMustTakeOne: string;
    description?: string;
}

interface Project {
    nombre: string;
    costoBase: number;
    recompensaBase: number;
}

interface MinBalance {
    periodo: number;
    saldo: number;
}

interface ProjectGroup {
    id: number;
    name: string;
    projects: string[];
}

interface ProjectCost {
    periodo: number;
    costo: number;
}

interface ProjectReward {
    periodo: number;
    recompensa: number;
}

// Estado para parámetros globales
const globalParams = reactive<GlobalParams>({
    numPeriodos: '',
    tasaDescuento: '',
    saldoInicial: '',
    nbMustTakeOne: '',
    description: ''
});

// Proyectos con sus valores base
const projects = ref<Project[]>([
    { nombre: 'IBM_5500', costoBase: 4000, recompensaBase: 2000 },
    { nombre: 'Sun_2000', costoBase: 4500, recompensaBase: 2500 },
    { nombre: 'New_CFO', costoBase: 8000, recompensaBase: 2000 },
    { nombre: 'Promote_SW_Reg', costoBase: 2000, recompensaBase: 4000 },
    { nombre: 'Stock_repur', costoBase: 4000, recompensaBase: 2000 }
]);

// Estado para nuevo proyecto
const newProject = reactive<Project>({
    nombre: '',
    costoBase: 0,
    recompensaBase: 0
});

const showAddProject = ref<boolean>(false);

// Estado para grupos
const numGroups = ref<string>('');
const groups = ref<ProjectGroup[]>([]);
const selectedProjects = ref<Record<string, number>>({});

// Estado para saldos mínimos
const minBalances = ref<MinBalance[]>([]);

// Estado para costos y recompensas dinámicas
const projectCosts = ref<Record<string, ProjectCost[]>>({});
const projectRewards = ref<Record<string, ProjectReward[]>>({});

// Computed para validar parámetros globales
const areGlobalParamsValid = computed(() => {
    return globalParams.numPeriodos &&
        globalParams.tasaDescuento &&
        globalParams.saldoInicial;
});

// Computed para obtener lista de proyectos seleccionados
const selectedProjectsList = computed(() => {
    return Object.keys(selectedProjects.value);
});

// Watch para actualizar saldos mínimos cuando cambia el número de períodos
watch(() => globalParams.numPeriodos, (newValue) => {
    if (newValue) {
        const periods = parseInt(newValue);
        minBalances.value = Array.from({ length: periods }, (_, i) => ({
            periodo: i + 1,
            saldo: 0
        }));
    }
});

// Manejar cambio en parámetros globales
const handleGlobalParamChange = (field: keyof GlobalParams, value: string) => {
    globalParams[field] = value;

    // Si todos los parámetros están completos, avanzar al paso 2
    if (areGlobalParamsValid.value) {
        currentStep.value = 2;
    }
};

// Agregar nuevo proyecto
const addNewProject = () => {
    if (!newProject.nombre.trim() || newProject.costoBase <= 0 || newProject.recompensaBase <= 0) {
        alert('Todos los campos son obligatorios y los valores deben ser mayores a 0');
        return;
    }

    // Verificar que no exista ya
    if (projects.value.some(p => p.nombre === newProject.nombre.trim())) {
        alert('Este proyecto ya existe');
        return;
    }

    projects.value.push({
        nombre: newProject.nombre.trim(),
        costoBase: newProject.costoBase,
        recompensaBase: newProject.recompensaBase
    });

    // Limpiar formulario
    newProject.nombre = '';
    newProject.costoBase = 0;
    newProject.recompensaBase = 0;
    showAddProject.value = false;
};

// Eliminar proyecto
const removeProject = (projectName: string) => {
    // Verificar si está siendo usado en algún grupo
    const isUsed = Object.keys(selectedProjects.value).includes(projectName);
    if (isUsed) {
        alert('No se puede eliminar un proyecto que está siendo usado en un grupo');
        return;
    }

    projects.value = projects.value.filter(p => p.nombre !== projectName);
};

// Editar proyecto en línea
const editProject = (index: number, field: keyof Project, value: string | number) => {
    if (field === 'nombre') {
        projects.value[index][field] = value as string;
    } else {
        projects.value[index][field] = typeof value === 'string' ? parseFloat(value) || 0 : value;
    }
};

// Inicializar grupos
const initializeGroups = () => {
    if (!numGroups.value) return;

    const groupsArray: ProjectGroup[] = Array.from({ length: parseInt(numGroups.value) }, (_, i) => ({
        id: i + 1,
        name: `Grupo ${i + 1}`,
        projects: []
    }));

    groups.value = groupsArray;
    selectedProjects.value = {};

    // Actualizar nbMustTakeOne automáticamente
    globalParams.nbMustTakeOne = numGroups.value;

    currentStep.value = 3;
};

// Manejar selección de proyectos en grupos
const handleProjectSelection = (groupId: number, projectName: string, isSelected: boolean) => {
    if (isSelected) {
        // Agregar proyecto al grupo
        groups.value = groups.value.map(group =>
            group.id === groupId
                ? { ...group, projects: [...group.projects, projectName] }
                : group
        );

        // Marcar proyecto como seleccionado
        selectedProjects.value = {
            ...selectedProjects.value,
            [projectName]: groupId
        };
    } else {
        // Remover proyecto del grupo
        groups.value = groups.value.map(group =>
            group.id === groupId
                ? { ...group, projects: group.projects.filter(p => p !== projectName) }
                : group
        );

        // Desmarcar proyecto
        const newSelected = { ...selectedProjects.value };
        delete newSelected[projectName];
        selectedProjects.value = newSelected;
    }
};

// Generar tablas dinámicas usando TODOS los proyectos, no solo los seleccionados
const generateDynamicTables = () => {
    // CAMBIO: Usar todos los proyectos disponibles, no solo los seleccionados
    const allProjectsList = projects.value.map(p => p.nombre); // TODOS los proyectos
    const periods = parseInt(globalParams.numPeriodos);

    // Inicializar costos usando los valores base de TODOS los proyectos
    const costs: Record<string, ProjectCost[]> = {};
    allProjectsList.forEach(projectName => { // CAMBIO: allProjectsList en lugar de selectedList
        const project = projects.value.find(p => p.nombre === projectName);
        const baseCost = project?.costoBase || 0;

        costs[projectName] = Array.from({ length: periods }, (_, i) => ({
            periodo: i + 1,
            costo: baseCost // Usar el costo base del proyecto
        }));
    });
    projectCosts.value = costs;

    // Inicializar recompensas usando los valores base de TODOS los proyectos
    const rewards: Record<string, ProjectReward[]> = {};
    allProjectsList.forEach(projectName => { // CAMBIO: allProjectsList en lugar de selectedList
        const project = projects.value.find(p => p.nombre === projectName);
        const baseReward = project?.recompensaBase || 0;

        rewards[projectName] = Array.from({ length: periods }, (_, i) => ({
            periodo: i + 1,
            recompensa: baseReward // Usar la recompensa base del proyecto
        }));
    });
    projectRewards.value = rewards;

    currentStep.value = 4;
};

// También corregir el método generateOptimizationData para enviar todos los proyectos
const generateOptimizationData = () => {
    // CAMBIO: Usar todos los proyectos, no solo los seleccionados
    const allProjectsList = projects.value.map(p => p.nombre);

    // Parámetros principales (sin cambios)
    const parameters = {
        T: parseInt(globalParams.numPeriodos),
        NbMustTakeOne: parseInt(globalParams.nbMustTakeOne),
        Rate: parseFloat(globalParams.tasaDescuento),
        InitBal: parseInt(globalParams.saldoInicial),
        Description: globalParams.description || 'Optimización sin descripción'
    };

    // Saldos mínimos (sin cambios)
    const minBal = minBalances.value.map(item => ({
        Period: item.periodo,
        MinBal: item.saldo
    }));

    // Grupos must-take-one (sin cambios)
    const mustTakeOne: Array<{ group: number, project: string }> = [];
    groups.value.forEach(group => {
        group.projects.forEach(project => {
            mustTakeOne.push({
                group: group.id,
                project: project
            });
        });
    });

    // CAMBIO: Costos de TODOS los proyectos
    const projectCostsData: Array<{ project: string, period: number, cost: number }> = [];
    allProjectsList.forEach(project => { // CAMBIO: allProjectsList en lugar de selectedList
        if (projectCosts.value[project]) {
            projectCosts.value[project].forEach(cost => {
                projectCostsData.push({
                    project: project,
                    period: cost.periodo,
                    cost: cost.costo
                });
            });
        }
    });

    // CAMBIO: Recompensas de TODOS los proyectos
    const projectRewardsData: Array<{ project: string, period: number, reward: number }> = [];
    allProjectsList.forEach(project => { // CAMBIO: allProjectsList en lugar de selectedList
        if (projectRewards.value[project]) {
            projectRewards.value[project].forEach(reward => {
                projectRewardsData.push({
                    project: project,
                    period: reward.periodo,
                    reward: reward.recompensa
                });
            });
        }
    });

    return {
        parameters,
        minBal,
        mustTakeOne,
        projectCosts: projectCostsData,
        projectRewards: projectRewardsData
    };
};

// Manejar cambio en saldo mínimo
const handleMinBalanceChange = (index: number, value: string) => {
    minBalances.value[index].saldo = parseInt(value) || 0;
};

// Manejar cambio en costos de proyecto
const handleProjectCostChange = (project: string, period: number, value: string) => {
    const projectCostArray = projectCosts.value[project];
    const costIndex = projectCostArray.findIndex(item => item.periodo === period);
    if (costIndex !== -1) {
        projectCostArray[costIndex].costo = parseInt(value) || 0;
    }
};

// Manejar cambio en recompensas de proyecto
const handleProjectRewardChange = (project: string, period: number, value: string) => {
    const projectRewardArray = projectRewards.value[project];
    const rewardIndex = projectRewardArray.findIndex(item => item.periodo === period);
    if (rewardIndex !== -1) {
        projectRewardArray[rewardIndex].recompensa = parseInt(value) || 0;
    }
};



const handleSubmit = () => {
    if (isSubmitting.value) return;

    isSubmitting.value = true;

    try {
        const optimizationData = generateOptimizationData();

        console.log('Enviando datos:', optimizationData);

        router.post('/optimizations', optimizationData, {
            onSuccess: (response) => {
                console.log('Optimización creada exitosamente:', response);
                alert('¡Optimización creada y ejecutada exitosamente! Consulte el historial para ver el progreso.');
            },
            onError: (errors) => {
                console.error('Error al crear optimización:', errors);
                alert('Error al crear la optimización. Por favor, revise los datos e intente nuevamente.');
            },
            onFinish: () => {
                isSubmitting.value = false;
            }
        });
    } catch (error) {
        console.error('Error generando datos de optimización:', error);
        alert('Error al procesar los datos. Por favor, verifique la configuración e intente nuevamente.');
        isSubmitting.value = false;
    }
};
</script>

<template>

    <Head title="Inicio - Capital Budgeting" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header del Dashboard -->
            <div class="mb-6">
                <h1
                    class="text-3xl font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-emerald-400 bg-clip-text text-transparent mb-2">
                    Configuración del Modelo
                </h1>
                <p class="text-blue-100/80">
                    Configure los parámetros y proyectos para el análisis de capital budgeting
                </p>
            </div>

            <!-- Contenido de la configuración del modelo -->
            <div class="space-y-8">
                <div class="relative rounded-xl border border-white/20 bg-white/5 backdrop-blur-sm p-6">
                    <h2 class="text-2xl font-bold text-white mb-6">Entrada del Modelo</h2>

                    <!-- Paso 1: Parámetros Globales -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                            1. Parámetros Globales
                            <span v-if="areGlobalParamsValid" class="text-green-400 text-sm">✓ Completado</span>
                        </h3>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="block text-blue-200 text-sm mb-2">Número de períodos (T) *</label>
                                <input type="number" v-model="globalParams.numPeriodos"
                                    @input="handleGlobalParamChange('numPeriodos', ($event.target as HTMLInputElement).value)"
                                    class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-white placeholder-blue-200/50 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
                                    required />
                            </div>
                            <div>
                                <label class="block text-blue-200 text-sm mb-2">Tasa de descuento (Rate) *</label>
                                <input type="number" step="0.01" v-model="globalParams.tasaDescuento"
                                    @input="handleGlobalParamChange('tasaDescuento', ($event.target as HTMLInputElement).value)"
                                    class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-white placeholder-blue-200/50 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
                                    required />
                            </div>
                            <div>
                                <label class="block text-blue-200 text-sm mb-2">Saldo Inicial (InitBal) *</label>
                                <input type="number" v-model="globalParams.saldoInicial"
                                    @input="handleGlobalParamChange('saldoInicial', ($event.target as HTMLInputElement).value)"
                                    class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-white placeholder-blue-200/50 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
                                    required />
                            </div>
                        </div>
                        <div>
                            <label class="block text-blue-200 text-sm mb-2">Descripción</label>
                            <textarea v-model="globalParams.description"
                                @input="handleGlobalParamChange('description', ($event.target as HTMLTextAreaElement).value || '')"
                                class="w-full bg-white/10 border border-white/20 rounded text-white placeholder-blue-200/50 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 resize-y text-sm"
                                rows="2" placeholder=" Descripción opcional de la optimización">
                            </textarea>
                        </div>
                        <p class="text-blue-200/60 text-sm">* Todos los campos son obligatorios para continuar</p>
                    </div>

                    <!-- Resto del formulario igual... -->
                    <!-- Paso 2: Gestión de Proyectos -->
                    <div v-if="areGlobalParamsValid" class="mb-8">
                        <h3 class="text-lg font-semibold text-white mb-4">
                            2. Gestión de Proyectos
                        </h3>

                        <!-- Botón para agregar proyecto -->
                        <div class="flex justify-between items-center mb-4">
                            <p class="text-blue-200 text-sm">Configure los proyectos disponibles con sus costos y
                                recompensas base</p>
                            <button @click="showAddProject = !showAddProject"
                                class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded transition-colors text-sm">
                                + Agregar Proyecto
                            </button>
                        </div>

                        <!-- Formulario para agregar proyecto -->
                        <div v-if="showAddProject" class="mb-6 p-4 bg-white/5 rounded-lg border border-white/10">
                            <h4 class="text-white font-medium mb-3">Nuevo Proyecto</h4>
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-blue-200 text-sm mb-2">Nombre del Proyecto *</label>
                                    <input type="text" v-model="newProject.nombre" @keyup.enter="addNewProject"
                                        placeholder="Ej: Proyecto_Alpha"
                                        class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-white placeholder-blue-200/50 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
                                </div>
                                <div>
                                    <label class="block text-blue-200 text-sm mb-2">Costo Base *</label>
                                    <input type="number" v-model="newProject.costoBase" placeholder="0"
                                        class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-white placeholder-blue-200/50 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
                                </div>
                                <div>
                                    <label class="block text-blue-200 text-sm mb-2">Recompensa Base *</label>
                                    <input type="number" v-model="newProject.recompensaBase" placeholder="0"
                                        class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-white placeholder-blue-200/50 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <button @click="addNewProject"
                                    :disabled="!newProject.nombre.trim() || newProject.costoBase <= 0 || newProject.recompensaBase <= 0"
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-500 disabled:cursor-not-allowed text-white rounded transition-colors">
                                    Agregar Proyecto
                                </button>
                                <button
                                    @click="showAddProject = false; newProject.nombre = ''; newProject.costoBase = 0; newProject.recompensaBase = 0;"
                                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition-colors">
                                    Cancelar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de proyectos -->
                        <div class="bg-white/5 rounded-lg overflow-hidden border border-white/10 mb-6">
                            <table class="w-full">
                                <thead class="bg-white/10">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-blue-200">Nombre del Proyecto</th>
                                        <th class="px-4 py-3 text-left text-blue-200">Costo Base</th>
                                        <th class="px-4 py-3 text-left text-blue-200">Recompensa Base</th>
                                        <th class="px-4 py-3 text-left text-blue-200">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(project, index) in projects" :key="project.nombre"
                                        class="border-t border-white/10">
                                        <td class="px-4 py-3">
                                            <input type="text" :value="project.nombre"
                                                @input="editProject(index, 'nombre', ($event.target as HTMLInputElement).value)"
                                                class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white focus:outline-none focus:border-blue-400" />
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" :value="project.costoBase"
                                                @input="editProject(index, 'costoBase', ($event.target as HTMLInputElement).value)"
                                                class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white focus:outline-none focus:border-blue-400" />
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" :value="project.recompensaBase"
                                                @input="editProject(index, 'recompensaBase', ($event.target as HTMLInputElement).value)"
                                                class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white focus:outline-none focus:border-blue-400" />
                                        </td>
                                        <td class="px-4 py-3">
                                            <button @click="removeProject(project.nombre)"
                                                class="text-red-400 hover:text-red-300 text-sm"
                                                title="Eliminar proyecto">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Configuración de Grupos -->
                        <div class="mb-4">
                            <label class="block text-blue-200 text-sm mb-2">Cantidad de grupos</label>
                            <div class="flex items-center gap-4">
                                <input type="number" v-model="numGroups"
                                    class="w-48 px-3 py-2 bg-white/10 border border-white/20 rounded text-white placeholder-blue-200/50 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
                                    placeholder="Ej: 2" />
                                <button @click="initializeGroups" :disabled="!numGroups || projects.length === 0"
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-500 disabled:cursor-not-allowed text-white rounded transition-colors">
                                    Crear Grupos
                                </button>
                            </div>
                            <p class="text-blue-200/60 text-xs mt-1">
                                El número de grupos se usará automáticamente como NbMustTakeOne
                            </p>
                        </div>

                        <!-- Mostrar grupos -->
                        <div v-if="groups.length > 0" class="space-y-4">
                            <div v-for="group in groups" :key="group.id"
                                class="bg-white/5 rounded-lg p-4 border border-white/10">
                                <h4 class="text-white font-medium mb-3">{{ group.name }}</h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 mb-3">
                                    <div v-for="project in projects" :key="project.nombre" class="flex items-center">
                                        <input type="checkbox" :id="`${group.id}-${project.nombre}`"
                                            :checked="selectedProjects[project.nombre] === group.id"
                                            :disabled="selectedProjects[project.nombre] && selectedProjects[project.nombre] !== group.id"
                                            @change="handleProjectSelection(group.id, project.nombre, ($event.target as HTMLInputElement).checked)"
                                            class="mr-2 accent-blue-500" />
                                        <label :for="`${group.id}-${project.nombre}`" :class="[
                                            'text-sm cursor-pointer',
                                            selectedProjects[project.nombre] && selectedProjects[project.nombre] !== group.id
                                                ? 'text-gray-400'
                                                : 'text-blue-200 hover:text-white'
                                        ]">
                                            {{ project.nombre }}
                                        </label>
                                    </div>
                                </div>
                                <div class="text-xs text-blue-300">
                                    <strong>Proyectos seleccionados:</strong>
                                    {{ group.projects.length > 0 ? group.projects.join(', ') : 'Ninguno' }}
                                </div>
                            </div>

                            <div v-if="selectedProjectsList.length > 0" class="flex justify-end">
                                <button @click="generateDynamicTables"
                                    class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded transition-colors">
                                    Continuar con Períodos ({{ selectedProjectsList.length }} proyectos seleccionados)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Saldos Mínimos por Período -->
                    <div v-if="currentStep >= 4 && minBalances.length > 0" class="mb-8">
                        <h3 class="text-lg font-semibold text-white mb-4">3. Saldo Mínimo por Período</h3>
                        <div class="bg-white/5 rounded-lg overflow-hidden border border-white/10">
                            <table class="w-full">
                                <thead class="bg-white/10">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-blue-200">Período</th>
                                        <th class="px-4 py-3 text-left text-blue-200">Saldo Mínimo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in minBalances" :key="index"
                                        class="border-t border-white/10">
                                        <td class="px-4 py-3 text-white">{{ item.periodo }}</td>
                                        <td class="px-4 py-3">
                                            <input type="number" :value="item.saldo"
                                                @input="handleMinBalanceChange(index, ($event.target as HTMLInputElement).value)"
                                                class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white focus:outline-none focus:border-blue-400"
                                                placeholder="0" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Paso 4: Costos de Proyectos por Período -->
                    <div v-if="currentStep >= 4 && Object.keys(projectCosts).length > 0" class="mb-8">
                        <h3 class="text-lg font-semibold text-white mb-4">4. Costos de Proyectos por Período</h3>
                        <p class="text-blue-200/60 text-sm mb-3">
                            Los valores se inicializan con los costos base configurados para TODOS los proyectos
                            disponibles.
                            Puede modificarlos según sea necesario.
                        </p>
                        <div class="bg-white/5 rounded-lg overflow-hidden border border-white/10">
                            <table class="w-full">
                                <thead class="bg-white/10">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-blue-200">Proyecto</th>
                                        <th v-for="i in parseInt(globalParams.numPeriodos)" :key="i"
                                            class="px-4 py-3 text-left text-blue-200">
                                            Período {{ i }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- CAMBIO: Mostrar TODOS los proyectos disponibles -->
                                    <tr v-for="project in projects.map(p => p.nombre)" :key="project"
                                        class="border-t border-white/10">
                                        <td class="px-4 py-3 text-white font-medium">
                                            {{ project }}
                                            <!-- Indicador si está seleccionado en algún grupo -->
                                            <span v-if="selectedProjects[project]"
                                                class="ml-2 px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs">
                                                Grupo {{ selectedProjects[project] }}
                                            </span>
                                        </td>
                                        <td v-for="cost in projectCosts[project]" :key="cost.periodo" class="px-4 py-3">
                                            <input type="number" :value="cost.costo"
                                                @input="handleProjectCostChange(project, cost.periodo, ($event.target as HTMLInputElement).value)"
                                                class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white focus:outline-none focus:border-blue-400"
                                                placeholder="0" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Paso 5: Recompensas de Proyecto por Período -->
                    <div v-if="currentStep >= 4 && Object.keys(projectRewards).length > 0" class="mb-8">
                        <h3 class="text-lg font-semibold text-white mb-4">5. Recompensas de Proyecto por Período</h3>
                        <p class="text-blue-200/60 text-sm mb-3">
                            Los valores se inicializan con las recompensas base configuradas para TODOS los proyectos
                            disponibles.
                            Puede modificarlos según sea necesario.
                        </p>
                        <div class="bg-white/5 rounded-lg overflow-hidden border border-white/10">
                            <table class="w-full">
                                <thead class="bg-white/10">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-blue-200">Proyecto</th>
                                        <th v-for="i in parseInt(globalParams.numPeriodos)" :key="i"
                                            class="px-4 py-3 text-left text-blue-200">
                                            Período {{ i }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- CAMBIO: Mostrar TODOS los proyectos disponibles -->
                                    <tr v-for="project in projects.map(p => p.nombre)" :key="project"
                                        class="border-t border-white/10">
                                        <td class="px-4 py-3 text-white font-medium">
                                            {{ project }}
                                            <!-- Indicador si está seleccionado en algún grupo -->
                                            <span v-if="selectedProjects[project]"
                                                class="ml-2 px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs">
                                                Grupo {{ selectedProjects[project] }}
                                            </span>
                                        </td>
                                        <td v-for="reward in projectRewards[project]" :key="reward.periodo"
                                            class="px-4 py-3">
                                            <input type="number" :value="reward.recompensa"
                                                @input="handleProjectRewardChange(project, reward.periodo, ($event.target as HTMLInputElement).value)"
                                                class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white focus:outline-none focus:border-blue-400"
                                                placeholder="0" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Botón para enviar la optimización -->
                    <div class="flex justify-end mt-6">
                        <button @click="handleSubmit" :disabled="isSubmitting || !areGlobalParamsValid"
                            class="px-6 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-500 disabled:cursor-not-allowed text-white rounded transition-colors">
                            {{ isSubmitting ? 'Enviando...' : 'Enviar Optimización' }}
                        </button>
                    </div>

                    <!-- Mensaje de estado actualizado -->
                    <div v-if="currentStep >= 4" class="mt-4">
                        <p class="text-blue-200/60 text-sm">
                            ✓ Modelo configurado con {{ projects.length }} proyectos disponibles,
                            {{ selectedProjectsList.length }} proyectos seleccionados en {{ groups.length }} grupos
                        </p>
                        <p class="text-blue-200/60 text-xs mt-1">
                            Al enviar se creará la optimización, generarán los CSVs para todos los proyectos,
                            se subirán a IBM COS y se ejecutará automáticamente el job.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
