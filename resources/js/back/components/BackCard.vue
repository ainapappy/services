<script setup lang="ts">
// Carte de la charte back-office : surface blanche sur fond #f3f3f1,
// avec en-tete optionnel (titre + description) et pied optionnel.
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <section class="back-card">
        <header
            v-if="title || description"
            class="flex flex-col gap-1 border-b border-[rgba(23,23,28,0.09)] px-6 py-4"
        >
            <h2
                class="text-base font-semibold tracking-tight text-[var(--color-ink)]"
            >
                {{ title }}
            </h2>
            <p v-if="description" class="text-sm text-[var(--color-muted)]">
                {{ description }}
            </p>
        </header>

        <div class="px-6 py-5">
            <slot />
        </div>

        <footer
            v-if="$slots.footer"
            class="flex items-center justify-end gap-3 border-t border-[rgba(23,23,28,0.09)] px-6 py-4"
        >
            <slot name="footer" />
        </footer>
    </section>
</template>
