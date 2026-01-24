<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import TeamController from '@/actions/App/Http/Controllers/TeamController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'team-created': [
        team: { id: number; name: string; logoUrl?: string | null },
    ];
}>();

const page = usePage();
const name = ref('');
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const close = () => {
    emit('update:open', false);
    name.value = '';
    errors.value = {};
};

const createTeam = () => {
    processing.value = true;
    errors.value = {};

    router.post(
        TeamController.store.url(),
        {
            _token: (page.props as any).csrf_token,
            name: name.value,
        },
        {
            preserveScroll: true,
            only: [],
            onSuccess: (page) => {
                const team = (page.props as any).team;
                if (team) {
                    emit('team-created', team);
                }
                close();
            },
            onError: (pageErrors) => {
                errors.value = pageErrors as Record<string, string>;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Create Team</DialogTitle>
                <DialogDescription>
                    Add a new team that can be used in tournaments.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div class="grid gap-2">
                    <Label for="modal-team-name">Team Name</Label>
                    <Input
                        id="modal-team-name"
                        v-model="name"
                        class="mt-1 block w-full"
                        required
                        autocomplete="off"
                        placeholder="Enter team name"
                        :disabled="processing"
                    />
                    <InputError class="mt-2" :message="errors.name" />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="close" :disabled="processing">
                    Cancel
                </Button>
                <Button
                    @click="createTeam"
                    :disabled="processing || !name.trim()"
                >
                    Create Team
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
