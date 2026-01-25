<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import TournamentController from '@/actions/App/Http/Controllers/TournamentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TeamCreationModal from '@/components/TeamCreationModal.vue';
import TeamSelector from '@/components/TeamSelector.vue';
import TeamStrengthTable from '@/components/TeamStrengthTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

type Team = App.Data.TeamData;
type Tournament = App.Data.TournamentData;

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

const teams = ref<Team[]>([...props.teams]);
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

const handleTeamCreated = (team: {
    id: number;
    name: string;
    logoUrl?: string | null;
}) => {
    teams.value.push({
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
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Heading
                variant="small"
                title="Edit Tournament"
                description="Update tournament information and team assignments"
            />

            <Form
                v-bind="TournamentController.update.form(props.tournament.id)"
                class="max-w-4xl space-y-6"
                v-slot="{ errors, processing, recentlySuccessful }"
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

                <input
                    type="hidden"
                    name="teams"
                    :value="JSON.stringify(selectedTeams)"
                />

                <div
                    v-if="!isEditable"
                    class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20"
                >
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        This tournament cannot be updated. Only tournaments with
                        "created" status can be modified.
                    </p>
                </div>

                <div
                    class="space-y-4"
                    :class="{ 'pointer-events-none opacity-50': !isEditable }"
                >
                    <TeamSelector
                        :teams="teams"
                        :selected-teams="selectedTeams"
                        :show-create-button="isEditable"
                        @add-team="addTeam"
                        @remove-team="removeTeam"
                        @create-team="showModal = true"
                    />

                    <TeamStrengthTable
                        :teams="teams"
                        :selected-teams="selectedTeams"
                        :show-randomize="isEditable"
                        @update-strength="updateStrength"
                    />

                    <InputError class="mt-2" :message="errors.teams" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing || !isEditable"
                        >Update Tournament</Button
                    >
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
