<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Play, PlayCircle } from 'lucide-vue-next';
import { computed } from 'vue';

type Tournament = App.Data.TournamentData;
type Match = App.Data.MatchData;

type Props = {
    tournament: Tournament;
    matches: Match[];
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
    const playedWeeks = props.matches
        .filter((match) => match.isPlayed)
        .map((match) => match.week);

    if (playedWeeks.length === 0) {
        return 1;
    }

    const maxPlayedWeek = Math.max(...playedWeeks);
    const hasUnplayedMatches = props.matches.some(
        (match) => match.week === maxPlayedWeek && !match.isPlayed,
    );

    return hasUnplayedMatches ? maxPlayedWeek : maxPlayedWeek + 1;
});

const showPredictions = computed(() => {
    return currentWeek.value >= 4;
});

const fixtures = computed(() => {
    return weeks.value.map((week) => ({
        week,
        matches: matchesByWeek.value[week].map((match) => ({
            home: match.homeTeam?.name || 'Unknown',
            away: match.awayTeam?.name || 'Unknown',
            homeScore: match.homeScore,
            awayScore: match.awayScore,
        })),
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

const mockLeagueTable = [
    {
        team: 'Chelsea',
        pts: 10,
        played: 4,
        wins: 3,
        draws: 1,
        losses: 0,
        goalDifference: 14,
        prediction: 65,
    },
    {
        team: 'Arsenal',
        pts: 8,
        played: 4,
        wins: 2,
        draws: 2,
        losses: 0,
        goalDifference: 6,
        prediction: 25,
    },
    {
        team: 'Manchester City',
        pts: 8,
        played: 4,
        wins: 2,
        draws: 2,
        losses: 0,
        goalDifference: 4,
        prediction: 25,
    },
    {
        team: 'Liverpool',
        pts: 4,
        played: 4,
        wins: 1,
        draws: 1,
        losses: 2,
        goalDifference: 0,
        prediction: 5,
    },
];

const playNextWeek = () => {
    // TODO: Implement backend call
    console.log('Play next week');
};

const playAllSimulation = () => {
    // TODO: Implement backend call
    console.log('Play all simulation');
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
                        <span class="text-sm text-muted-foreground">
                            {{ formatDate(startDate) }} -
                            {{ formatDate(endDate) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- League Table -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <div class="bg-muted/50 px-6 py-3">
                    <h2 class="text-lg font-semibold">League Table</h2>
                </div>
                <div
                    v-if="!showPredictions"
                    class="border-b border-sidebar-border/70 bg-muted/30 px-6 py-3 dark:border-sidebar-border"
                >
                    <p class="text-sm text-muted-foreground">
                        <span class="font-medium"
                            >Predictions will be available in week 4.</span
                        >
                        Championship predictions will be shown after week 3 is
                        completed.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Teams
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    PTS
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    P
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    W
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    D
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    L
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    GD
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Prediction %
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border"
                        >
                            <tr
                                v-for="team in mockLeagueTable"
                                :key="team.team"
                                class="hover:bg-muted/50"
                            >
                                <td class="px-6 py-4 text-sm font-medium">
                                    {{ team.team }}
                                </td>
                                <td
                                    class="px-6 py-4 text-center text-sm font-bold"
                                >
                                    {{ team.pts }}
                                </td>
                                <td
                                    class="px-6 py-4 text-center text-sm text-muted-foreground"
                                >
                                    {{ team.played }}
                                </td>
                                <td
                                    class="px-6 py-4 text-center text-sm text-muted-foreground"
                                >
                                    {{ team.wins }}
                                </td>
                                <td
                                    class="px-6 py-4 text-center text-sm text-muted-foreground"
                                >
                                    {{ team.draws }}
                                </td>
                                <td
                                    class="px-6 py-4 text-center text-sm text-muted-foreground"
                                >
                                    {{ team.losses }}
                                </td>
                                <td
                                    class="px-6 py-4 text-center text-sm"
                                    :class="{
                                        'text-green-600 dark:text-green-400':
                                            team.goalDifference > 0,
                                        'text-red-600 dark:text-red-400':
                                            team.goalDifference < 0,
                                        'text-muted-foreground':
                                            team.goalDifference === 0,
                                    }"
                                >
                                    {{ team.goalDifference > 0 ? '+' : ''
                                    }}{{ team.goalDifference }}
                                </td>
                                <td
                                    class="px-6 py-4 text-center text-sm font-semibold"
                                >
                                    <span v-if="showPredictions"
                                        >{{ team.prediction }}%</span
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- All Weeks Section -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <div class="bg-muted/50 px-6 py-3">
                    <h2 class="text-lg font-semibold">All Weeks</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div
                            v-for="fixture in fixtures"
                            :key="fixture.week"
                            class="group relative overflow-hidden rounded-lg border border-sidebar-border bg-card p-6 shadow-sm transition-all duration-300"
                            :class="{
                                'border-primary bg-muted/10 shadow-lg ring-2 shadow-primary/10 ring-primary/30':
                                    fixture.week === currentWeek,
                                'hover:border-sidebar-border hover:shadow-md':
                                    fixture.week !== currentWeek,
                            }"
                        >
                            <div
                                v-if="fixture.week === currentWeek"
                                class="pointer-events-none absolute inset-0 rounded-lg opacity-60"
                                style="
                                    background: radial-gradient(
                                        ellipse 800px 600px at 0% 0%,
                                        rgba(255, 255, 255, 0.2),
                                        transparent 70%
                                    );
                                "
                            />
                            <div class="relative">
                                <div
                                    class="mb-5 flex items-center justify-between"
                                >
                                    <h3
                                        class="text-lg font-bold"
                                        :class="{
                                            'text-primary':
                                                fixture.week === currentWeek,
                                            'text-foreground':
                                                fixture.week !== currentWeek,
                                        }"
                                    >
                                        Week {{ fixture.week }}
                                    </h3>
                                    <div
                                        v-if="fixture.week === currentWeek"
                                        class="flex size-2 rounded-full bg-primary"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <div
                                        v-for="(
                                            match, index
                                        ) in fixture.matches"
                                        :key="index"
                                        class="flex items-center justify-between rounded-lg border border-sidebar-border bg-muted/10 px-4 py-3 transition-colors hover:bg-muted/20"
                                    >
                                        <span
                                            class="flex-1 text-right text-sm font-semibold text-foreground"
                                        >
                                            {{ match.home }}
                                        </span>
                                        <div
                                            class="mx-4 flex items-center gap-2"
                                        >
                                            <span
                                                v-if="match.homeScore !== null"
                                                class="min-w-[2rem] text-center text-base font-bold text-foreground"
                                            >
                                                {{ match.homeScore }}
                                            </span>
                                            <span
                                                v-else
                                                class="min-w-[2rem] text-center text-muted-foreground"
                                                >-</span
                                            >
                                            <span class="text-muted-foreground"
                                                >-</span
                                            >
                                            <span
                                                v-if="match.awayScore !== null"
                                                class="min-w-[2rem] text-center text-base font-bold text-foreground"
                                            >
                                                {{ match.awayScore }}
                                            </span>
                                            <span
                                                v-else
                                                class="min-w-[2rem] text-center text-muted-foreground"
                                                >-</span
                                            >
                                        </div>
                                        <span
                                            class="flex-1 text-left text-sm font-semibold text-foreground"
                                        >
                                            {{ match.away }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
