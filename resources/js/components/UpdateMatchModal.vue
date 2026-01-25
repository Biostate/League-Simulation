<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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

type Match = App.Data.MatchData;

const props = defineProps<{
    open: boolean;
    match: Match | null;
    tournamentId: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    updated: [];
}>();

const page = usePage();
const homeScore = ref<number | null>(null);
const awayScore = ref<number | null>(null);
const errors = ref<Record<string, string>>({});
const processing = ref(false);

watch(
    () => props.match,
    (match) => {
        if (match) {
            homeScore.value = match.homeScore;
            awayScore.value = match.awayScore;
        } else {
            homeScore.value = null;
            awayScore.value = null;
        }
        errors.value = {};
    },
    { immediate: true },
);

const close = () => {
    emit('update:open', false);
    errors.value = {};
};

const updateMatch = () => {
    if (!props.match) {
        return;
    }

    processing.value = true;
    errors.value = {};

    router.put(
        `/tournaments/${props.tournamentId}/matches/${props.match.id}`,
        {
            home_score: homeScore.value,
            away_score: awayScore.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('updated');
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
                <DialogTitle>Update Match</DialogTitle>
                <DialogDescription>
                    Update the scores for this match.
                </DialogDescription>
            </DialogHeader>

            <div v-if="props.match" class="space-y-4 py-4">
                <div
                    class="flex items-center justify-between rounded-lg border p-4"
                >
                    <span class="font-semibold">
                        {{ props.match.homeTeam?.name || 'Unknown' }}
                    </span>
                    <span class="text-muted-foreground">vs</span>
                    <span class="font-semibold">
                        {{ props.match.awayTeam?.name || 'Unknown' }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="home-score">
                            {{ props.match.homeTeam?.name || 'Home' }} Score
                        </Label>
                        <Input
                            id="home-score"
                            v-model.number="homeScore"
                            type="number"
                            min="0"
                            class="mt-1 block w-full"
                            autocomplete="off"
                            placeholder="0"
                            :disabled="processing"
                        />
                        <InputError class="mt-2" :message="errors.home_score" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="away-score">
                            {{ props.match.awayTeam?.name || 'Away' }} Score
                        </Label>
                        <Input
                            id="away-score"
                            v-model.number="awayScore"
                            type="number"
                            min="0"
                            class="mt-1 block w-full"
                            autocomplete="off"
                            placeholder="0"
                            :disabled="processing"
                        />
                        <InputError class="mt-2" :message="errors.away_score" />
                    </div>
                </div>

                <p class="text-sm text-muted-foreground">
                    Leave both scores empty to mark the match as unplayed.
                </p>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="close" :disabled="processing">
                    Cancel
                </Button>
                <Button @click="updateMatch" :disabled="processing">
                    {{ processing ? 'Updating...' : 'Update Match' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
