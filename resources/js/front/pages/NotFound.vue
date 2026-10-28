<script setup lang="ts">
// Page 404 de la vitrine : charte front (halos animes en -z-10, grand 404
// en degrade, revelation au scroll), autoportante comme les autres pages
// front/. Aucun import hors arbre front/ et routes wayfinder.
import { Head } from '@inertiajs/vue3';
import { home, pricing } from '@/routes';
import { vReveal } from '../directives/reveal';
// Charte front indispensable : sans cet import, aucune regle front-* n'atteint
// la page (front.css est charge par les pages front, pas par le bundle global).
import '../../../css/front.css';

const appName = import.meta.env.VITE_APP_NAME || 'Services';
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <Head title="Page introuvable" />

        <!-- NAV -->
        <header class="front-nav">
            <div class="front-nav-inner front-container">
                <a class="front-brand" :href="home.url()">
                    <svg
                        width="28"
                        height="28"
                        viewBox="0 0 32 32"
                        fill="none"
                        aria-hidden="true"
                    >
                        <defs>
                            <linearGradient
                                id="front-brand-lg-404"
                                x1="0"
                                y1="0"
                                x2="32"
                                y2="32"
                            >
                                <stop stop-color="#6366f1" />
                                <stop offset="1" stop-color="#8b5cf6" />
                            </linearGradient>
                        </defs>
                        <rect
                            width="32"
                            height="32"
                            rx="9"
                            fill="url(#front-brand-lg-404)"
                        />
                        <circle cx="10.5" cy="16" r="2.4" fill="#fff" />
                        <circle cx="21.5" cy="10.5" r="2.4" fill="#fff" />
                        <circle cx="21.5" cy="21.5" r="2.4" fill="#fff" />
                        <path
                            d="M12.7 15l6.5-3.6M12.7 17l6.5 3.6"
                            stroke="#fff"
                            stroke-width="1.6"
                        />
                    </svg>
                    <span>{{ appName }}&nbsp;<em>Services</em></span>
                </a>
                <nav class="front-links" aria-label="Navigation principale">
                    <a :href="pricing.url()">Tarifs</a>
                </nav>
                <div class="front-nav-cta">
                    <a
                        class="front-cta front-cta-primary front-cta--sm"
                        :href="home.url()"
                    >
                        Accueil
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <!-- HERO : halos -z-10 + grand 404 en degrade -->
            <section class="front-hero front-hero--error">
                <div class="front-glow front-glow--a" aria-hidden="true"></div>
                <div class="front-glow front-glow--b" aria-hidden="true"></div>
                <div class="front-container">
                    <p v-reveal class="front-kicker">Erreur 404</p>
                    <p
                        v-reveal="{ delay: 60 }"
                        class="front-error-code front-grad"
                        aria-hidden="true"
                    >
                        404
                    </p>
                    <h1 v-reveal="{ delay: 120 }" class="front-hero-title">
                        Cette page n'existe pas
                    </h1>
                    <p v-reveal="{ delay: 200 }" class="front-lead">
                        Le lien est peut-être obsolète ou la page a été
                        déplacée. Reprenons depuis l'accueil : vos modules y
                        sont présentés, et les tarifs restent à un clic.
                    </p>
                    <div v-reveal="{ delay: 280 }" class="front-error-actions">
                        <a
                            class="front-cta front-cta-primary"
                            :href="home.url()"
                        >
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
                        <a
                            class="front-cta front-cta-secondary"
                            :href="pricing.url()"
                        >
                            Voir les tarifs
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <!-- FOOTER -->
        <footer class="front-footer">
            <div class="front-container front-foot">
                <p class="front-copy">
                    © {{ new Date().getFullYear() }} {{ appName }} — Tous droits
                    réservés.
                </p>
            </div>
        </footer>
    </div>
</template>
