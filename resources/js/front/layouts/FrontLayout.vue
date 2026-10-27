<script setup lang="ts">
// Coquille front-office autoportante : aucun import hors vue/@inertiajs
// (et la charte front.scss). Les deux arbres front/ et back/ sont etanches.
import '../../../css/front.scss';

const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-[rgba(23,23,28,0.09)]">
            <div
                class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-4"
            >
                <p
                    class="text-sm font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    DevToolbox
                </p>
                <nav
                    class="flex items-center gap-6 text-sm"
                    aria-label="Navigation principale"
                >
                    <a
                        href="#"
                        class="text-[var(--color-muted)] transition-colors hover:text-[var(--color-ink)]"
                        >Produit</a
                    >
                    <a
                        href="#"
                        class="text-[var(--color-muted)] transition-colors hover:text-[var(--color-ink)]"
                        >Tarifs</a
                    >
                    <a
                        href="#"
                        class="text-[var(--color-muted)] transition-colors hover:text-[var(--color-ink)]"
                        >Contact</a
                    >
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-[rgba(23,23,28,0.09)]">
            <div
                class="mx-auto w-full max-w-5xl px-6 py-5 text-xs text-[var(--color-muted)]"
            >
                DevToolbox — Tous droits réservés {{ year }}.
            </div>
        </footer>
    </div>
</template>
