<template>
    <AppLayout>
        <Head title="Lista de Planes" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between flex-col md:lg:flex-row">
                <h1 class="text-2xl font-semibold">Lista de Planes</h1>
                <div class="flex gap-2">
                    <CreatePlan />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between gap-4">
                <input
                    v-model="search"
                    @input="filterPlans"
                    type="text"
                    placeholder="Buscar por nombre..."
                    class="w-full sm:w-1/2 p-2 border rounded-md dark:text-black"
                />
            </div>

            <!-- Tabla responsive -->
            <div class="w-full overflow-auto rounded-xl border bg-background shadow-sm">
                <table class="min-w-max w-full text-sm text-left border-collapse">
                    <thead class="border-b bg-muted">
                        <tr>
                            <th
                                v-for="column in columns"
                                :key="column.key"
                                class="px-4 py-2 text-muted-foreground text-sm font-medium whitespace-nowrap"
                            >
                                {{ column.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(plan, i) in filteredPlans"
                            :key="i"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td v-for="column in columns" :key="column.key" class="px-4 py-3 whitespace-nowrap">
                                <template v-if="column.key === 'actions'">
                                    <div class="flex gap-2">
                                        <EditPlan :plan-data="plan" />
                                    </div>
                                </template>
                                <template v-else-if="column.key === 'price'">
                                    ${{ getPlanProperty(plan, column.key) }}
                                </template>
                                <template v-else-if="column.key === 'price_bs'">
                                    {{ formatNumber(parseFloat(getPlanProperty(plan, 'price')) * (bcvStore.bcv || 1)) }} Bs
                                </template>
                                <template v-else>
                                    {{ getPlanProperty(plan, column.key) }}
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mensaje si no hay datos -->
            <div v-if="filteredPlans && filteredPlans.length === 0" class="text-center py-8 text-gray-500">
                No se encontraron planes.
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import CreatePlan from './Components/CreatePlan.vue'
import EditPlan from './Components/EditPlan.vue'


import { useBcvStore } from '@/stores/bcv'
const bcvStore = useBcvStore()

// Definir interfaz para los planes
interface Plan {
    id: number
    name: string
    price: string
    price_bs?: string
    type: string
    mbps: string | null
    status: string
}

// Props (puedes recibir los datos desde el backend)
const props = defineProps<{
    plans?: Plan[]
}>()

//formatear miles
const formatNumber = (number: number) => {
    return number.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Columnas de la tabla
const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'price', label: 'Precio' },
    { key: 'price_bs', label: 'Precio en Bs' },
    { key: 'actions', label: 'Opciones' },
]

// Estado reactivo para búsqueda
const search = ref('')


// Planes filtrados
const filteredPlans = computed(() => {
    const plans = props.plans
    if (!search.value) {
        return plans
    }

    return plans.filter(plan =>
        plan.name?.toLowerCase().includes(search.value.toLowerCase())
    )
})

// Funciones
const filterPlans = () => {
    // La funcionalidad de filtrado se maneja automáticamente con computed
}



const getPlanProperty = (plan: Plan, key: string) => {
    return (plan as any)[key]
}
</script>
