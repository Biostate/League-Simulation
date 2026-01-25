<script setup lang="ts">
type TeamStats = {
    team: string;
    pts: number;
    played: number;
    wins: number;
    draws: number;
    losses: number;
    goalDifference: number;
    prediction: number;
};

type Props = {
    teams: TeamStats[];
    showPredictions: boolean;
};

defineProps<Props>();
</script>

<template>
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
                        v-for="team in teams"
                        :key="team.team"
                        class="hover:bg-muted/50"
                    >
                        <td class="px-6 py-4 text-sm font-medium">
                            {{ team.team }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-bold">
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
                        <td class="px-6 py-4 text-center text-sm font-semibold">
                            <span v-if="showPredictions"
                                >{{ team.prediction }}%</span
                            >
                            <span v-else class="text-muted-foreground">-</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
