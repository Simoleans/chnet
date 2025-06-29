<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useNotifications } from '@/composables/useNotifications';
import { useBanksStore } from '@/stores/banks';
const { notify } = useNotifications();

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
import { Label } from '@/components/ui/label';

interface Props {
    planPrice?: string | number;
    userId?: number;
    open?: boolean;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
}

const props = withDefaults(defineProps<Props>(), {
    planPrice: 0,
    open: false,
});

const emit = defineEmits<Emits>();

// Store Bancos
const banksStore = useBanksStore();
const { getBankOptions } = banksStore;

const form = useForm({
    reference: '',
    nationality: 'V',
    id_number: '',
    bank: '',
    phone: '',
    amount: '',
    payment_date: '',
});

// Watchers para sincronizar el estado del modal
watch(() => props.open, (newVal) => {
    if (newVal && props.planPrice) {
        form.amount = props.planPrice.toString();
    }
});

const setTodayDate = () => {
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    form.payment_date = formattedDate;
};

const submitPayment = () => {
    if (!form.reference.trim()) {
        notify({
            message: 'La referencia es obligatoria',
            type: 'error',
            duration: 2000,
        });
        return;
    }

    if (!form.nationality.trim()) {
        notify({
            message: 'La nacionalidad es obligatoria',
            type: 'error',
            duration: 2000,
        });
        return;
    }

    if (!form.id_number.trim()) {
        notify({
            message: 'La cédula es obligatoria',
            type: 'error',
            duration: 2000,
        });
        return;
    }

    if (!form.bank.trim()) {
        notify({
            message: 'El banco es obligatorio',
            type: 'error',
            duration: 2000,
        });
        return;
    }

    if (!form.phone.trim()) {
        notify({
            message: 'El teléfono es obligatorio',
            type: 'error',
            duration: 2000,
        });
        return;
    }

    if (!form.amount || parseFloat(form.amount) <= 0) {
        notify({
            message: 'El monto debe ser mayor a 0',
            type: 'error',
            duration: 2000,
        });
        return;
    }

    if (!form.payment_date) {
        notify({
            message: 'La fecha de pago es obligatoria',
            type: 'error',
            duration: 2000,
        });
        return;
    }

    form.post(route('payments.store'), {
        onSuccess: () => {
            notify({
                message: 'Pago reportado exitosamente',
                type: 'success',
                duration: 2000,
            });
            emit('update:open', false);
            resetForm();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            notify({
                message: Array.isArray(firstError) ? firstError[0] : firstError,
                type: 'error',
                duration: 3000,
            });
        },
    });
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
};

const handleOpenChange = (open: boolean) => {
    emit('update:open', open);
    if (!open) {
        resetForm();
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Reportar Pago</DialogTitle>
                <DialogDescription>
                    Ingresa los datos de tu pago realizado
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="reference">Referencia</Label>
                    <Input
                        id="reference"
                        v-model="form.reference"
                        placeholder="Número de referencia del pago"
                        :disabled="form.processing"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="nationality">Nacionalidad</Label>
                    <select
                        id="nationality"
                        v-model="form.nationality"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        <option value="V">V - Venezolano</option>
                        <option value="E">E - Extranjero</option>
                        <option value="J">J - Jurídico</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="id_number">Número de Cédula/RIF</Label>
                    <Input
                        id="id_number"
                        v-model="form.id_number"
                        placeholder="Ej: 12345678"
                        :disabled="form.processing"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="bank">Banco</Label>
                    <select
                        id="bank"
                        v-model="form.bank"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        <option value="">Seleccione un banco</option>
                        <option
                            v-for="bank in getBankOptions()"
                            :key="bank.value"
                            :value="bank.value"
                        >
                            {{ bank.label }}
                        </option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="phone">Teléfono</Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        placeholder="Ej: 0424-1234567"
                        :disabled="form.processing"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="amount">Monto (Bs)</Label>
                    <Input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        :disabled="form.processing"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="payment_date">Fecha de Pago</Label>
                    <div class="flex gap-2">
                        <Input
                            id="payment_date"
                            v-model="form.payment_date"
                            type="date"
                            class="flex-1"
                            :disabled="form.processing"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="setTodayDate"
                            :disabled="form.processing"
                        >
                            Hoy
                        </Button>
                    </div>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <DialogClose asChild>
                    <Button variant="outline" :disabled="form.processing">
                        Cancelar
                    </Button>
                </DialogClose>
                <Button
                    @click="submitPayment"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Enviando...' : 'Reportar Pago' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
