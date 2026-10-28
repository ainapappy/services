<script setup lang="ts">
// Champ de saisie de la charte back-office : attributs transmis tels quels
// (type, id, name, autocomplete, aria-invalid, etc.).
// Le v-model est déclaré explicitement (defineModel) : sans lui, un
// `v-model` posé sur le composant ne se propage jamais jusqu'à l'input
// natif et le parent ne voit aucune saisie.
const model = defineModel<string | number | null>();
</script>

<template>
    <input v-model="model" class="back-input" />
</template>
