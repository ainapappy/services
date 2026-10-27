<script setup lang="ts">
// Interrupteur accessible de la charte back-office (role="switch").
// Le parent pilote l'etat via la prop `checked` et l'evenement `toggle`.
defineProps<{
    checked: boolean;
    label: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    toggle: [];
}>();
</script>

<template>
    <button
        type="button"
        role="switch"
        class="back-toggle"
        :aria-checked="checked"
        :aria-label="label"
        :disabled="disabled"
        @click="emit('toggle')"
    />
</template>
