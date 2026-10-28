<script setup lang="ts">
// Bascule de theme binaire de la vitrine (clair <-> sombre) : bouton unique
// appuye sur useAppearance, la meme source que le back-office (localStorage +
// cookie + classe .dark sur <html>, script anti-flash du blade). Composant
// front : l'arbre back/components reste etanche.
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { Button } from '@/components/ui/button';
import { MoonIcon, SunIcon } from '@lucide/vue';

const { resolvedAppearance, updateAppearance } = useAppearance();

const label = computed(() =>
    resolvedAppearance.value === 'dark'
        ? 'Passer en thème clair'
        : 'Passer en thème sombre',
);

function toggleTheme(): void {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        :aria-label="label"
        :title="label"
        @click="toggleTheme"
    >
        <SunIcon
            class="size-4 scale-100 rotate-0 transition-all dark:scale-0 dark:-rotate-90"
        />
        <MoonIcon
            class="absolute size-4 scale-0 rotate-90 transition-all dark:scale-100 dark:rotate-0"
        />
    </Button>
</template>
