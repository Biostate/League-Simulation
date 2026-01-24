<script setup lang="ts">
import TournamentController from '@/actions/App/Http/Controllers/TournamentController';
import TeamCreationModal from '@/components/TeamCreationModal.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';

type Team = {
    id: number;
    name: string;
    createdAt: string | null;
    updatedAt: string | null;
    logoUrl: string | null;
};

type TeamTournament = {
    id: number;
    name: string;
    strength: number;
};

type Tournament = {
    id: number;
    name: string;
    status: string;
    userId: number;
    teams: TeamTournament[] | null;
};

type Props = {
    tournament: Tournament;
    teams: Team[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/tournaments',
    },
    {
        title: 'Edit Tournament',
    },
];

const selectedTeams = ref<Array<{ id: number; strength: string | number }>>([]);
const showModal = ref(false);

onMounted(() => {
    if (props.tournament.teams) {
        selectedTeams.value = props.tournament.teams.map((team) => ({
            id: team.id,
            strength: team.strength.toString(),
        }));
    }
});

const isEditable = computed(() => props.tournament.status === 'created');

const availableTeams = computed(() => {
    return props.teams.filter(
        (team) => !selectedTeams.value.some((st) => st.id === team.id)
    );
});

const addTeam = (teamId: number) => {
    if (!selectedTeams.value.some((st) => st.id === teamId)) {
        selectedTeams.value.push({ id: teamId, strength: '' });
    }
};

const removeTeam = (teamId: number) => {
    selectedTeams.value = selectedTeams.value.filter((st) => st.id !== teamId);
};

const updateStrength = (teamId: number, strength: string | number) => {
    const index = selectedTeams.value.findIndex((st) => st.id === teamId);
    if (index !== -1) {
        selectedTeams.value[index] = {
            ...selectedTeams.value[index],
            strength,
        };
    }
};

const getTeamName = (teamId: number) => {
    return props.teams.find((t) => t.id === teamId)?.name || '';
};

const getTeamLogo = (teamId: number) => {
    return props.teams.find((t) => t.id === teamId)?.logoUrl || null;
};

const handleTeamCreated = (team: { id: number; name: string }) => {
    props.teams.push({
        id: team.id,
        name: team.name,
        createdAt: null,
        updatedAt: null,
        logoUrl: team.logoUrl || null,
    });
    addTeam(team.id);
};
</script>

<template>
    <Head title="Edit Tournament" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Heading
                variant="small"
                title="Edit Tournament"
                description="Update tournament information and team assignments"
            />

            <Form
                v-bind="TournamentController.update.form(props.tournament.id)"
                class="space-y-6 max-w-4xl"
                v-slot="{ errors, processing, recentlySuccessful }"
                @submit="(e) => {
                    const formData = new FormData(e.target as HTMLFormElement);
                    formData.append('teams', JSON.stringify(selectedTeams));
                }"
            >
                <div class="grid gap-2">
                    <Label for="name">Tournament Name</Label>
                    <Input
                        id="name"
                        class="mt-1 block w-full"
                        name="name"
                        :default-value="props.tournament.name"
                        required
                        autocomplete="off"
                        placeholder="Enter tournament name"
                    />
                    <InputError class="mt-2" :message="errors.name" />
                </div>

                <input type="hidden" name="teams" :value="JSON.stringify(selectedTeams)" />

                <div v-if="!isEditable" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        This tournament cannot be updated. Only tournaments with "created" status can be modified.
                    </p>
                </div>

                <div class="space-y-4" :class="{ 'opacity-50 pointer-events-none': !isEditable }">
                    <div class="flex items-center justify-between">
                        <Label>Teams</Label>
                        <Button
                            type="button"
                            variant="outline"
                            @click="showModal = true"
                        >
                            <Plus />
                            Create a Team
                        </Button>
                    </div>

                    <div class="space-y-2">
                        <Label for="team-select">Select Team</Label>
                        <select
                            id="team-select"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            @change="(e) => {
                                const teamId = parseInt((e.target as HTMLSelectElement).value);
                                if (teamId) {
                                    addTeam(teamId);
                                    (e.target as HTMLSelectElement).value = '';
                                }
                            }"
                        >
                            <option value="">Select a team...</option>
                            <option
                                v-for="team in availableTeams"
                                :key="team.id"
                                :value="team.id"
                            >
                                {{ team.name }}
                            </option>
                        </select>
                    </div>

                    <div
                        v-if="selectedTeams.length > 0"
                        class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border overflow-hidden"
                    >
                        <table class="w-full">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                        Logo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                        Team
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                        Strength
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                <tr
                                    v-for="selectedTeam in selectedTeams"
                                    :key="selectedTeam.id"
                                    class="hover:bg-muted/50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <img
                                            v-if="getTeamLogo(selectedTeam.id)"
                                            :src="getTeamLogo(selectedTeam.id)"
                                            :alt="getTeamName(selectedTeam.id)"
                                            class="h-10 w-10 object-contain"
                                        />
                                        <div
                                            v-else
                                            class="flex h-10 w-10 items-center justify-center rounded bg-muted text-xs font-medium"
                                        >
                                            {{ getTeamName(selectedTeam.id).charAt(0) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        {{ getTeamName(selectedTeam.id) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Input
                                            type="text"
                                            :model-value="selectedTeam.strength"
                                            @update:model-value="(value) => updateStrength(selectedTeam.id, value)"
                                            class="w-24"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="removeTeam(selectedTeam.id)"
                                        >
                                            Remove
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <InputError class="mt-2" :message="errors.teams" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing || !isEditable">Update Tournament</Button>
                    <Link :href="TournamentController.index.url()">
                        <Button variant="outline">Cancel</Button>
                    </Link>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-neutral-600"
                        >
                            Tournament updated successfully.
                        </p>
                    </Transition>
                </div>
            </Form>

            <TeamCreationModal
                :open="showModal"
                @update:open="showModal = $event"
                @team-created="handleTeamCreated"
            />
        </div>
    </AppLayout>
</template>
