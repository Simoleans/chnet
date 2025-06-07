<template>
    <AppLayout>
      <Head title="Mi BUS" />
      <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex justify-between flex-col md:lg:flex-row">
            <h1 class="text-2xl font-semibold">Mi BUS</h1>
            <div class="flex gap-2">
                <CreateBus />
                <UploadBus />
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between gap-4">
          <input
            v-model="search"
            @input="submit"
            type="text"
            placeholder="Buscar por # Caso o Operador..."
            class="w-full sm:w-1/2 p-2 border rounded-md dark:text-black"
          />
          <div class="flex w-full justify-between items-center gap-2">
            <!-- fecha -->
            <input
              v-model="date"
              @input="submit"
              type="date"
              placeholder="Buscar por fecha..."
              class="w-full sm:w-1/2 p-2 border rounded-md dark:text-black"
            />
            <Button variant="outline" @click="restoreFilters">Restaurar Filtros</Button>
          </div>
         <!--  <CreateLawyer /> -->
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
                      <button
                        @click="generatePDF(item)"
                        class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm"
                      >
                        Imprimir
                      </button>
                      <EditBus :bus-data="item" />
                    </div>
                  </template>
                  <template v-else>
                    {{ item[column.key] }}
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
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
    import { ref, watch } from 'vue'
    import UploadBus from './Components/UploadBus.vue'
    import CreateBus from './Components/CreateBus.vue'
    import EditBus from './Components/EditBus.vue'
    import { Button } from '@/components/ui/button'
    import { generateBusPDF } from '@/utils/generateBusPDF'
    import { debounce } from 'lodash'

    const props = defineProps({
        data: Array,
        filters: Object,
        pagination: Object,
    })

    const columns = [
        { key: 'caso', label: 'Caso' },
        { key: 'fecha_accidente', label: 'Fecha Accidente' },
        { key: 'nombre_operador', label: 'Nombre Operador' },
        { key: 'patio_operador', label: 'Patio Operador' },
        { key: 'fecha_audiencia', label: 'Fecha Audiencia' },
        { key: 'actions', label: 'Opciones' },
    ]

    const search = ref(props.filters.search || '')
    const date = ref(props.filters.date || '')

    const restoreFilters = () => {
        search.value = ''
        date.value = ''
        submit()
    }

    const submit = debounce(() => {
        router.get(route('sheets.index'), {
            search: search.value,
            date: date.value,
            page: 1, // Resetear a la primera página al filtrar
        }, {
            preserveState: true,
            replace: true,
        })
    }, 700)

    const previousPage = () => {
        if (props.pagination.current_page > 1) {
            router.get(route('sheets.index'), {
                search: search.value,
                date: date.value,
                page: props.pagination.current_page - 1,
            }, {
                preserveState: true,
                replace: true,
            })
        }
    }

    const nextPage = () => {
        if (props.pagination.current_page < props.pagination.last_page) {
            router.get(route('sheets.index'), {
                search: search.value,
                date: date.value,
                page: props.pagination.current_page + 1,
            }, {
                preserveState: true,
                replace: true,
            })
        }
    }

    watch(search, submit)

    const generatePDF = async (item) => {
      const doc = await generateBusPDF(item)
      doc.save(`mibus_${item.caso}.pdf`)
    }
  </script>
