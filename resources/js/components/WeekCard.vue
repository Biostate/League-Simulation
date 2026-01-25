<script setup lang="ts">
type Props = {
    week: number;
    matches: App.Data.MatchData[];
    isCurrentWeek: boolean;
};

defineProps<Props>();
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-lg border border-sidebar-border bg-card p-6 shadow-sm transition-all duration-300"
        :class="{
            'border-primary bg-muted/10 shadow-lg ring-2 shadow-primary/10 ring-primary/30':
                isCurrentWeek,
            'hover:border-sidebar-border hover:shadow-md': !isCurrentWeek,
        }"
    >
        <div
            v-if="isCurrentWeek"
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
            <div class="mb-5 flex items-center justify-between">
                <h3
                    class="text-lg font-bold"
                    :class="{
                        'text-primary': isCurrentWeek,
                        'text-foreground': !isCurrentWeek,
                    }"
                >
                    Week {{ week }}
                </h3>
                <div
                    v-if="isCurrentWeek"
                    class="flex size-2 rounded-full bg-primary"
                />
            </div>
            <div class="space-y-2">
                <div
                    v-for="(match, index) in matches"
                    :key="index"
                    class="flex items-center justify-between rounded-lg border border-sidebar-border bg-muted/10 px-4 py-3 transition-colors hover:bg-muted/20"
                >
                    <span
                        class="flex-1 text-right text-sm font-semibold text-foreground"
                    >
                        {{ match.homeTeam?.name || 'Unknown' }}
                    </span>
                    <div class="mx-4 flex items-center gap-2">
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
                        <span class="text-muted-foreground">-</span>
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
                        {{ match.awayTeam?.name || 'Unknown' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
