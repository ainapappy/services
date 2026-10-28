<script setup lang="ts">
// Page vitrine publique sur primitives shadcn (coquille FrontLayout, vitrine
// toujours sombre) : portage du prototype .knowledge/design/landing.html.
// Le heros et les CTA viennent des props ; les sections decoratives (console
// temps reel, marquee, modules, compteurs) gardent les contenus statiques du
// prototype, generes localement sans reseau.
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { FrontLandingShowPageProps } from '@/types';
import FrontLayout from '../../layouts/FrontLayout.vue';
import { vCount } from '../../directives/count';
import { vReveal } from '../../directives/reveal';

const props = defineProps<FrontLandingShowPageProps>();

const appName = import.meta.env.VITE_APP_NAME || 'Services';

const prefersReducedMotion = (): boolean =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ------------------------------------------------------------------ */
/* SEO                                                                 */
/* ------------------------------------------------------------------ */

const seoTitle = computed(() => props.landing.seo.title || props.landing.title);

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
    <FrontLayout>
        <Head :title="seoTitle">
            <meta name="description" :content="props.landing.seo.description" />
        </Head>

        <!-- HERO : halos -z-10, grille radiale, console temps reel -->
        <section class="relative">
            <div class="front-bg-grid" aria-hidden="true"></div>
            <div class="front-glow front-glow--a" aria-hidden="true"></div>
            <div class="front-glow front-glow--b" aria-hidden="true"></div>

            <div
                class="mx-auto grid w-full max-w-6xl items-center gap-14 px-6 py-24 lg:grid-cols-[1.05fr_0.95fr]"
            >
                <div>
                    <Badge v-reveal variant="outline" class="front-eyebrow">
                        <span class="dot" aria-hidden="true"></span>
                        Plateforme de services · Écosystème {{ appName }}
                    </Badge>
                    <h1
                        v-reveal="{ delay: 80 }"
                        class="mt-6 text-4xl font-bold tracking-tight text-balance md:text-6xl"
                    >
                        <span
                            class="bg-linear-to-r from-primary to-violet-500 bg-clip-text text-transparent"
                        >
                            {{ props.landing.heroTitle }}
                        </span>
                    </h1>
                    <p
                        v-reveal="{ delay: 160 }"
                        class="mt-5 max-w-xl text-lg text-muted-foreground"
                    >
                        {{ props.landing.heroSubtitle }}
                    </p>
                    <div
                        v-if="props.landing.ctas.length > 0"
                        v-reveal="{ delay: 240 }"
                        class="mt-8 flex flex-wrap gap-3"
                    >
                        <template
                            v-for="cta in props.landing.ctas"
                            :key="`${cta.label}-${cta.url}`"
                        >
                            <Button
                                v-if="cta.style === 'primary'"
                                size="lg"
                                as-child
                            >
                                <a :href="cta.url">
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
                            </Button>
                            <Button v-else size="lg" variant="outline" as-child>
                                <a :href="cta.url">{{ cta.label }}</a>
                            </Button>
                        </template>
                    </div>
                    <p
                        v-reveal="{ delay: 320 }"
                        class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.6"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-primary"
                                aria-hidden="true"
                            >
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                            Sans carte bancaire
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.6"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-primary"
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
                    class="front-hero-visual mx-auto w-full max-w-[480px]"
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
                            <span class="rps-label"> requêtes / seconde </span>
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
        <section
            id="modules"
            class="mx-auto w-full max-w-6xl scroll-mt-20 px-6 py-20"
        >
            <div class="mx-auto max-w-2xl text-center">
                <p
                    v-reveal
                    class="text-sm font-semibold tracking-[0.14em] text-primary uppercase"
                >
                    Modules
                </p>
                <div v-reveal="{ delay: 60 }" class="mt-4">
                    <h2 class="text-3xl font-bold tracking-tight md:text-4xl">
                        Quatre modules,
                        <span
                            class="bg-linear-to-r from-primary to-violet-500 bg-clip-text text-transparent"
                            >un seul back-office</span
                        >
                    </h2>
                    <p class="mt-4 text-muted-foreground">
                        Chaque module est un domaine indépendant : activez ce
                        dont vous avez besoin, quand vous en avez besoin.
                    </p>
                </div>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <Card
                    v-reveal
                    class="bg-card/60 backdrop-blur transition-[border-color,box-shadow] duration-300 hover:border-primary/40 hover:shadow-lg"
                >
                    <CardHeader>
                        <CardAction>
                            <div
                                class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                aria-hidden="true"
                            >
                                <svg
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M12 3l7 3v5c0 4.6-3 8.1-7 10-4-1.9-7-5.4-7-10V6l7-3z"
                                    />
                                    <path d="M12 8v4M12 15h.01" />
                                </svg>
                            </div>
                        </CardAction>
                        <CardTitle class="text-base">API Gateway</CardTitle>
                        <CardDescription>
                            Accès sécurisé aux services et API des applications
                            : clés, quotas et permissions granulaires.
                        </CardDescription>
                    </CardHeader>
                </Card>
                <Card
                    v-reveal="{ delay: 80 }"
                    class="bg-card/60 backdrop-blur transition-[border-color,box-shadow] duration-300 hover:border-primary/40 hover:shadow-lg"
                >
                    <CardHeader>
                        <CardAction>
                            <div
                                class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                aria-hidden="true"
                            >
                                <svg
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"
                                    />
                                    <path
                                        d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"
                                    />
                                </svg>
                            </div>
                        </CardAction>
                        <CardTitle class="text-base">Connectors</CardTitle>
                        <CardDescription>
                            WordPress, Caren, Erpen et services externes — des
                            intégrations prêtes à l'emploi, configurables en
                            back-office.
                        </CardDescription>
                    </CardHeader>
                </Card>
                <Card
                    v-reveal="{ delay: 160 }"
                    class="bg-card/60 backdrop-blur transition-[border-color,box-shadow] duration-300 hover:border-primary/40 hover:shadow-lg"
                >
                    <CardHeader>
                        <CardAction>
                            <div
                                class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                aria-hidden="true"
                            >
                                <svg
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                >
                                    <path
                                        d="M10 12a2 2 0 1 0 4 0a2 2 0 1 0-4 0"
                                    />
                                    <path
                                        d="M16.2 7.8a6 6 0 0 1 0 8.4M7.8 16.2a6 6 0 0 1 0-8.4M19.1 4.9a10 10 0 0 1 0 14.2M4.9 19.1a10 10 0 0 1 0-14.2"
                                    />
                                </svg>
                            </div>
                        </CardAction>
                        <CardTitle class="text-base">Webhooks</CardTitle>
                        <CardDescription>
                            Événements, tentatives, erreurs et relances
                            automatiques : aucun flux ne se perd entre vos
                            applications.
                        </CardDescription>
                    </CardHeader>
                </Card>
                <Card
                    v-reveal="{ delay: 240 }"
                    class="bg-card/60 backdrop-blur transition-[border-color,box-shadow] duration-300 hover:border-primary/40 hover:shadow-lg"
                >
                    <CardHeader>
                        <CardAction>
                            <div
                                class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                aria-hidden="true"
                            >
                                <svg
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                                </svg>
                            </div>
                        </CardAction>
                        <CardTitle class="text-base">Monitoring</CardTitle>
                        <CardDescription>
                            État des connexions, journaux et alertes : la santé
                            de l'écosystème visible en un coup d'œil.
                        </CardDescription>
                    </CardHeader>
                </Card>
            </div>
        </section>

        <!-- STATS -->
        <section
            id="stats"
            class="mx-auto w-full max-w-6xl scroll-mt-20 px-6 py-10"
        >
            <div
                v-reveal
                class="grid grid-cols-2 gap-10 rounded-3xl border border-border/60 bg-card/40 px-6 py-12 text-center backdrop-blur lg:grid-cols-4"
            >
                <div
                    v-for="stat in stats"
                    :key="stat.label"
                    class="flex flex-col gap-1"
                >
                    <b
                        v-count="{
                            target: stat.target,
                            dec: stat.dec,
                            prefix: stat.prefix,
                            suffix: stat.suffix,
                        }"
                        class="text-3xl font-semibold tracking-tight tabular-nums md:text-4xl"
                        >0</b
                    >
                    <span class="text-sm text-muted-foreground">
                        {{ stat.label }}
                    </span>
                </div>
                <div class="flex flex-col gap-1">
                    <b
                        class="text-3xl font-semibold tracking-tight tabular-nums md:text-4xl"
                        >24/7</b
                    >
                    <span class="text-sm text-muted-foreground">
                        supervision &amp; alertes
                    </span>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section
            id="commencer"
            class="mx-auto w-full max-w-6xl scroll-mt-20 px-6 pt-10 pb-24"
        >
            <div
                v-reveal
                class="relative overflow-hidden rounded-3xl border border-border/60 bg-card px-6 py-20 text-center"
            >
                <div
                    class="pointer-events-none absolute -bottom-56 -left-24 size-105 rounded-full bg-primary/40 blur-[80px]"
                    aria-hidden="true"
                ></div>
                <div
                    class="pointer-events-none absolute -top-44 -right-20 size-90 rounded-full bg-violet-500/30 blur-[80px]"
                    aria-hidden="true"
                ></div>
                <h2
                    class="relative text-3xl font-bold tracking-tight md:text-4xl"
                >
                    Prêt à connecter<br />votre écosystème ?
                </h2>
                <p class="relative mx-auto mt-4 max-w-lg text-muted-foreground">
                    Créez votre passerelle, branchez vos connecteurs et publiez
                    vos landing pages — depuis une seule interface.
                </p>
                <div class="relative mt-8 flex flex-wrap justify-center gap-3">
                    <Button as-child>
                        <a href="#commencer">Commencer gratuitement</a>
                    </Button>
                    <Button variant="outline" as-child>
                        <a href="#modules">Revoir les modules</a>
                    </Button>
                </div>
            </div>
        </section>
    </FrontLayout>
</template>
