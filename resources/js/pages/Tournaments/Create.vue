<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
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

type Props = {
    teams: Team[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/tournaments',
    },
    {
        title: 'Create Tournament',
    },
];

const teams = ref<Team[]>([...props.teams]);
const selectedTeams = ref<Array<{ id: number; strength: string | number }>>([]);
const showModal = ref(false);

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

const randomizeStrengths = () => {
    selectedTeams.value = selectedTeams.value.map((team) => ({
        ...team,
        strength: Math.floor(Math.random() * 100) + 1,
    }));
};
</script>

<template>
    <Head title="Create Tournament" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Heading
                variant="small"
                title="Create Tournament"
                description="Create a new tournament and assign teams with strength points"
            />

            <Form
                v-bind="TournamentController.store.form()"
                class="max-w-4xl space-y-6"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="grid gap-2">
                    <Label for="name">Tournament Name</Label>
                    <Input
                        id="name"
                        class="mt-1 block w-full"
                        name="name"
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

                <div class="space-y-4">
                    <TeamSelector
                        :teams="teams"
                        :selected-teams="selectedTeams"
                        @add-team="addTeam"
                        @remove-team="removeTeam"
                        @create-team="showModal = true"
                    />

                    <TeamStrengthTable
                        :teams="teams"
                        :selected-teams="selectedTeams"
                        @update-strength="updateStrength"
                        @randomize="randomizeStrengths"
                    />

                    <InputError class="mt-2" :message="errors.teams" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing">Create Tournament</Button>
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
                            Tournament created successfully.
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
