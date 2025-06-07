<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { useForm } from '@inertiajs/vue3';

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
import { Label } from '@/components/ui/label';
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

const payFee = () => {
    form.post(route('pay-fee'));
}

const reloadBcvRate = async () => {
    await bcvStore.$reloadBcvAmount()
}

</script>

<template>
    <Head title="Mi CHNET" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="primary">Pagar</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <form class="space-y-6" @submit.prevent="payFee">
                                <DialogHeader class="space-y-3">
                                    <DialogTitle>¿Desea pagar la cuota?</DialogTitle>
                                    <DialogDescription>
                                        Una vez que pague la cuota, se le enviará un correo de confirmación.
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-2">
                                    <Label for="client" class="sr-only">client</Label>
                                    <Input id="client" type="client" name="client" ref="clientInput" v-model="form.client" placeholder="client" />
                                    <InputError :message="form.errors.client" />
                                </div>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary" @click="closeModal"> Cancel </Button>
                                    </DialogClose>

                                    <Button variant="destructive" :disabled="form.processing">
                                        <button type="submit">Delete account</button>
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
