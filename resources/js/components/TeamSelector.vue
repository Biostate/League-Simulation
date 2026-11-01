<script setup lang="ts">
import { Plus, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Team = App.Data.TeamData;

type SelectedTeam = {
    id: number;
    strength: string | number;
};

type Props = {
    teams: Team[];
    selectedTeams: SelectedTeam[];
    showCreateButton?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showCreateButton: true,
});

const emit = defineEmits<{
    'add-team': [teamId: number];
    'remove-team': [teamId: number];
    'create-team': [];
}>();

const availableTeams = computed(() => {
    return props.teams.filter(
        (team) => !props.selectedTeams.some((st) => st.id === team.id),
    );
});

const getTeam = (teamId: number) => {
    return props.teams.find((t) => t.id === teamId);
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <Label
                class="text-sm font-medium tracking-wider text-muted-foreground uppercase"
            >
                Teams
            </Label>
            <Button
                v-if="showCreateButton"
                type="button"
                variant="outline"
                size="sm"
                @click="emit('create-team')"
            >
                <Plus class="h-4 w-4" />
                Create Team
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
                            :src="getTeam(selectedTeam.id)!.logoUrl!"
                            :alt="getTeam(selectedTeam.id)?.name || ''"
                        />
                        <AvatarFallback class="text-lg">
                            {{ getTeam(selectedTeam.id)?.name?.charAt(0) }}
                        </AvatarFallback>
                    </Avatar>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="absolute -top-1 -right-1 size-6 rounded-full bg-background p-0 shadow-sm hover:bg-destructive hover:text-destructive-foreground"
                        @click="emit('remove-team', selectedTeam.id)"
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
                @click="emit('add-team', team.id)"
            >
                <div class="relative">
                    <Avatar class="size-16" style="filter: grayscale(100%)">
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
                <span class="text-sm font-semibold">{{ team.name }}</span>
            </div>

            <!-- Add Button -->
            <button
                v-if="showCreateButton"
                type="button"
                class="flex shrink-0 flex-col items-center gap-2 transition-colors hover:opacity-80"
                @click="emit('create-team')"
            >
                <div
                    class="flex size-16 items-center justify-center rounded-full border-2 border-dashed border-muted-foreground/40 bg-transparent"
                >
                    <Plus class="size-8 text-muted-foreground" />
                </div>
                <span class="text-sm font-semibold">Add</span>
            </button>
        </div>
    </div>
</template>
