<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Play, PlayCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AllWeeks from '@/components/AllWeeks.vue';
import LeagueTable from '@/components/LeagueTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import UpdateMatchModal from '@/components/UpdateMatchModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Tournament = App.Data.TournamentData;
type Match = App.Data.MatchData;
type Standing = App.Data.StandingData;

type Props = {
    tournament: Tournament;
    matches: Match[];
    standings: Standing[];
    predictions?: Record<number, number>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/tournaments',
    },
    {
        title: props.tournament.name,
        href: '#',
    },
    {
        title: 'Simulate',
        href: '#',
    },
];

const matchesByWeek = computed(() => {
    const grouped: Record<number, Match[]> = {};
    props.matches.forEach((match) => {
        if (!grouped[match.week]) {
            grouped[match.week] = [];
        }
        grouped[match.week].push(match);
    });
    return grouped;
});

const weeks = computed(() => {
    return Object.keys(matchesByWeek.value)
        .map(Number)
        .sort((a, b) => b - a);
});

const totalWeeks = computed(() => {
    return props.tournament.totalWeeks;
});

const currentWeek = computed(() => {
    return props.tournament.currentWeek;
});

const showPredictions = computed(() => {
    return currentWeek.value >= 4;
});

const fixtures = computed(() => {
    return weeks.value
        .filter((week) => week <= currentWeek.value + 1)
        .map((week) => ({
            week,
            matches: matchesByWeek.value[week],
        }));
});

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

const isCompleted = computed(() => {
    return props.tournament.status === 'completed';
});

const canPlay = computed(() => {
    return !isCompleted.value && currentWeek.value < totalWeeks.value;
});

const processingNextWeek = ref(false);
const processingAllWeeks = ref(false);
const processingRollback = ref(false);
const selectedMatch = ref<Match | null>(null);
const isModalOpen = ref(false);

const playNextWeek = () => {
    if (processingNextWeek.value) {
        return;
    }

    processingNextWeek.value = true;

    router.post(
        `/tournaments/${props.tournament.id}/play-next-week`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ['tournament', 'matches', 'standings', 'predictions'],
                });
            },
            onFinish: () => {
                processingNextWeek.value = false;
            },
        },
    );
};

const playAllSimulation = () => {
    if (processingAllWeeks.value) {
        return;
    }

    processingAllWeeks.value = true;

    router.post(
        `/tournaments/${props.tournament.id}/play-all-weeks`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ['tournament', 'matches', 'standings', 'predictions'],
                });
            },
            onFinish: () => {
                processingAllWeeks.value = false;
            },
        },
    );
};

const leagueTableData = computed(() => {
    return props.standings.map((standing) => ({
        team: standing.team?.name || 'Unknown',
        pts: standing.points,
        played: standing.played,
        wins: standing.won,
        draws: standing.drawn,
        losses: standing.lost,
        goalDifference: standing.goalDifference,
        prediction: props.predictions?.[standing.teamId] ?? 0,
    }));
});

const handleMatchClick = (match: Match) => {
    selectedMatch.value = match;
    isModalOpen.value = true;
};

const handleMatchUpdated = () => {
    router.reload({ only: ['matches', 'standings'] });
};

const handleRollbackWeek = (week: number) => {
    if (processingRollback.value) {
        return;
    }

    processingRollback.value = true;

    router.post(
        `/tournaments/${props.tournament.id}/rollback-week/${week}`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ['tournament', 'matches', 'standings', 'predictions'],
                });
            },
            onFinish: () => {
                processingRollback.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`Simulate - ${tournament.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 pb-24"
        >
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div class="flex flex-col gap-2">
                    <h1 class="text-3xl font-bold">{{ tournament.name }}</h1>
                    <div class="flex flex-wrap items-center gap-4">
                        <Badge
                            :variant="getStatusBadgeVariant(tournament.status)"
                        >
                            {{ tournament.status.replace('_', ' ') }}
                        </Badge>
                        <span class="text-sm text-muted-foreground">
                            Week {{ currentWeek }}/{{ totalWeeks }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- League Table -->
            <LeagueTable
                :teams="leagueTableData"
                :show-predictions="showPredictions"
            />

            <!-- All Weeks Section -->
            <AllWeeks
                :fixtures="fixtures"
                :current-week="currentWeek"
                @match-clicked="handleMatchClick"
                @rollback-week="handleRollbackWeek"
            />
        </div>

        <!-- Update Match Modal -->
        <UpdateMatchModal
            v-model:open="isModalOpen"
            :match="selectedMatch"
            :tournament-id="tournament.id"
            @updated="handleMatchUpdated"
        />

        <!-- Action Dock -->
        <div
            class="fixed right-0 bottom-0 left-0 z-50 border-t border-white/20 bg-white/10 shadow-lg backdrop-blur-lg md:left-64 dark:border-white/10 dark:bg-black/10"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-4"
            >
                <Button
                    variant="outline"
                    @click="playAllSimulation"
                    :disabled="
                        !canPlay || processingAllWeeks || processingNextWeek
                    "
                >
                    <PlayCircle class="mr-2 size-4" />
                    {{
                        processingAllWeeks
                            ? 'Playing...'
                            : 'Play All Simulation'
                    }}
                </Button>
                <Button
                    @click="playNextWeek"
                    :disabled="
                        !canPlay || processingNextWeek || processingAllWeeks
                    "
                >
                    <Play class="mr-2 size-4" />
                    {{ processingNextWeek ? 'Playing...' : 'Play Next Week' }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
