<script setup lang="ts">
// Badge d'etat de la charte back-office : success (actif) ou muted (inactif).
withDefaults(
    defineProps<{
        variant?: 'success' | 'muted';
    }>(),
    { variant: 'muted' },
);
</script>

<template>
    <span class="back-badge" :class="`back-badge--${variant}`">
        <slot />
    </span>
</template>
