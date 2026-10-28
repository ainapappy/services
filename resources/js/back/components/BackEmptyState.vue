<script setup lang="ts">
// Etat vide de la charte back-office : bloc centralise utilise par toutes
// les listes (titre, phrase d'explication, action optionnelle). L'action
// est un slot nomme : lien (a.back-btn) ou bouton (BackButton) selon
// l'ecran, le focus restant gere par les classes de la charte.
withDefaults(
    defineProps<{
        title: string;
        /** Largeur du paragraphe d'explication (sm defaut, md pour les textes longs). */
        wide?: boolean;
    }>(),
    { wide: false },
);
</script>

<template>
    <div class="back-empty">
        <p class="back-empty-title">{{ title }}</p>
        <p class="back-empty-desc" :class="{ 'back-empty-desc--wide': wide }">
            <slot />
        </p>
        <div v-if="$slots.action" class="back-empty-action">
            <slot name="action" />
        </div>
    </div>
</template>
