<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import TournamentController from '@/actions/App/Http/Controllers/TournamentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TeamCreationModal from '@/components/TeamCreationModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
const searchQuery = ref('');

const filteredTeams = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) {
        return teams.value.filter(
            (team) => !selectedTeams.value.some((st) => st.id === team.id),
        );
    }
    return teams.value.filter(
        (team) =>
            !selectedTeams.value.some((st) => st.id === team.id) &&
            team.name.toLowerCase().includes(query),
    );
});

const getTeam = (teamId: number) => {
    return teams.value.find((t) => t.id === teamId);
};

const addTeam = (teamId: number) => {
    if (!selectedTeams.value.some((st) => st.id === teamId)) {
        selectedTeams.value.push({ id: teamId, strength: '' });
        searchQuery.value = '';
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
                @submit="
                    (e) => {
                        const formData = new FormData(
                            e.target as HTMLFormElement,
                        );
                        formData.append('teams', JSON.stringify(selectedTeams));
                    }
                "
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
                    <div class="flex items-center justify-between">
                        <Label>Teams</Label>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="showModal = true"
                        >
                            <Plus class="h-4 w-4" />
                            Create Team
                        </Button>
                    </div>

                    <div class="space-y-3">
                        <Label for="team-search">Search Teams</Label>
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="team-search"
                                v-model="searchQuery"
                                class="pl-9"
                                placeholder="Search teams..."
                                autocomplete="off"
                            />
                        </div>
                    </div>

                    <div
                        v-if="filteredTeams.length > 0"
                        class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4"
                    >
                        <Card
                            v-for="team in filteredTeams"
                            :key="team.id"
                            class="cursor-pointer transition-all hover:border-primary"
                            @click="addTeam(team.id)"
                        >
                            <CardContent class="flex items-center gap-2 p-3">
                                <Avatar class="h-8 w-8 shrink-0">
                                    <AvatarImage
                                        v-if="team.logoUrl"
                                        :src="team.logoUrl"
                                        :alt="team.name"
                                    />
                                    <AvatarFallback class="text-xs">
                                        {{ team.name.charAt(0) }}
                                    </AvatarFallback>
                                </Avatar>
                                <p class="truncate text-sm font-medium">
                                    {{ team.name }}
                                </p>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="ml-auto h-6 w-6 shrink-0 p-0"
                                    @click.stop="addTeam(team.id)"
                                >
                                    <Plus class="h-3 w-3" />
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    <div
                        v-else-if="searchQuery && filteredTeams.length === 0"
                        class="rounded-lg border border-dashed p-6 text-center"
                    >
                        <p class="text-sm text-muted-foreground">
                            No teams found matching "{{ searchQuery }}"
                        </p>
                    </div>

                    <div
                        v-else-if="teams.length === 0"
                        class="rounded-lg border border-dashed p-6 text-center"
                    >
                        <p class="text-sm text-muted-foreground">
                            No teams available. Create your first team to get
                            started.
                        </p>
                    </div>

                    <div
                        v-if="selectedTeams.length > 0"
                        class="space-y-3 rounded-lg border-2 border-primary/20 bg-muted/30 p-4"
                    >
                        <Label class="text-base font-semibold"
                            >Team Strength Points</Label
                        >
                        <div class="space-y-2">
                            <div
                                v-for="selectedTeam in selectedTeams"
                                :key="selectedTeam.id"
                                class="flex items-center gap-3 rounded-md border bg-background p-3"
                            >
                                <Avatar class="h-10 w-10 shrink-0">
                                    <AvatarImage
                                        v-if="getTeam(selectedTeam.id)?.logoUrl"
                                        :src="getTeam(selectedTeam.id)?.logoUrl"
                                        :alt="getTeam(selectedTeam.id)?.name"
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
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 w-8 shrink-0 p-0"
                                    @click="removeTeam(selectedTeam.id)"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

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
                @team-created="
                    (team) => {
                        teams.value.push({
                            id: team.id,
                            name: team.name,
                            createdAt: null,
                            updatedAt: null,
                            logoUrl: team.logoUrl || null,
                        });
                        addTeam(team.id);
                    }
                "
            />
        </div>
    </AppLayout>
</template>
