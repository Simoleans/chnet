<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { useBcvStore } from '@/stores/bcv'

const bcvStore = useBcvStore()

const props = withDefaults(defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>(),{
    breadcrumbs:()=>[]
});

</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4 justify-between"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <span class="text-[17px] font-bold min-w-[70px]">
            <template v-if="bcvStore.loading">
                <span class="inline-block h-4 w-16 animate-pulse rounded bg-muted"></span>
            </template>
            <template v-else>
                <template v-if="bcvStore.bcv"> {{ bcvStore.bcv }} Bs - {{ bcvStore.date }}</template>
                <template v-else>No disponible la tasa BCV</template>
            </template>
        </span>
    </header>
</template>
