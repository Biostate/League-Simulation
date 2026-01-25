<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Team = App.Data.TeamData;

type Props = {
    team: Team;
    strength: string | number;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:strength': [value: string | number];
}>();
</script>

<template>
    <div class="flex items-center gap-3 rounded-md border bg-background p-3">
        <Avatar class="h-10 w-10 shrink-0">
            <AvatarImage
                v-if="team.logoUrl"
                :src="team.logoUrl"
                :alt="team.name"
            />
            <AvatarFallback>
                {{ team.name?.charAt(0) }}
            </AvatarFallback>
        </Avatar>
        <span class="flex-1 font-medium">{{ team.name }}</span>
        <div class="flex items-center gap-2">
            <Label
                :for="`strength-${team.id}`"
                class="text-sm text-muted-foreground"
            >
                Strength:
            </Label>
            <Input
                :id="`strength-${team.id}`"
                type="text"
                :model-value="strength"
                @update:model-value="emit('update:strength', $event)"
                class="w-24"
            />
        </div>
    </div>
</template>
