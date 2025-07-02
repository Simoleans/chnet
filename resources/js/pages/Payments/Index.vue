<template>
    <AppLayout>
        <Head title="Pagos" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between flex-col md:lg:flex-row">
                <h1 class="text-2xl font-semibold">Pagos</h1>
            </div>

            <div class="flex flex-col sm:flex-row justify-between gap-4">
                <input
                    v-model="search"
                    @input="submit"
                    type="text"
                    placeholder="Buscar por referencia o código de abonado..."
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
                                        <button
                                            v-if="item.image_path"
                                            @click="viewReceipt(item)"
                                            class="px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 transition text-sm"
                                        >
                                            Ver Comprobante
                                        </button>
                                        <button
                                            @click="viewPayment(item)"
                                            class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm"
                                        >
                                            Ver Detalles
                                        </button>
                                    </div>
                                </template>
                                <template v-else-if="column.key === 'amount'">
                                    <div class="flex flex-col">
                                        <span class="font-medium">${{ Number(item.amount).toFixed(2) }}</span>
                                        <span class="text-xs text-muted-foreground">
                                            Bs. {{ Number(item.amount_bs).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                                        </span>
                                    </div>
                                </template>
                                <template v-else-if="column.key === 'user_info'">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ item.user_name }}</span>
                                        <span class="text-xs text-muted-foreground">{{ item.user_code }}</span>
                                    </div>
                                </template>
                                <template v-else-if="column.key === 'contact_info'">
                                    <div class="flex flex-col">
                                        <span class="text-sm">{{ item.id_number }}</span>
                                        <span class="text-xs text-muted-foreground">{{ item.phone }}</span>
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

            <!-- Mensaje si no hay datos -->
            <div v-if="data && data.length === 0" class="text-center py-8 text-gray-500">
                No se encontraron pagos.
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

                <!-- Modales -->
        <PaymentDetailsModal
            v-model:open="showPaymentDetailsModal"
            :payment="selectedPayment"
        />

        <PaymentReceiptModal
            v-model:open="showReceiptModal"
            :payment="selectedPayment"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import PaymentDetailsModal from '@/components/PaymentDetailsModal.vue'
import PaymentReceiptModal from '@/components/PaymentReceiptModal.vue'
import { debounce } from 'lodash'

const props = defineProps({
    data: Array,
    filters: Object,
    pagination: Object,
})

const columns = [
    { key: 'reference', label: 'Referencia' },
    { key: 'user_info', label: 'Abonado' },
    { key: 'amount', label: 'Monto' },
    { key: 'payment_date', label: 'Fecha Pago' },
    { key: 'bank', label: 'Banco' },
    { key: 'contact_info', label: 'Contacto' },
    { key: 'invoice_period', label: 'Período' },
    { key: 'created_at', label: 'Registrado' },
    { key: 'actions', label: 'Acciones' },
]

const search = ref(props.filters.search || '')

// Estados para los modales
const showPaymentDetailsModal = ref(false)
const showReceiptModal = ref(false)
const selectedPayment = ref(null)

const restoreFilters = () => {
    search.value = ''
    submit()
}

const submit = debounce(() => {
    router.get(route('payments.index'), {
        search: search.value,
        page: 1, // Resetear a la primera página al filtrar
    }, {
        preserveState: true,
        replace: true,
    })
}, 700)

const previousPage = () => {
    if (props.pagination.current_page > 1) {
        router.get(route('payments.index'), {
            search: search.value,
            page: props.pagination.current_page - 1,
        }, {
            preserveState: true,
            replace: true,
        })
    }
}

const nextPage = () => {
    if (props.pagination.current_page < props.pagination.last_page) {
        router.get(route('payments.index'), {
            search: search.value,
            page: props.pagination.current_page + 1,
        }, {
            preserveState: true,
            replace: true,
        })
    }
}

const viewPayment = (payment) => {
    selectedPayment.value = payment
    showPaymentDetailsModal.value = true
}

const viewReceipt = (payment) => {
    if (payment.image_path) {
        selectedPayment.value = payment
        showReceiptModal.value = true
    }
}


</script>
