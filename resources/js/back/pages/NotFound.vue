<script setup lang="ts">
// Page 404 du back-office : autonome et centree, volontairement hors
// coquille (rendue par exception, sans les props partagees auth/modules de
// BackLayout). Tokens app.css uniquement (bg-background/text-foreground) :
// ni back.css, ni classes back-*, ni composant back/.
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
import { dashboard } from '@/routes/admin';
import { Button } from '@/components/ui/button';
</script>

<template>
    <main
        class="flex min-h-svh flex-col items-center justify-center bg-background px-4 py-10 text-foreground"
    >
        <div
            class="flex w-full max-w-md flex-col items-center gap-6 text-center"
        >
            <p class="text-sm font-semibold tracking-tight">
                Services
                <span class="font-normal text-muted-foreground">
                    · Back-office
                </span>
            </p>

            <!-- Grand 404 en degrade : decoratif, l'information portee par
                 le h1 qui suit. -->
            <p
                class="bg-gradient-to-r from-primary to-violet-500 bg-clip-text text-7xl font-semibold tracking-tight text-transparent tabular-nums"
                aria-hidden="true"
            >
                404
            </p>

            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Page introuvable
                </h1>
                <p class="text-sm text-muted-foreground">
                    Cette adresse n'existe pas ou n'existe plus : le lien est
                    peut-être obsolète ou l'URL mal saisie.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <Button as-child>
                    <Link :href="dashboard.url()">Tableau de bord</Link>
                </Button>
                <Button as-child variant="outline">
                    <Link :href="home.url()">Retour à l'accueil</Link>
                </Button>
            </div>
        </div>
    </main>
</template>
