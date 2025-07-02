<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import ReportPaymentModal from '../components/ReportPaymentModal.vue';
import { useForm } from '@inertiajs/vue3';
import { useNotifications } from '@/composables/useNotifications';
import axios from 'axios';
const { notify } = useNotifications();

// Components

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

import { useBcvStore } from '@/stores/bcv';
import { useBanksStore } from '@/stores/banks';
import { storeToRefs } from 'pinia';


const form = useForm({
    client: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mi CHNET',
        href: '/dashboard',
    },
];

// Usar el store de BCV
const bcvStore = useBcvStore()
const { bcv, date, loading, error } = storeToRefs(bcvStore)

// Usar el store de bancos
const banksStore = useBanksStore()

// Cargar bancos al montar el componente
banksStore.loadBanks()

// Usar usePage para acceder a los datos del usuario
const page = usePage()

// Estados para el pago
const paymentLoading = ref(false)
const paymentError = ref(false)
const showReferenceInput = ref(false)
const referenceNumber = ref('')
const paymentDate = ref('')
const paymentAmount = ref('')
const showReportLink = ref(false)

// Estado para el modal de reportar pago
const showReportPaymentModal = ref(false)

// Estado para el modal de pagar plan
const showPaymentModal = ref(false)

const payFee = () => {
    form.post(route('pay-fee'));
}

const reloadBcvRate = async () => {
    await bcvStore.$reloadBcvAmount()
}

const copyPaymentReference = async () => {
    console.log('Intentando copiar...', { bcv: bcv.value, user: page.props.auth?.user });

    if (bcv.value && page.props.auth?.user?.plan?.price) {
        const total = (parseFloat(page.props.auth.user.plan.price) * parseFloat(bcv.value)).toFixed(2);

        // Formato mejorado de los datos bancarios
        const bankingData = `📧 CHNET - Datos para Pago Movil

            Banco: 0191
            RIF: J125697857
            Teléfono: 04120355541
            Monto: ${total} Bs`;

        console.log('Datos a copiar:', bankingData);

        try {
            // Intentar usar la API moderna del Clipboard
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(bankingData);
                notify({
                    message: 'Datos copiados correctamente',
                    type: 'success',
                    duration: 1500,
                });
            } else {
                // Fallback para navegadores que no soportan clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = bankingData;
                textArea.style.position = 'fixed';
                textArea.style.left = '-9999px';
                textArea.style.top = '0';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);

                if (successful) {
                    notify({
                        message: '✅ Datos bancarios copiados (método compatible)',
                        type: 'success',
                        duration: 2000,
                    });
                } else {
                    throw new Error('Método de copia no disponible');
                }
            }
        } catch (err) {
            console.error('Error al copiar:', err);

            // Mostrar modal con los datos para copiar manualmente
            notify({
                message: '⚠️ No se pudo copiar automáticamente. Revisa la consola para copiar manualmente.',
                type: 'error',
                duration: 4000,
            });

            // Log los datos en consola para que el usuario pueda copiarlos
            console.log('📋 COPIA ESTOS DATOS MANUALMENTE:');
            console.log('=====================================');
            console.log(bankingData);
            console.log('=====================================');

            // También mostrar un alert como último recurso
            alert(`No se pudo copiar automáticamente. Copia estos datos manualmente:\n\n${bankingData}`);
        }
    } else {
        notify({
            message: '❌ No hay datos disponibles para copiar. Verifica que tengas un plan asignado y que la tasa BCV esté cargada.',
            type: 'error',
            duration: 3000,
        });
    }
}

const handleReportManually = () => {
    showPaymentModal.value = false;
    showReportPaymentModal.value = true;
}

const checkPayment = async () => {
    paymentLoading.value = true;
    paymentError.value = false;
    showReferenceInput.value = true;
    showReportLink.value = false;

    // Si no hay fecha, usar la fecha actual
    if (!paymentDate.value) {
        const today = new Date();
        paymentDate.value = today.toISOString().split('T')[0];
    }

    // Si no hay monto, usar el del plan
    if (!paymentAmount.value && bcv.value && $page.props.auth?.user?.plan?.price) {
        paymentAmount.value = (parseFloat($page.props.auth.user.plan.price) * parseFloat(bcv.value)).toFixed(2);
    }

    try {
        if (referenceNumber.value.trim()) {
            console.log('LOG:: Validando referencia:', referenceNumber.value);
            console.log('LOG:: Fecha de pago:', paymentDate.value);
            console.log('LOG:: Monto esperado:', paymentAmount.value);

            const res = await axios.get(`/api/bnc/validate-reference/${referenceNumber.value}`, {
                params: {
                    payment_date: paymentDate.value,
                    expected_amount: parseFloat(paymentAmount.value)
                }
            });

            console.log('LOG:: Respuesta de validación:', res.data);

            if (res.data.success) {
                notify({
                    message: res.data.message,
                    type: 'success',
                    duration: 2000,
                });
                showReferenceInput.value = false;
                referenceNumber.value = '';
                paymentDate.value = '';
                paymentAmount.value = '';
                showPaymentModal.value = false;
            } else {
                showReportLink.value = res.data.showReportLink;
                notify({
                    message: res.data.message,
                    type: 'warning',
                    duration: 4000,
                });
            }
        }
    } catch (err) {
        console.error('LOG:: Error al validar referencia:', err);
        paymentError.value = true;
        notify({
            message: err.response?.data?.error || 'Error al validar la referencia. Por favor, intente nuevamente.',
            type: 'error',
            duration: 3000,
        });
    } finally {
        paymentLoading.value = false;
    }
}

const submitReference = async () => {
    if (referenceNumber.value.trim()) {
        await checkPayment();
    } else {
        notify({
            message: 'Por favor ingrese un número de referencia válido',
            type: 'error',
            duration: 2000,
        });
    }
}

</script>

<template>
    <Head title="Mi CHNET" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Tarjeta Tasa BCV -->
                <div class="relative min-h-[200px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <div class="p-4 h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Tasa BCV</h3>
                            <div v-if="loading" class="text-sm text-gray-500">Cargando...</div>
                            <div v-else-if="error" class="text-sm text-red-500">{{ error }}</div>
                            <div v-else class="space-y-2">
                                <p class="text-2xl font-bold">{{ bcv ? `${bcv} Bs` : 'No disponible' }}</p>
                                <p class="text-sm text-gray-500">{{ date ? `Fecha: ${date}` : '' }}</p>
                            </div>
                        </div>
                        <div v-if="!loading && !error" class="flex gap-2 mt-4">
                            <Button @click="reloadBcvRate" size="sm" variant="outline" :disabled="loading" class="flex-1">
                                Actualizar
                            </Button>
                            <Button as="a" href="https://www.bcv.org.ve/" target="_blank" size="sm" variant="outline" class="flex-1">
                                Verificar
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Mi Plan -->
                <div class="relative min-h-[200px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern v-if="!$page.props.auth.user?.plan_id" />
                    <div v-else class="p-4 h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Mi Plan</h3>
                            <div class="space-y-2">
                                <p class="text-2xl font-bold">{{ $page.props.auth.user.plan.name }}</p>
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-500">
                                        <span class="font-medium">Velocidad:</span>
                                        {{ $page.props.auth.user.plan.mbps ? `${$page.props.auth.user.plan.mbps} Mbps` : '-' }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <span class="font-medium">Precio:</span>
                                        ${{ $page.props.auth.user.plan.price }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <span class="font-medium">Tipo:</span>
                                        {{ $page.props.auth.user.plan.type }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4" v-if="$page.props.auth.user.plan_id && bcv">
                            <div class="flex gap-2">
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button class="flex-1" size="sm">
                                            Pagar Plan
                                        </Button>
                                    </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Pagar Plan</DialogTitle>
                                        <DialogDescription>
                                            Datos para realizar el pago de tu plan
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div class="space-y-4">
                                        <input type="hidden" :value="$page.props.auth.user.id" />

                                        <div class="space-y-2">
                                            <p class="font-medium">🏦 Banco Nacional de Crédito</p>
                                            <!-- <p class="text-sm"><span class="font-medium">💳 Cuenta:</span> 0191-0001-48-2101010049</p> -->
                                            <p class="text-sm"><span class="font-medium">👤 RIF:</span> J-12569785-7</p>
                                            <p class="text-sm"><span class="font-medium">📞 Teléfono:</span> 0412-0355541</p>
                                            <p class="text-sm">
                                                <span class="font-medium">💰 Monto a pagar: </span>
                                                <span class="text-lg font-bold">
                                                    {{ bcv && $page.props.auth.user.plan.price ?
                                                        `${(parseFloat($page.props.auth.user.plan.price) * parseFloat(bcv)).toFixed(2)} Bs` :
                                                        'Calculando...'
                                                    }}
                                                </span>
                                            </p>
                                                                                        <div class="mt-3 space-y-2">
                                                <Button
                                                    @click="copyPaymentReference"
                                                    size="sm"
                                                    variant="outline"
                                                    :disabled="!bcv || !$page.props.auth.user.plan.price"
                                                    class="w-full"
                                                >
                                                    Copiar datos bancarios
                                                </Button>

                                                <Button
                                                    @click="checkPayment"
                                                    size="sm"
                                                    :disabled="paymentLoading || !bcv || !$page.props.auth.user.plan.price"
                                                    class="w-full"
                                                >
                                                    {{ paymentLoading ? 'Verificando...' : 'Ya pagué' }}
                                                </Button>

                                                <div v-if="showReferenceInput" class="space-y-2">
                                                    <label class="text-sm font-medium">Número de referencia:</label>
                                                    <div class="flex gap-2 flex-col">
                                                        <Input
                                                            v-model="referenceNumber"
                                                            placeholder="Ingrese el número de referencia"
                                                            class="flex-1"
                                                        />
                                                        <Input
                                                            type="date"
                                                            v-model="paymentDate"
                                                            class="flex-1"
                                                        />
                                                        <Input
                                                            type="number"
                                                            v-model="paymentAmount"
                                                            :placeholder="bcv && $page.props.auth?.user?.plan?.price ?
                                                                `Monto en Bs. (Sugerido: ${(parseFloat($page.props.auth.user.plan.price) * parseFloat(bcv)).toFixed(2)})` :
                                                                'Monto en Bs.'"
                                                            class="flex-1"
                                                            step="0.01"
                                                        />
                                                        <div class="flex items-center justify-between">
                                                            <Button
                                                                @click="submitReference"
                                                                size="sm"
                                                                :disabled="!referenceNumber.trim() || !paymentAmount || paymentAmount <= 0"
                                                            >
                                                                Enviar
                                                            </Button>
                                                            <Button
                                                                v-if="showReportLink"
                                                                @click="handleReportManually"
                                                                size="sm"
                                                                variant="link"
                                                                class="text-blue-500 hover:text-blue-700"
                                                            >
                                                                Reportar pago manualmente
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <DialogClose asChild>
                                            <Button variant="outline">Cerrar</Button>
                                        </DialogClose>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>

                            <Button
                                @click="showReportPaymentModal = true"
                                class="flex-1"
                                size="sm"
                                variant="outline"
                            >
                                Reportar pago
                            </Button>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- Tercera Tarjeta -->
                <div class="relative min-h-[200px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <!-- <PlaceholderPattern /> -->
                    <div class="p-4">
                        <h3 class="text-lg font-semibold mb-2">Mi Abonado CHNET</h3>
                        <div class="space-y-2">
                            <p class="text-2xl font-bold">{{ $page.props.auth.user.code }}</p>
                            <div class="space-y-1">
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium">Zona:</span>
                                    {{ $page.props.auth.user.zone.name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium">Dirección:</span>
                                    {{ $page.props.auth.user.address }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <PlaceholderPattern />
                <!-- <pre>{{ $page.props.auth.user}}</pre> -->
            </div>
        </div>

        <!-- Modal para reportar pago -->
        <ReportPaymentModal
            v-model:open="showReportPaymentModal"
            :plan-price="bcv && $page.props.auth.user?.plan?.price ?
                (parseFloat($page.props.auth.user.plan.price) * parseFloat(bcv)).toFixed(2) :
                '0'"
            :user-id="$page.props.auth.user?.id"
        />
    </AppLayout>
</template>
