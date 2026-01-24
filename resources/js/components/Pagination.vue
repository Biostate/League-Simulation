<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

type Props = {
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    baseUrl: string;
};

const props = defineProps<Props>();

const goToPage = (page: number) => {
    router.get(props.baseUrl, { page });
};
</script>

<template>
    <div v-if="props.pagination.last_page > 1" class="flex items-center justify-between">
        <div class="text-sm text-muted-foreground">
            Showing {{ (props.pagination.current_page - 1) * props.pagination.per_page + 1 }} to
            {{ Math.min(props.pagination.current_page * props.pagination.per_page, props.pagination.total) }} of
            {{ props.pagination.total }} results
        </div>
        <div class="flex gap-2">
            <Button
                variant="outline"
                :disabled="props.pagination.current_page === 1"
                @click="goToPage(props.pagination.current_page - 1)"
            >
                Previous
            </Button>
            <Button
                variant="outline"
                :disabled="props.pagination.current_page === props.pagination.last_page"
                @click="goToPage(props.pagination.current_page + 1)"
            >
                Next
            </Button>
        </div>
    </div>
</template>
