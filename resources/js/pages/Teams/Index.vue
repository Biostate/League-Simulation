<script setup lang="ts">
import TeamController from '@/actions/App/Http/Controllers/TeamController';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from 'lucide-vue-next';

type Team = App.Data.TeamData;

type Props = {
    teams: Team[];
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
        title: 'Teams',
        href: '/teams',
    },
];

const deleteTeam = (teamId: number) => {
    if (confirm('Are you sure you want to delete this team?')) {
        router.delete(TeamController.destroy.url(teamId));
    }
};
</script>

<template>
    <Head title="Teams" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Teams</h1>
                <Link :href="TeamController.create.url()">
                    <Button>
                        <Plus />
                        Create Team
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
                                Logo
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Name
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
                            v-for="team in props.teams"
                            :key="team.id"
                            class="hover:bg-muted/50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <img
                                    v-if="team.logoUrl"
                                    :src="team.logoUrl"
                                    :alt="team.name"
                                    class="h-10 w-10 object-contain"
                                />
                                <div
                                    v-else
                                    class="flex h-10 w-10 items-center justify-center rounded bg-muted text-xs font-medium"
                                >
                                    {{ team.name.charAt(0) }}
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 text-sm font-medium whitespace-nowrap"
                            >
                                {{ team.name }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="TeamController.edit.url(team.id)"
                                    >
                                        <Button variant="ghost" size="icon-sm">
                                            <Edit />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        @click="deleteTeam(team.id)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="props.teams.length === 0">
                            <td
                                colspan="3"
                                class="px-6 py-4 text-center text-sm text-muted-foreground"
                            >
                                No teams found. Create your first team!
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :pagination="props.pagination" :base-url="'/teams'" />
        </div>
    </AppLayout>
</template>
