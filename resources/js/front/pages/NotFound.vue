<script setup lang="ts">
// Page 404 de la vitrine sur primitives shadcn : coquille FrontLayout
// (vitrine toujours sombre), heros centre avec halos -z-10, grand 404 en
// degrade, revelation au scroll. Liens vers l'accueil (/) et les tarifs
// (/pricing) via les routes Wayfinder, comme avant la refonte.
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { home, pricing } from '@/routes';
import FrontLayout from '../layouts/FrontLayout.vue';
import { vReveal } from '../directives/reveal';
</script>

<template>
    <!-- Sans ancre #commencer ici, le CTA du header pointe les tarifs. -->
    <FrontLayout :cta-href="pricing.url()">
        <Head title="Page introuvable" />

        <!-- HERO : halos -z-10 + grand 404 en degrade -->
        <section class="front-hero--error relative">
            <div class="front-glow front-glow--a" aria-hidden="true"></div>
            <div class="front-glow front-glow--b" aria-hidden="true"></div>

            <div class="relative mx-auto w-full max-w-6xl px-6">
                <p
                    v-reveal
                    class="text-sm font-semibold tracking-[0.14em] text-primary uppercase"
                >
                    Erreur 404
                </p>
                <p
                    v-reveal="{ delay: 60 }"
                    class="front-error-code bg-linear-to-r from-primary to-violet-500 bg-clip-text text-transparent"
                    aria-hidden="true"
                >
                    404
                </p>
                <h1
                    v-reveal="{ delay: 120 }"
                    class="mt-6 text-4xl font-bold tracking-tight md:text-5xl"
                >
                    Cette page n'existe pas
                </h1>
                <p
                    v-reveal="{ delay: 200 }"
                    class="mx-auto mt-4 max-w-xl text-lg text-muted-foreground"
                >
                    Le lien est peut-être obsolète ou la page a été déplacée.
                    Reprenons depuis l'accueil : vos modules y sont présentés,
                    et les tarifs restent à un clic.
                </p>
                <div
                    v-reveal="{ delay: 280 }"
                    class="mt-8 flex flex-wrap justify-center gap-3"
                >
                    <Button size="lg" as-child>
                        <a :href="home.url()">
                            Retour à l'accueil
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </a>
                    </Button>
                    <Button size="lg" variant="outline" as-child>
                        <a :href="pricing.url()">Voir les tarifs</a>
                    </Button>
                </div>
            </div>
        </section>
    </FrontLayout>
</template>
