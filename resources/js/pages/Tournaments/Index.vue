<script setup lang="ts">
import TournamentController from '@/actions/App/Http/Controllers/TournamentController';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Play, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

type Tournament = App.Data.TournamentData;

type Props = {
    tournaments: Tournament[];
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/tournaments',
    },
];

const showDeleteDialog = ref(false);
const tournamentToDelete = ref<number | null>(null);

const openDeleteDialog = (tournamentId: number) => {
    tournamentToDelete.value = tournamentId;
    showDeleteDialog.value = true;
};

const closeDeleteDialog = () => {
    showDeleteDialog.value = false;
    tournamentToDelete.value = null;
};

const confirmDelete = () => {
    if (tournamentToDelete.value) {
        router.delete(
            TournamentController.destroy.url(tournamentToDelete.value),
        );
        closeDeleteDialog();
    }
};

const getStatusBadgeVariant = (status: App.Enums.TournamentStatus) => {
    switch (status) {
        case 'created':
            return 'secondary';
        case 'in_progress':
            return 'default';
        case 'completed':
            return 'outline';
        default:
            return 'secondary';
    }
};

const getTournamentName = () => {
    if (!tournamentToDelete.value) {
        return '';
    }
    return (
        props.tournaments.find((t) => t.id === tournamentToDelete.value)
            ?.name || ''
    );
};
</script>

<template>
    <Head title="Tournaments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Tournaments</h1>
                <Link :href="TournamentController.create.url()">
                    <Button>
                        <Plus />
                        Create Tournament
                    </Button>
                </Link>
            </div>

            <div
                class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Teams
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border"
                    >
                        <tr
                            v-for="tournament in props.tournaments"
                            :key="tournament.id"
                            class="hover:bg-muted/50"
                        >
                            <td
                                class="px-6 py-4 text-sm font-medium whitespace-nowrap"
                            >
                                {{ tournament.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Badge
                                    :variant="
                                        getStatusBadgeVariant(tournament.status)
                                    "
                                >
                                    {{ tournament.status.replace('_', ' ') }}
                                </Badge>
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-muted-foreground"
                            >
                                {{ tournament.teams?.length || 0 }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            TournamentController.simulate.url(
                                                tournament.id,
                                            )
                                        "
                                    >
                                        <Button variant="ghost" size="icon-sm">
                                            <Play />
                                        </Button>
                                    </Link>
                                    <Link
                                        :href="
                                            TournamentController.edit.url(
                                                tournament.id,
                                            )
                                        "
                                    >
                                        <Button variant="ghost" size="icon-sm">
                                            <Edit />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        @click="openDeleteDialog(tournament.id)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="props.tournaments.length === 0">
                            <td
                                colspan="4"
                                class="px-6 py-4 text-center text-sm text-muted-foreground"
                            >
                                No tournaments found. Create your first
                                tournament!
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                :pagination="props.pagination"
                :base-url="'/tournaments'"
            />

            <Dialog
                :open="showDeleteDialog"
                @update:open="showDeleteDialog = $event"
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Tournament</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "<strong>{{
                                getTournamentName()
                            }}</strong
                            >"? This action cannot be undone and will remove all
                            associated team relationships.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <Button variant="outline" @click="closeDeleteDialog">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="confirmDelete">
                            Delete Tournament
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
