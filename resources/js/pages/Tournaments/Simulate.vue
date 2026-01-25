<script setup lang="ts">
import AllWeeks from '@/components/AllWeeks.vue';
import LeagueTable from '@/components/LeagueTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Play, PlayCircle } from 'lucide-vue-next';
import { computed } from 'vue';

type Tournament = App.Data.TournamentData;
type Match = App.Data.MatchData;
type Standing = App.Data.StandingData;

type Props = {
    tournament: Tournament;
    matches: Match[];
    standings: Standing[];
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
    return weeks.value.length > 0 ? Math.max(...weeks.value) : 0;
});

const currentWeek = computed(() => {
    return props.tournament.currentWeek;
});

const showPredictions = computed(() => {
    return currentWeek.value >= 4;
});

const fixtures = computed(() => {
    return weeks.value
        .filter((week) => week <= currentWeek.value)
        .map((week) => ({
            week,
            matches: matchesByWeek.value[week],
        }));
});

const startDate = new Date('2025-01-15');
const endDate = new Date('2025-02-28');

const formatDate = (date: Date): string => {
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

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

const playNextWeek = () => {
    // TODO: Implement backend call
    console.log('Play next week');
};

const playAllSimulation = () => {
    // TODO: Implement backend call
    console.log('Play all simulation');
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
        prediction: 0, // TODO: Calculate prediction
    }));
});
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
                        <span class="text-sm text-muted-foreground">
                            {{ formatDate(startDate) }} -
                            {{ formatDate(endDate) }}
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
            <AllWeeks :fixtures="fixtures" :current-week="currentWeek" />
        </div>

        <!-- Action Dock -->
        <div
            class="fixed right-0 bottom-0 left-0 z-50 border-t border-white/20 bg-white/10 shadow-lg backdrop-blur-lg md:left-64 dark:border-white/10 dark:bg-black/10"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-4"
            >
                <Button variant="outline" @click="playAllSimulation">
                    <PlayCircle class="mr-2 size-4" />
                    Play All Simulation
                </Button>
                <Button @click="playNextWeek">
                    <Play class="mr-2 size-4" />
                    Play Next Week
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
