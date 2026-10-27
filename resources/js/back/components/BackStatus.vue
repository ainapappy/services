<script setup lang="ts">
// Bandeau de statut (flash, information, avertissement) de la charte
// back-office. role="status" : annonce poliment par les lecteurs d'ecran.
withDefaults(
    defineProps<{
        tone?: 'info' | 'success' | 'warning';
    }>(),
    { tone: 'info' },
);
</script>

<template>
    <div class="back-status" :class="`back-status--${tone}`" role="status">
        <slot />
    </div>
</template>
