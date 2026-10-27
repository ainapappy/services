<script setup lang="ts">
// Enveloppe de champ de la charte back-office : libelle, controle (slot),
// message d'aide ou d'erreur de validation.
defineProps<{
    label: string;
    fieldId: string;
    error?: string;
    help?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label class="back-label" :for="fieldId">{{ label }}</label>
        <slot />
        <p v-if="error" class="back-error">{{ error }}</p>
        <p v-else-if="help" class="back-help">{{ help }}</p>
    </div>
</template>
