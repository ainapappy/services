<script setup lang="ts">
// Page 404 du back-office : autonome et centree, volontairement hors
// coquille (rendue par exception, sans les props partagees auth/modules de
// BackLayout). Charte back distincte de la vitrine : back.css, classes
// back-*, aucun import hors arbre back/ et routes wayfinder.
import { home } from '@/routes';
import { dashboard } from '@/routes/admin';
import '../../../css/back.css';
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center px-4 py-10">
        <div
            class="flex w-full max-w-md flex-col items-center gap-6 text-center"
        >
            <p
                class="text-sm font-semibold tracking-tight text-[var(--color-ink)]"
            >
                DevToolbox
                <span class="font-normal text-[var(--color-muted)]">
                    · Back-office
                </span>
            </p>

            <div
                class="back-card flex w-full flex-col items-center gap-5 px-6 py-10 sm:px-8"
            >
                <!-- Grand 404 : texte large (48px+), seuil AA « grand texte »
                     (3:1) ; l'accent brut passe largement (4.47:1). -->
                <p
                    class="font-mono text-5xl font-semibold tracking-tight text-[var(--color-accent)]"
                    aria-hidden="true"
                >
                    404
                </p>

                <div class="flex flex-col gap-2">
                    <h1
                        class="text-lg font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Page introuvable
                    </h1>
                    <p class="text-sm text-[var(--color-muted)]">
                        Cette adresse n'existe pas ou n'existe plus : le lien
                        est peut-être obsolète ou l'URL mal saisie.
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a
                        :href="dashboard.url()"
                        class="back-btn back-btn--primary"
                    >
                        Tableau de bord
                    </a>
                    <a :href="home.url()" class="back-btn back-btn--secondary">
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
