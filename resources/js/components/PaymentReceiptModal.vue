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

const openImageInNewTab = () => {
    if (props.payment?.image_path) {
        window.open(`/storage/${props.payment.image_path}`, '_blank')
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-4xl max-h-[90vh]">
            <DialogHeader>
                <DialogTitle>Comprobante de Pago</DialogTitle>
                <DialogDescription v-if="payment">
                    Referencia: {{ payment.reference || 'Sin referencia' }} -
                    Cliente: {{ payment.user_name }} ({{ payment.user_code }})
                </DialogDescription>
            </DialogHeader>

            <div v-if="payment?.image_path" class="flex justify-center">
                <img
                    :src="`/storage/${payment.image_path}`"
                    :alt="`Comprobante de pago ${payment.reference}`"
                    class="max-w-full max-h-[70vh] object-contain rounded-lg border"
                    @error="() => console.error('Error loading image')"
                />
            </div>

            <div v-else-if="payment" class="text-center py-8 text-gray-500">
                Este pago no tiene comprobante adjunto
            </div>

            <DialogFooter>
                <DialogClose asChild>
                    <Button variant="outline">Cerrar</Button>
                </DialogClose>
                <Button
                    v-if="payment?.image_path"
                    @click="openImageInNewTab"
                >
                    Abrir en nueva pestaña
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
