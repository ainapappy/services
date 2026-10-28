<script setup lang="ts">
// Badge d'etat de la charte back-office : success (actif, operationnel),
// warning (degrade), muted (inactif, jamais controle) ou danger (echec,
// revoquee).
withDefaults(
    defineProps<{
        variant?: 'success' | 'warning' | 'muted' | 'danger';
    }>(),
    { variant: 'muted' },
);
</script>

<template>
    <span class="back-badge" :class="`back-badge--${variant}`">
        <slot />
    </span>
</template>
