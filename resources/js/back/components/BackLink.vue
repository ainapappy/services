<script setup lang="ts">
// Lien interne de la charte back-office, base sur le Link Inertia.
// href / method / tabindex etc. sont transmis par attribution d'attributs.
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link class="back-link"><slot /></Link>
</template>
