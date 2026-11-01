<script setup lang="ts">
import { Shuffle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import TeamStrengthRow from './TeamStrengthRow.vue';

type Team = App.Data.TeamData;

type SelectedTeam = {
    id: number;
    strength: string | number;
};

type Props = {
    teams: Team[];
    selectedTeams: SelectedTeam[];
    showRandomize?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showRandomize: true,
});

const emit = defineEmits<{
    'update-strength': [teamId: number, strength: string | number];
    randomize: [];
}>();

const getTeam = (teamId: number) => {
    return props.teams.find((t) => t.id === teamId);
};

const handleStrengthUpdate = (teamId: number, strength: string | number) => {
    emit('update-strength', teamId, strength);
};
</script>

<template>
    <div
        class="space-y-3 rounded-lg border-2 border-primary/20 bg-muted/30 p-4"
    >
        <div class="flex items-center justify-between">
            <Label class="text-base font-semibold">Team Strength Points</Label>
            <Button
                v-if="showRandomize && selectedTeams.length > 0"
                type="button"
                variant="outline"
                size="sm"
                @click="emit('randomize')"
            >
                <Shuffle class="mr-2 h-4 w-4" />
                Randomize Strength Points
            </Button>
        </div>
        <div v-if="selectedTeams.length > 0" class="space-y-2">
            <TeamStrengthRow
                v-for="selectedTeam in selectedTeams"
                :key="selectedTeam.id"
                :team="getTeam(selectedTeam.id)!"
                :strength="selectedTeam.strength"
                @update:strength="
                    (value) => handleStrengthUpdate(selectedTeam.id, value)
                "
            />
        </div>
        <div v-else class="py-8 text-center text-sm text-muted-foreground">
            Select teams above to set their strength points.
        </div>
    </div>
</template>
