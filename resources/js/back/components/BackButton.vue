<script setup lang="ts">
// Bouton de la charte back-office : variantes primary / secondary / danger,
// avec etats hover, focus-visible, disabled et loading (spinner integre).
withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'danger';
        type?: 'button' | 'submit' | 'reset';
        loading?: boolean;
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        type: 'button',
        loading: false,
        disabled: false,
    },
);
</script>

<template>
    <button
        :type="type"
        class="back-btn"
        :class="`back-btn--${variant}`"
        :aria-busy="loading || undefined"
        :disabled="disabled || loading"
    >
        <span v-if="loading" class="back-spinner" aria-hidden="true" />
        <slot />
    </button>
</template>
