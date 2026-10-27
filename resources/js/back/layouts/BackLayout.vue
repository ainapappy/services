<script setup lang="ts">
// Coquille back-office autoportante : aucun import hors vue/@inertiajs
// (et la charte back.scss). Les deux arbres front/ et back/ sont etanches.
import '../../../css/back.scss';
</script>

<template>
    <div class="flex min-h-screen">
        <aside class="flex w-60 shrink-0 flex-col bg-[var(--color-ink)]">
            <p
                class="px-6 pt-6 pb-4 text-sm font-semibold tracking-wide text-white"
            >
                DevToolbox
            </p>
            <nav
                class="flex flex-1 flex-col gap-1 px-3"
                aria-label="Navigation principale"
            >
                <span class="back-nav-item" aria-current="page"
                    >Tableau de bord</span
                >
                <span class="back-nav-item">Modules</span>
                <span class="back-nav-item">Connecteurs</span>
                <span class="back-nav-item">Webhooks</span>
                <span class="back-nav-item">Journal</span>
            </nav>
            <p class="px-6 py-4 text-xs text-white/40">Administration</p>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="flex h-12 shrink-0 items-center border-b border-[rgba(23,23,28,0.09)] bg-[var(--color-surface)] px-6"
            >
                <p class="text-xs text-[var(--color-muted)]">Back-office</p>
            </header>

            <main class="flex-1 px-6 py-8">
                <slot />
            </main>
        </div>
    </div>
</template>
