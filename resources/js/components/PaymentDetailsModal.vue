<script setup>
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'

const props = defineProps({
    open: Boolean,
    payment: Object,
})

const emit = defineEmits(['update:open'])

const openImage = () => {
    if (props.payment?.image_path) {
        window.open(`/storage/${props.payment.image_path}`, '_blank')
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Detalles del Pago</DialogTitle>
                <DialogDescription>
                    Información completa del pago seleccionado
                </DialogDescription>
            </DialogHeader>

            <div v-if="payment" class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-muted-foreground">Referencia:</span>
                        <p class="font-mono">{{ payment.reference || 'Sin referencia' }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-muted-foreground">ID Pago:</span>
                        <p class="font-mono">#{{ payment.id }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-muted-foreground">Cliente:</span>
                        <p>{{ payment.user_name }}</p>
                        <p class="text-xs text-muted-foreground">Código: {{ payment.user_code }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-muted-foreground">Monto:</span>
                        <p class="font-bold text-green-600">${{ Number(payment.amount).toFixed(2) }}</p>
                        <p class="text-xs text-muted-foreground">
                            Bs. {{ Number(payment.amount_bs).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                        </p>
                    </div>
                    <div>
                        <span class="font-medium text-muted-foreground">Banco:</span>
                        <p>{{ payment.bank }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-muted-foreground">Teléfono:</span>
                        <p>{{ payment.phone }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-muted-foreground">Cédula/RIF:</span>
                        <p>{{ payment.id_number }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-muted-foreground">Fecha de Pago:</span>
                        <p>{{ payment.payment_date }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-muted-foreground">Período:</span>
                        <p>{{ payment.invoice_period }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-muted-foreground">Registrado:</span>
                        <p>{{ payment.created_at }}</p>
                    </div>
                </div>

                <div v-if="payment.image_path" class="border-t pt-4">
                    <span class="font-medium text-muted-foreground">Comprobante:</span>
                    <div class="mt-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="openImage"
                        >
                            📎 Ver Comprobante
                        </Button>
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
</template>
