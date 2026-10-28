<script setup lang="ts">
// Page vitrine publique : retranscription du prototype
// .knowledge/design/landing.html (structure, rythme, animations).
// Le héros et les CTA viennent des props ; les sections décoratives
// (console temps réel, marquee, modules, compteurs) gardent les contenus
// statiques du prototype, générés localement sans réseau.
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { home } from '@/routes';
import type { FrontLandingShowPageProps } from '@/types';
import { vCount } from '../../directives/count';
import { vReveal } from '../../directives/reveal';
// Charte front indispensable : sans cet import, aucune regle front-* n'atteint
// la page (front.scss est charge par les pages front, pas par le bundle global).
import '../../../../css/front.scss';

const props = defineProps<FrontLandingShowPageProps>();

const appName = import.meta.env.VITE_APP_NAME || 'DevToolbox';

const prefersReducedMotion = (): boolean =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ------------------------------------------------------------------ */
/* SEO                                                                 */
/* ------------------------------------------------------------------ */

const seoTitle = computed(() => props.landing.seo.title || props.landing.title);

/* ------------------------------------------------------------------ */
/* Nav : fond translucide au scroll                                    */
/* ------------------------------------------------------------------ */

const scrolled = ref(false);

const onScroll = (): void => {
    scrolled.value = window.scrollY > 8;
};

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
});

/* ------------------------------------------------------------------ */
/* Console « temps réel » : barres d'activité + compteur req/s         */
/* ------------------------------------------------------------------ */

const bars = Array.from(
    { length: 28 },
    () => 18 + Math.round(Math.random() * 66),
);

const motionOk = ref(true);

const rps = ref(128);
let rpsTimer: number | undefined;

onMounted(() => {
    motionOk.value = !prefersReducedMotion();

    if (!motionOk.value) {
        return;
    }

    rpsTimer = window.setInterval(() => {
        const next = rps.value + Math.round((Math.random() - 0.5) * 16);
        rps.value = Math.min(184, Math.max(96, next));
    }, 900);
});

onBeforeUnmount(() => {
    window.clearInterval(rpsTimer);
});

const consoleRows = [
    { method: 'POST', path: '/v1/connectors/caren', status: '200', tone: 'ok' },
    { method: 'GET', path: '/v1/gateway/keys', status: '200', tone: 'ok' },
    {
        method: 'POST',
        path: '/v1/hooks/erp · relance',
        status: '202',
        tone: 'warn',
    },
] as const;

/* ------------------------------------------------------------------ */
/* Tilt 3D de la console au pointeur                                   */
/* ------------------------------------------------------------------ */

const tiltZone = ref<HTMLElement | null>(null);
const consoleEl = ref<HTMLElement | null>(null);
const tiltEnabled = ref(false);

const onTilt = (event: PointerEvent): void => {
    const zone = tiltZone.value;
    const card = consoleEl.value;

    if (!zone || !card) {
        return;
    }

    const rect = zone.getBoundingClientRect();
    const x = (event.clientX - rect.left) / rect.width - 0.5;
    const y = (event.clientY - rect.top) / rect.height - 0.5;
    card.style.transform = `perspective(900px) rotateX(${(-y * 6).toFixed(2)}deg) rotateY(${(x * 8).toFixed(2)}deg)`;
};

const onTiltLeave = (): void => {
    if (consoleEl.value) {
        consoleEl.value.style.transform = '';
    }
};

onMounted(() => {
    tiltEnabled.value =
        motionOk.value && window.matchMedia('(pointer: fine)').matches;
});

/* ------------------------------------------------------------------ */
/* Sections décoratives : marquee, modules, compteurs                  */
/* ------------------------------------------------------------------ */

interface MarqueeItem {
    name: string;
    icon: string[];
}

const integrations: MarqueeItem[] = [
    {
        name: 'WordPress',
        icon: [
            'M3 12a9 9 0 1 0 18 0a9 9 0 1 0-18 0',
            'M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18',
        ],
    },
    {
        name: 'Caren',
        icon: [
            'M6 4h12a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3Z',
            'M3 10h18M8 4v6',
        ],
    },
    {
        name: 'Erpen',
        icon: [
            'M9 12a3 3 0 1 0 6 0a3 3 0 1 0-6 0',
            'M12 2v4M12 18v4M2 12h4M18 12h4',
        ],
    },
    {
        name: 'API REST',
        icon: [
            'M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5',
            'M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5',
        ],
    },
    {
        name: 'Webhooks',
        icon: [
            'M10 12a2 2 0 1 0 4 0a2 2 0 1 0-4 0',
            'M16.2 7.8a6 6 0 0 1 0 8.4M7.8 16.2a6 6 0 0 1 0-8.4M19.1 4.9a10 10 0 0 1 0 14.2M4.9 19.1a10 10 0 0 1 0-14.2',
        ],
    },
    {
        name: 'Services externes',
        icon: ['M4 17l6-6-6-6M12 19h8'],
    },
];

/* Duplication pour la boucle continue de l'animation. */
const marqueeItems = [...integrations, ...integrations];

const stats = [
    {
        target: 99.98,
        dec: 2,
        prefix: '',
        suffix: ' %',
        label: 'de disponibilité',
    },
    {
        target: 12,
        dec: 0,
        prefix: '',
        suffix: ' M',
        label: 'appels API / mois',
    },
    {
        target: 80,
        dec: 0,
        prefix: '< ',
        suffix: ' ms',
        label: 'latence médiane',
    },
];
</script>

<template>
    <div>
        <Head :title="seoTitle">
            <meta name="description" :content="props.landing.seo.description" />
        </Head>

        <!-- NAV -->
        <header class="front-nav" :class="{ 'front-nav--scrolled': scrolled }">
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
                                id="front-brand-lg"
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
                            fill="url(#front-brand-lg)"
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
                    <a href="#modules">Modules</a>
                    <a href="#stats">Chiffres</a>
                    <a href="#commencer">Tarifs</a>
                </nav>
                <div class="front-nav-cta">
                    <a
                        class="front-cta front-cta-secondary front-cta--sm"
                        href="#commencer"
                    >
                        Voir les tarifs
                    </a>
                    <a
                        class="front-cta front-cta-primary front-cta--sm"
                        href="#commencer"
                    >
                        Commencer
                    </a>
                </div>
            </div>
        </header>

        <main>
            <div class="front-bg-grid" aria-hidden="true"></div>

            <!-- HERO -->
            <section class="front-hero">
                <div class="front-glow front-glow--a" aria-hidden="true"></div>
                <div class="front-glow front-glow--b" aria-hidden="true"></div>
                <div class="front-container front-hero-grid">
                    <div class="front-hero-copy">
                        <p v-reveal class="front-eyebrow">
                            <span class="dot"></span>
                            Plateforme de services · Écosystème {{ appName }}
                        </p>
                        <h1 v-reveal="{ delay: 80 }" class="front-hero-title">
                            {{ props.landing.heroTitle }}
                        </h1>
                        <p v-reveal="{ delay: 160 }" class="front-lead">
                            {{ props.landing.heroSubtitle }}
                        </p>
                        <div
                            v-if="props.landing.ctas.length > 0"
                            v-reveal="{ delay: 240 }"
                            class="front-hero-actions"
                        >
                            <template
                                v-for="cta in props.landing.ctas"
                                :key="`${cta.label}-${cta.url}`"
                            >
                                <a
                                    v-if="cta.style === 'primary'"
                                    class="front-cta front-cta-primary"
                                    :href="cta.url"
                                >
                                    {{ cta.label }}
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
                                    v-else
                                    class="front-cta front-cta-secondary"
                                    :href="cta.url"
                                >
                                    {{ cta.label }}
                                </a>
                            </template>
                        </div>
                        <p v-reveal="{ delay: 320 }" class="front-trust">
                            <span>
                                <svg
                                    width="14"
                                    height="14"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                                Sans carte bancaire
                            </span>
                            <span>
                                <svg
                                    width="14"
                                    height="14"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                                Opérationnel en 5 minutes
                            </span>
                        </p>
                    </div>

                    <div
                        ref="tiltZone"
                        v-reveal="{ delay: 200 }"
                        class="front-hero-visual"
                        @pointermove="tiltEnabled ? onTilt($event) : undefined"
                        @pointerleave="onTiltLeave"
                    >
                        <div ref="consoleEl" class="front-console">
                            <div class="front-console-head">
                                <span class="cdot r"></span
                                ><span class="cdot y"></span
                                ><span class="cdot g"></span>
                                <span class="ctitle">
                                    passerelle · temps réel
                                </span>
                                <span class="badge-live">
                                    <span class="pulse"></span>LIVE
                                </span>
                            </div>
                            <div class="front-console-metric">
                                <span class="rps-label">
                                    requêtes / seconde
                                </span>
                                <span class="rps">{{ rps }}</span>
                            </div>
                            <div class="front-bars" aria-hidden="true">
                                <span
                                    v-for="(bar, index) in bars"
                                    :key="index"
                                    :style="{
                                        height: `${bar}%`,
                                        animationDelay: motionOk
                                            ? `${index * 90}ms`
                                            : undefined,
                                    }"
                                ></span>
                            </div>
                            <div class="front-console-rows">
                                <div
                                    v-for="row in consoleRows"
                                    :key="row.path"
                                    class="row"
                                >
                                    <code>{{ row.method }}</code>
                                    <span>{{ row.path }}</span>
                                    <em :class="row.tone">{{ row.status }}</em>
                                </div>
                            </div>
                        </div>
                        <div class="front-chip front-chip--a">
                            API Gateway · 200 OK
                        </div>
                        <div class="front-chip front-chip--b">
                            Webhook relancé · 3/3
                        </div>
                    </div>
                </div>
            </section>

            <!-- MARQUEE -->
            <section v-reveal class="front-marquee" aria-label="Intégrations">
                <p>Ils se branchent en quelques minutes</p>
                <div class="front-marquee-track" aria-hidden="true">
                    <span
                        v-for="pill in marqueeItems"
                        :key="pill.name"
                        class="front-pill"
                    >
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            aria-hidden="true"
                        >
                            <path v-for="d in pill.icon" :key="d" :d="d" />
                        </svg>
                        {{ pill.name }}
                    </span>
                </div>
            </section>

            <!-- MODULES -->
            <section id="modules" class="front-modules">
                <div class="front-container">
                    <span v-reveal class="front-kicker">Modules</span>
                    <div v-reveal="{ delay: 60 }" class="front-sec-head">
                        <h2>
                            Quatre modules,
                            <span class="front-grad">un seul back-office</span>
                        </h2>
                        <p>
                            Chaque module est un domaine indépendant : activez
                            ce dont vous avez besoin, quand vous en avez besoin.
                        </p>
                    </div>
                    <div class="front-cards">
                        <article v-reveal class="front-card">
                            <div class="icon">
                                <svg
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M12 3l7 3v5c0 4.6-3 8.1-7 10-4-1.9-7-5.4-7-10V6l7-3z"
                                    />
                                    <path d="M12 8v4M12 15h.01" />
                                </svg>
                            </div>
                            <h3>API Gateway</h3>
                            <p>
                                Accès sécurisé aux services et API des
                                applications : clés, quotas et permissions
                                granulaires.
                            </p>
                        </article>
                        <article v-reveal="{ delay: 80 }" class="front-card">
                            <div class="icon">
                                <svg
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"
                                    />
                                    <path
                                        d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"
                                    />
                                </svg>
                            </div>
                            <h3>Connectors</h3>
                            <p>
                                WordPress, Caren, Erpen et services externes —
                                des intégrations prêtes à l'emploi,
                                configurables en back-office.
                            </p>
                        </article>
                        <article v-reveal="{ delay: 160 }" class="front-card">
                            <div class="icon">
                                <svg
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M10 12a2 2 0 1 0 4 0a2 2 0 1 0-4 0"
                                    />
                                    <path
                                        d="M16.2 7.8a6 6 0 0 1 0 8.4M7.8 16.2a6 6 0 0 1 0-8.4M19.1 4.9a10 10 0 0 1 0 14.2M4.9 19.1a10 10 0 0 1 0-14.2"
                                    />
                                </svg>
                            </div>
                            <h3>Webhooks</h3>
                            <p>
                                Événements, tentatives, erreurs et relances
                                automatiques : aucun flux ne se perd entre vos
                                applications.
                            </p>
                        </article>
                        <article v-reveal="{ delay: 240 }" class="front-card">
                            <div class="icon">
                                <svg
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                                </svg>
                            </div>
                            <h3>Monitoring</h3>
                            <p>
                                État des connexions, journaux et alertes : la
                                santé de l'écosystème visible en un coup d'œil.
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- STATS -->
            <section id="stats" class="front-stats">
                <div class="front-container">
                    <div v-reveal class="front-stats-panel">
                        <div
                            v-for="stat in stats"
                            :key="stat.label"
                            class="front-stat"
                        >
                            <b
                                v-count="{
                                    target: stat.target,
                                    dec: stat.dec,
                                    prefix: stat.prefix,
                                    suffix: stat.suffix,
                                }"
                                >0</b
                            >
                            <span>{{ stat.label }}</span>
                        </div>
                        <div class="front-stat">
                            <b>24/7</b>
                            <span>supervision &amp; alertes</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section id="commencer" class="front-cta-section">
                <div class="front-container">
                    <div v-reveal class="front-cta-panel">
                        <h2>Prêt à connecter<br />votre écosystème ?</h2>
                        <p>
                            Créez votre passerelle, branchez vos connecteurs et
                            publiez vos landing pages — depuis une seule
                            interface.
                        </p>
                        <div class="front-cta-actions">
                            <a
                                class="front-cta front-cta-light"
                                href="#commencer"
                            >
                                Commencer gratuitement
                            </a>
                            <a
                                class="front-cta front-cta-outline-light"
                                href="#modules"
                            >
                                Revoir les modules
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- FOOTER -->
        <footer class="front-footer">
            <div class="front-container front-foot">
                <a class="front-brand" :href="home.url()">
                    <svg
                        width="24"
                        height="24"
                        viewBox="0 0 32 32"
                        fill="none"
                        aria-hidden="true"
                    >
                        <defs>
                            <linearGradient
                                id="front-brand-lg-foot"
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
                            fill="url(#front-brand-lg-foot)"
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
                <nav aria-label="Liens de pied de page">
                    <a href="#modules">Modules</a>
                    <a href="#commencer">Tarifs</a>
                    <a :href="home.url()">Accueil</a>
                </nav>
                <p class="front-copy">
                    © {{ new Date().getFullYear() }} {{ appName }} — Tous droits
                    réservés.
                </p>
            </div>
        </footer>
    </div>
</template>
