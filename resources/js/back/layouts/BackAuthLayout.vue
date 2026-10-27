<script setup lang="ts">
// Mini-layout des ecrans d'authentification back-office (login, mot de
// passe oublie, reinitialisation, verification d'email) : page autonome
// centree, hors sidebar. Aucun import hors @inertiajs/vue3 et back.scss.
import '../../../css/back.scss';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center px-4 py-10">
        <div class="flex w-full max-w-sm flex-col gap-6">
            <div class="flex flex-col items-center gap-1 text-center">
                <p
                    class="text-sm font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    DevToolbox
                </p>
                <p class="text-xs text-[var(--color-muted)]">Back-office</p>
            </div>

            <div
                class="back-card flex flex-col gap-5 px-6 py-6 sm:px-8 sm:py-8"
            >
                <div class="flex flex-col gap-1 text-center">
                    <h1
                        class="text-lg font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-sm text-[var(--color-muted)]"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
