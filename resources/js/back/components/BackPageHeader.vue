<script setup lang="ts">
// En-tete de page back-office : titre + description a gauche, actions a
// droite (slot #actions). Remplace les h1 autrefois dupliques dans chaque
// page, avec le meme rythme typographique que les templates admin shadcn.
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1.5">
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="max-w-2xl text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
