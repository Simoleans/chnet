<template>
    <AppLayout>
        <Head title="Zonas" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between flex-col md:lg:flex-row">
                <h1 class="text-2xl font-semibold">Zonas</h1>
                <div class="flex gap-2">
                    <CreateZone />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between gap-4">
                <input
                    v-model="search"
                    @input="submit"
                    type="text"
                    placeholder="Buscar por nombre..."
                    class="w-full sm:w-1/2 p-2 border rounded-md dark:text-black"
                />
                <div class="flex w-full justify-end items-center gap-2">
                    <Button variant="outline" @click="restoreFilters">Restaurar Filtros</Button>
                </div>
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
                            v-for="(item, i) in data"
                            :key="i"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td v-for="column in columns" :key="column.key" class="px-4 py-3 whitespace-nowrap">
                                <template v-if="column.key === 'actions'">
                                    <div class="flex gap-2">
                                        <EditZone :zone-data="item" />
                                       <!--  <button
                                            @click="deleteZone(item)"
                                            class="px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition text-sm"
                                        >
                                            Eliminar
                                        </button> -->
                                    </div>
                                </template>
                                <template v-else>
                                    {{ item[column.key as keyof Zone] }}
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mensaje si no hay datos -->
            <div v-if="data && data.length === 0" class="text-center py-8 text-gray-500">
                No se encontraron zonas.
            </div>

            <!-- Paginación -->
            <div v-if="pagination" class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between flex-1 sm:justify-end">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-700">
                            Mostrando
                            <span class="font-medium">{{ pagination.from }}</span>
                            a
                            <span class="font-medium">{{ pagination.to }}</span>
                            de
                            <span class="font-medium">{{ pagination.total }}</span>
                            resultados
                        </span>
                        <div class="flex gap-1">
                            <button
                                @click="previousPage"
                                :disabled="pagination.current_page === 1"
                                class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Anterior
                            </button>
                            <button
                                @click="nextPage"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { debounce } from 'lodash'
import CreateZone from './Components/CreateZone.vue'
import EditZone from './Components/EditZone.vue'

interface Zone {
    id: number
    name: string
}

interface Pagination {
    current_page: number
    last_page: number
    from: number
    to: number
    total: number
}

interface Filters {
    search?: string
}

const props = defineProps<{
    data: Zone[]
    filters: Filters
    pagination?: Pagination
}>()

const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'actions', label: 'Opciones' },
]

const search = ref(props.filters.search || '')

const restoreFilters = () => {
    search.value = ''
    submit()
}

const submit = debounce(() => {
    router.get(route('zones.index'), {
        search: search.value,
        page: 1, // Resetear a la primera página al filtrar
    }, {
        preserveState: true,
        replace: true,
    })
}, 700)

const previousPage = () => {
    if (props.pagination && props.pagination.current_page > 1) {
        router.get(route('zones.index'), {
            search: search.value,
            page: props.pagination.current_page - 1,
        }, {
            preserveState: true,
            replace: true,
        })
    }
}

const nextPage = () => {
    if (props.pagination && props.pagination.current_page < props.pagination.last_page) {
        router.get(route('zones.index'), {
            search: search.value,
            page: props.pagination.current_page + 1,
        }, {
            preserveState: true,
            replace: true,
        })
    }
}

const deleteZone = (zone: Zone) => {
    if (confirm('¿Estás seguro de que quieres eliminar esta zona?')) {
        router.delete(route('zones.destroy', zone.id), {
            preserveState: true,
        })
    }
}
</script>
