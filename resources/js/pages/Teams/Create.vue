<script setup lang="ts">
import TeamController from '@/actions/App/Http/Controllers/TeamController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: 'Create Team',
    },
];
</script>

<template>
    <Head title="Create Team" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Heading
                variant="small"
                title="Create Team"
                description="Add a new team to your collection"
            />

            <Form
                v-bind="TeamController.store.form()"
                class="space-y-6 max-w-2xl"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="grid gap-2">
                    <Label for="name">Team Name</Label>
                    <Input
                        id="name"
                        class="mt-1 block w-full"
                        name="name"
                        required
                        autocomplete="off"
                        placeholder="Enter team name"
                    />
                    <InputError class="mt-2" :message="errors.name" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing">Create Team</Button>
                    <Link :href="TeamController.index.url()">
                        <Button variant="outline">Cancel</Button>
                    </Link>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-neutral-600"
                        >
                            Team created successfully.
                        </p>
                    </Transition>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
