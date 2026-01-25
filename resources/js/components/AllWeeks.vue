<script setup lang="ts">
import WeekCard from './WeekCard.vue';

type Fixture = {
    week: number;
    matches: App.Data.MatchData[];
};

type Props = {
    fixtures: Fixture[];
    currentWeek: number;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'match-clicked': [match: App.Data.MatchData];
    'rollback-week': [week: number];
}>();

const handleMatchClick = (match: App.Data.MatchData) => {
    emit('match-clicked', match);
};

const handleRollback = (week: number) => {
    emit('rollback-week', week);
};
</script>

<template>
    <div
        class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
    >
        <div class="bg-muted/50 px-6 py-3">
            <h2 class="text-lg font-semibold">All Weeks</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <WeekCard
                    v-for="fixture in fixtures"
                    :key="fixture.week"
                    :week="fixture.week"
                    :matches="fixture.matches"
                    :is-current-week="fixture.week === currentWeek"
                    :current-week="props.currentWeek"
                    @match-clicked="handleMatchClick"
                    @rollback-week="handleRollback"
                />
            </div>
        </div>
    </div>
</template>
