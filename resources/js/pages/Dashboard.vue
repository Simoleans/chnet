<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import ReportPaymentModal from '../components/ReportPaymentModal.vue';
import { useForm } from '@inertiajs/vue3';
import { useNotifications } from '@/composables/useNotifications';
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

// Usar usePage para acceder a los datos del usuario
const page = usePage()

// Estados para el pago
const paymentLoading = ref(false)
const paymentError = ref(false)
const showReferenceInput = ref(false)
const referenceNumber = ref('')

// Estado para el modal de reportar pago
const showReportPaymentModal = ref(false)

const payFee = () => {
    form.post(route('pay-fee'));
}

const reloadBcvRate = async () => {
    await bcvStore.$reloadBcvAmount()
}

const copyPaymentReference = () => {
    console.log('Intentando copiar...', { bcv: bcv.value, user: page.props.auth?.user });

    if (bcv.value && page.props.auth?.user?.plan?.price) {
        const total = (parseFloat(page.props.auth.user.plan.price) * parseFloat(bcv.value)).toFixed(2);
        const reference = `0191 12569785 ${total}`;

        console.log('Referencia a copiar:', reference);

        // Usar método compatible con todos los navegadores
        const textArea = document.createElement('textarea');
        textArea.value = reference;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        textArea.style.top = '0';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);

            if (successful) {
                notify({
                    message: 'Datos bancarios copiados',
                    type: 'success',
                    duration: 1100,
                });
            } else {
                notify({
                    message: 'No se pudo copiar automáticamente. Copia manualmente:\n\n' + reference,
                    type: 'error',
                    duration: 3000,
                });
            }
        } catch (err) {
            console.error('Error al copiar:', err);
            document.body.removeChild(textArea);
            alert('Copia manualmente este texto:\n\n' + reference);
        }
    } else {
        notify({
            message: 'No hay datos disponibles para copiar. Verifica que tengas un plan asignado y que la tasa BCV esté cargada.',
            type: 'error',
            duration: 3000,
        });
    }
}

const checkPayment = async () => {
    paymentLoading.value = true;
    paymentError.value = false;
    showReferenceInput.value = false;

    try {
        const res = await fetch('/api/bnc/history?account=01910001482101010049');
        const json = await res.json();

        if (!res.ok || !json.success) {
            paymentError.value = true;
            showReferenceInput.value = true;
            notify({
                message: 'Error al conectar con el banco. Ingrese el número de referencia manualmente.',
                type: 'error',
                duration: 3000,
            });
            throw new Error(json.error || 'Error desconocido');
        }

        // Si llegamos aquí, la conexión fue exitosa
        notify({
            message: 'Se conectó exitosamente con el banco',
            type: 'success',
            duration: 2000,
        });

    } catch (err) {
        console.error('Error al verificar pago:', err);
        paymentError.value = true;
        showReferenceInput.value = true;
    } finally {
        paymentLoading.value = false;
    }
}

const submitReference = () => {
    if (referenceNumber.value.trim()) {
        notify({
            message: 'Número de referencia enviado correctamente',
            type: 'success',
            duration: 2000,
        });
        showReferenceInput.value = false;
        referenceNumber.value = '';
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
                                            <p class="font-medium">Banco Nacional de Crédito</p>
                                            <p class="text-sm font-bold">RIF/Cédula: 12569785</p>
                                            <p class="text-sm">
                                                <span class="font-medium">Monto a pagar: </span>
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
                                                    <div class="flex gap-2">
                                                        <Input
                                                            v-model="referenceNumber"
                                                            placeholder="Ingrese el número de referencia"
                                                            class="flex-1"
                                                        />
                                                        <Button
                                                            @click="submitReference"
                                                            size="sm"
                                                            :disabled="!referenceNumber.trim()"
                                                        >
                                                            Enviar
                                                        </Button>
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
                <pre>{{ $page.props.auth.user}}</pre>
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
