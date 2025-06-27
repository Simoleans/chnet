<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useNotifications } from '@/composables/useNotifications';
import { useBcvStore } from '@/stores/bcv';
import { storeToRefs } from 'pinia';
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    open?: boolean;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
});

const emit = defineEmits<Emits>();

// Estados
const searchCode = ref('');
const searchLoading = ref(false);
const userData = ref(null);
const imageFile = ref(null);

// Store BCV
const bcvStore = useBcvStore();
const { bcv } = storeToRefs(bcvStore);

const form = useForm({
    user_id: '',
    reference: '',
    nationality: 'V',
    id_number: '',
    bank: '',
    phone: '',
    amount: '',
    payment_date: '',
    image: null,
});

// Búsqueda en tiempo real
watch(searchCode, async (newCode) => {
    if (newCode.length >= 3) {
        await searchUser(newCode);
    } else {
        userData.value = null;
        clearForm();
    }
});

const searchUser = async (code: string) => {
    searchLoading.value = true;
    try {
        const response = await fetch(`/api/users/search/${code}`);
        const data = await response.json();

        if (data.success) {
            userData.value = data.data;
            // Auto-llenar algunos campos
            form.amount = calculateTotalAmount();
        } else {
            userData.value = null;
            notify({
                message: data.message,
                type: 'warning',
                duration: 2000,
            });
        }
    } catch (error) {
        console.error('Error searching user:', error);
        userData.value = null;
    } finally {
        searchLoading.value = false;
    }
};

const calculateTotalAmount = () => {
    if (!userData.value || !bcv.value) return '';

    const totalDebtUSD = userData.value.total_debt;
    return (totalDebtUSD * parseFloat(bcv.value)).toFixed(2);
};

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        imageFile.value = file;
        form.image = file;
    }
};

const setTodayDate = () => {
    const today = new Date();
    form.payment_date = today.toISOString().split('T')[0];
};

const clearForm = () => {
    form.reset();
    imageFile.value = null;
};

const submitPayment = () => {
    if (!userData.value) {
        notify({
            message: 'Debe buscar un usuario válido primero',
            type: 'error',
            duration: 2000,
        });
        return;
    }

    form.user_id = userData.value.id;

    form.post(route('payments.store'), {
        onSuccess: () => {
            notify({
                message: 'Pago reportado exitosamente',
                type: 'success',
                duration: 2000,
            });
            emit('update:open', false);
            resetModal();
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

const resetModal = () => {
    searchCode.value = '';
    userData.value = null;
    clearForm();
};

const handleOpenChange = (open: boolean) => {
    emit('update:open', open);
    if (!open) {
        resetModal();
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-lg max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Pago Rápido</DialogTitle>
                <DialogDescription>
                    Busque al cliente por código y registre su pago
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Búsqueda de usuario -->
                <div class="space-y-2">
                    <Label for="search_code">Código del Cliente</Label>
                    <Input
                        id="search_code"
                        v-model="searchCode"
                        placeholder="Ingrese el código del cliente"
                        :disabled="form.processing"
                    />
                    <div v-if="searchLoading" class="text-sm text-gray-500">
                        Buscando...
                    </div>
                </div>

                <!-- Información del usuario encontrado -->
                <div v-if="userData" class="bg-gray-50 p-4 rounded-lg space-y-2">
                    <h4 class="font-semibold">Cliente Encontrado:</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><strong>Nombre:</strong> {{ userData.name }}</div>
                        <div><strong>Código:</strong> {{ userData.code }}</div>
                        <div><strong>Zona:</strong> {{ userData.zone }}</div>
                        <div><strong>Plan:</strong> {{ userData.plan?.name }} (${{ userData.plan?.price }})</div>
                        <div class="col-span-2">
                            <strong>Deuda Total:</strong>
                            <span class="text-red-600 font-bold">
                                ${{ userData.total_debt }}
                                <span v-if="bcv">({{ (userData.total_debt * parseFloat(bcv)).toFixed(2) }} Bs)</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Datos bancarios -->
                <div v-if="userData" class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold mb-2">Datos para Pago Móvil:</h4>
                    <div class="text-sm space-y-1">
                        <div>🏦 <strong>Banco:</strong> Nacional de Crédito (0191)</div>
                        <div>👤 <strong>RIF:</strong> J-12569785-7</div>
                        <div>📞 <strong>Teléfono:</strong> 0412-0355541</div>
                    </div>
                </div>

                <!-- Formulario de pago -->
                <div v-if="userData" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="reference">Referencia del Pago</Label>
                        <Input
                            id="reference"
                            v-model="form.reference"
                            placeholder="Número de referencia del pago"
                            :disabled="form.processing"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="nationality">Nacionalidad</Label>
                            <select
                                id="nationality"
                                v-model="form.nationality"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                :disabled="form.processing"
                            >
                                <option value="V">V - Venezolano</option>
                                <option value="E">E - Extranjero</option>
                                <option value="J">J - Jurídico</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="id_number">Cédula/RIF</Label>
                            <Input
                                id="id_number"
                                v-model="form.id_number"
                                placeholder="Número de cédula o RIF"
                                :disabled="form.processing"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="bank">Banco</Label>
                            <Input
                                id="bank"
                                v-model="form.bank"
                                placeholder="Banco emisor del pago"
                                :disabled="form.processing"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">Teléfono</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="Teléfono asociado al pago"
                                :disabled="form.processing"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="amount">Monto (Bs)</Label>
                            <Input
                                id="amount"
                                type="number"
                                step="0.01"
                                v-model="form.amount"
                                :placeholder="`Monto sugerido: ${calculateTotalAmount()}`"
                                :disabled="form.processing"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="payment_date">Fecha de Pago</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="payment_date"
                                    type="date"
                                    v-model="form.payment_date"
                                    :disabled="form.processing"
                                    class="flex-1"
                                />
                                <Button
                                    type="button"
                                    @click="setTodayDate"
                                    size="sm"
                                    variant="outline"
                                >
                                    Hoy
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="image">Captura del Pago (Opcional)</Label>
                        <Input
                            id="image"
                            type="file"
                            accept="image/*"
                            @change="handleImageUpload"
                            :disabled="form.processing"
                        />
                        <div v-if="imageFile" class="text-sm text-green-600">
                            ✓ Imagen seleccionada: {{ imageFile.name }}
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter v-if="userData">
                <DialogClose asChild>
                    <Button variant="outline">Cancelar</Button>
                </DialogClose>
                <Button
                    @click="submitPayment"
                    :disabled="form.processing || !form.reference || !form.amount"
                >
                    {{ form.processing ? 'Procesando...' : 'Registrar Pago' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
