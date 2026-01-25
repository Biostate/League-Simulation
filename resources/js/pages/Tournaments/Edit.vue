<script setup lang="ts">
import TournamentController from '@/actions/App/Http/Controllers/TournamentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TeamCreationModal from '@/components/TeamCreationModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

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

const availableTeams = computed(() => {
    return teams.value.filter(
        (team) => !selectedTeams.value.some((st) => st.id === team.id),
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

const getTeam = (teamId: number) => {
    return teams.value.find((t) => t.id === teamId);
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
                    <div class="flex items-center justify-between">
                        <Label
                            class="text-sm font-medium tracking-wider text-muted-foreground uppercase"
                            >Teams</Label
                        >
                        <Button
                            type="button"
                            variant="outline"
                            @click="showModal = true"
                        >
                            <Plus />
                            Create a Team
                        </Button>
                    </div>

                    <div class="flex items-start gap-4 overflow-x-auto pb-2">
                        <!-- Selected Teams (Full Color) -->
                        <div
                            v-for="selectedTeam in selectedTeams"
                            :key="selectedTeam.id"
                            class="flex shrink-0 flex-col items-center gap-2"
                        >
                            <div class="relative">
                                <Avatar class="size-16">
                                    <AvatarImage
                                        v-if="getTeam(selectedTeam.id)?.logoUrl"
                                        :src="
                                            getTeam(selectedTeam.id)!.logoUrl!
                                        "
                                        :alt="
                                            getTeam(selectedTeam.id)?.name || ''
                                        "
                                    />
                                    <AvatarFallback class="text-lg">
                                        {{
                                            getTeam(
                                                selectedTeam.id,
                                            )?.name?.charAt(0)
                                        }}
                                    </AvatarFallback>
                                </Avatar>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="absolute -top-1 -right-1 size-6 rounded-full bg-background p-0 shadow-sm hover:bg-destructive hover:text-destructive-foreground"
                                    @click="removeTeam(selectedTeam.id)"
                                >
                                    <X class="size-3" />
                                </Button>
                            </div>
                            <span class="text-sm font-semibold">{{
                                getTeam(selectedTeam.id)?.name
                            }}</span>
                        </div>

                        <!-- Available Teams (Grayscale with + overlay) -->
                        <div
                            v-for="team in availableTeams"
                            :key="team.id"
                            class="relative flex shrink-0 cursor-pointer flex-col items-center gap-2"
                            @click="addTeam(team.id)"
                        >
                            <div class="relative">
                                <Avatar
                                    class="size-16"
                                    style="filter: grayscale(100%)"
                                >
                                    <AvatarImage
                                        v-if="team.logoUrl"
                                        :src="team.logoUrl"
                                        :alt="team.name"
                                    />
                                    <AvatarFallback class="text-lg">
                                        {{ team.name.charAt(0) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div
                                    class="absolute -right-1 -bottom-1 flex size-6 items-center justify-center rounded-full bg-background shadow-sm"
                                >
                                    <Plus class="size-4 text-foreground" />
                                </div>
                            </div>
                            <span class="text-sm font-semibold">{{
                                team.name
                            }}</span>
                        </div>

                        <!-- Add Button -->
                        <button
                            type="button"
                            class="flex shrink-0 flex-col items-center gap-2 transition-colors hover:opacity-80"
                            @click="showModal = true"
                        >
                            <div
                                class="flex size-16 items-center justify-center rounded-full border-2 border-dashed border-muted-foreground/40 bg-transparent"
                            >
                                <Plus class="size-8 text-muted-foreground" />
                            </div>
                            <span class="text-sm font-semibold">Add</span>
                        </button>
                    </div>

                    <div
                        class="space-y-3 rounded-lg border-2 border-primary/20 bg-muted/30 p-4"
                    >
                        <Label class="text-base font-semibold"
                            >Team Strength Points</Label
                        >
                        <div v-if="selectedTeams.length > 0" class="space-y-2">
                            <div
                                v-for="selectedTeam in selectedTeams"
                                :key="selectedTeam.id"
                                class="flex items-center gap-3 rounded-md border bg-background p-3"
                            >
                                <Avatar class="h-10 w-10 shrink-0">
                                    <AvatarImage
                                        v-if="getTeam(selectedTeam.id)?.logoUrl"
                                        :src="
                                            getTeam(selectedTeam.id)!.logoUrl!
                                        "
                                        :alt="
                                            getTeam(selectedTeam.id)?.name || ''
                                        "
                                    />
                                    <AvatarFallback>
                                        {{
                                            getTeam(
                                                selectedTeam.id,
                                            )?.name?.charAt(0)
                                        }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="flex-1 font-medium">
                                    {{ getTeam(selectedTeam.id)?.name }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <Label
                                        :for="`strength-${selectedTeam.id}`"
                                        class="text-sm text-muted-foreground"
                                    >
                                        Strength:
                                    </Label>
                                    <Input
                                        :id="`strength-${selectedTeam.id}`"
                                        type="text"
                                        :model-value="selectedTeam.strength"
                                        @update:model-value="
                                            (value) =>
                                                updateStrength(
                                                    selectedTeam.id,
                                                    value,
                                                )
                                        "
                                        class="w-24"
                                    />
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Select teams above to set their strength points.
                        </div>
                    </div>
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
