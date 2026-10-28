<script setup lang="ts">
// Badge d'etat de la charte back-office : success (actif), muted (inactif,
// expiree) ou danger (revoquee).
withDefaults(
    defineProps<{
        variant?: 'success' | 'muted' | 'danger';
    }>(),
    { variant: 'muted' },
);
</script>

<template>
    <span class="back-badge" :class="`back-badge--${variant}`">
        <slot />
    </span>
</template>
