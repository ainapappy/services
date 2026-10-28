<script setup lang="ts">
// Page tarifs publique sur primitives shadcn (coquille FrontLayout, vitrine
// toujours sombre) : portage du prototype .knowledge/design/pricing.html.
// Titre, plans et FAQ viennent des props ; la bascule des prix est purement
// locale (les deux prix sont dans les props, aucune requete reseau) avec le
// swap de 180 ms du prototype. Le CTA final et la section « inclus »
// restent statiques comme dans le prototype.
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Check } from '@lucide/vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Item,
    ItemContent,
    ItemGroup,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item';
import { Switch } from '@/components/ui/switch';
import { home } from '@/routes';
import type { FrontPricingShowPageProps, PricingShowPlan } from '@/types';
import FrontLayout from '../../layouts/FrontLayout.vue';
import { vReveal } from '../../directives/reveal';

const props = defineProps<FrontPricingShowPageProps>();

const landingUrl = home.url();

/* ------------------------------------------------------------------ */
/* SEO                                                                 */
/* ------------------------------------------------------------------ */

const seoTitle = computed(() => props.table.title || 'Tarifs');

/* ------------------------------------------------------------------ */
/* Switch mensuel / annuel : bascule locale avec swap des prix         */
/* ------------------------------------------------------------------ */

const annual = ref(false);
const swapping = ref(false);

const motionOk = ref(true);

onMounted(() => {
    motionOk.value = !window.matchMedia('(prefers-reduced-motion: reduce)')
        .matches;
});

let swapTimer: number | undefined;

/* Le texte bascule apres l'extinction (180 ms) puis reapparait, comme le
   prototype ; sans mouvement prefere, la bascule est immediate. */
function setBilling(next: boolean): void {
    if (annual.value === next) {
        return;
    }

    window.clearTimeout(swapTimer);

    if (motionOk.value) {
        swapping.value = true;
        swapTimer = window.setTimeout(() => {
            annual.value = next;
            swapping.value = false;
        }, 180);
        return;
    }

    annual.value = next;
}

onBeforeUnmount(() => {
    window.clearTimeout(swapTimer);
});

/* ------------------------------------------------------------------ */
/* Prix : centimes -> euros, calculs entiers uniquement                */
/* ------------------------------------------------------------------ */

function formatEuros(cents: number): string {
    const safe = Math.round(cents);
    const sign = safe < 0 ? '-' : '';
    const abs = Math.abs(safe);
    const unit = Math.floor(abs / 100).toLocaleString('fr-FR');
    const rem = (abs % 100).toString().padStart(2, '0');
    return `${sign}${unit},${rem} €`;
}

/** Montant principal : sans décimales quand le prix est rond (29, pas 29,00). */
function formatAmount(cents: number): string {
    const safe = Math.round(cents);
    const sign = safe < 0 ? '-' : '';
    const abs = Math.abs(safe);
    const unit = Math.floor(abs / 100).toLocaleString('fr-FR');
    const rem = abs % 100;

    if (rem === 0) {
        return `${sign}${unit}`;
    }

    return `${sign}${unit},${rem.toString().padStart(2, '0')}`;
}

function displayAmount(plan: PricingShowPlan): string {
    return formatAmount(annual.value ? plan.priceYearly : plan.priceMonthly);
}

/* Note de facturation : mensuel -> « Facturation mensuelle » ; annuel ->
   gratuit ou équivalent annuel (prix mensuel x 12), comme le prototype. */
function billNote(plan: PricingShowPlan): string {
    if (!annual.value) {
        return 'Facturation mensuelle';
    }

    if (plan.priceYearly === 0) {
        return 'Gratuit pour toujours';
    }

    return `Soit ${formatEuros(plan.priceYearly * 12)} facturés à l'année`;
}

/* ------------------------------------------------------------------ */
/* Section « inclus » : contenus statiques du prototype                */
/* ------------------------------------------------------------------ */

const includes = [
    {
        label: 'Back-office complet',
        icon: [
            'M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4Z',
            'M3 9h18M9 21V9',
        ],
    },
    {
        label: 'HTTPS & clés API',
        icon: ['M12 3l7 3v5c0 4.6-3 8.1-7 10-4-1.9-7-5.4-7-10V6l7-3z'],
    },
    {
        label: 'Landing pages éditables',
        icon: [
            'M4 7V5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2M4 7h16M4 7v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V7M9 11h6',
        ],
    },
    {
        label: "Journal d'audit",
        icon: ['M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
    },
] as const;
</script>

<template>
    <FrontLayout>
        <Head :title="seoTitle">
            <meta name="description" :content="props.table.subtitle" />
        </Head>

        <!-- HERO + SWITCH -->
        <section class="relative">
            <div class="front-bg-grid" aria-hidden="true"></div>
            <div class="front-glow front-glow--top" aria-hidden="true"></div>

            <div class="mx-auto w-full max-w-6xl px-6 pt-24 pb-4 text-center">
                <Badge v-reveal variant="outline" class="front-eyebrow">
                    Tarifs
                </Badge>
                <h1
                    v-reveal="{ delay: 60 }"
                    class="mt-6 text-4xl font-bold tracking-tight text-balance md:text-6xl"
                >
                    <span
                        class="bg-linear-to-r from-primary to-violet-500 bg-clip-text text-transparent"
                    >
                        {{ props.table.title }}
                    </span>
                </h1>
                <p
                    v-reveal="{ delay: 140 }"
                    class="mx-auto mt-4 max-w-xl text-lg text-muted-foreground"
                >
                    {{ props.table.subtitle }}
                </p>

                <!-- Bascule mensuel / annuel : le Switch pilote la meme
                     logique de swap de 180 ms que l'ancien radiogroup. -->
                <div
                    v-reveal="{ delay: 220 }"
                    class="mt-10 flex items-center justify-center gap-3"
                >
                    <span
                        class="text-sm"
                        :class="
                            annual
                                ? 'text-muted-foreground'
                                : 'font-semibold text-foreground'
                        "
                        >Mensuel</span
                    >
                    <Switch
                        :model-value="annual"
                        aria-label="Périodicité de facturation"
                        @update:model-value="setBilling"
                    />
                    <span
                        class="text-sm"
                        :class="
                            annual
                                ? 'font-semibold text-foreground'
                                : 'text-muted-foreground'
                        "
                        >Annuel</span
                    >
                    <Badge>-20 %</Badge>
                </div>
            </div>
        </section>

        <!-- PLANS -->
        <section class="mx-auto w-full max-w-6xl px-6 py-12">
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="(plan, index) in props.table.plans"
                    :key="plan.slug"
                    v-reveal="{ delay: index * 100 }"
                    class="relative"
                    :class="{ 'front-plan--popular': plan.popular }"
                >
                    <Badge v-if="plan.popular" class="front-tag-popular">
                        Populaire
                    </Badge>
                    <CardHeader>
                        <CardTitle class="text-lg">{{ plan.name }}</CardTitle>
                        <CardDescription v-if="plan.tagline">
                            {{ plan.tagline }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col">
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="text-xl font-bold text-muted-foreground"
                                >€</span
                            >
                            <span
                                class="text-5xl font-bold tracking-tight tabular-nums transition-[opacity,transform] duration-180"
                                :class="{
                                    'front-price-amount--swap': swapping,
                                }"
                                >{{ displayAmount(plan) }}</span
                            >
                            <span class="text-sm text-muted-foreground"
                                >/ mois</span
                            >
                        </div>
                        <p
                            class="mt-1 min-h-5 text-xs text-muted-foreground transition-opacity duration-180"
                            :class="{
                                'front-bill-note--swap': swapping,
                            }"
                        >
                            {{ billNote(plan) }}
                        </p>
                        <ul class="mt-6 flex-1 space-y-2.5">
                            <li
                                v-for="feature in plan.features"
                                :key="feature"
                                class="flex items-start gap-2.5 text-sm"
                            >
                                <Check
                                    class="mt-0.5 size-4 shrink-0 text-primary"
                                    aria-hidden="true"
                                />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                        <Button
                            class="mt-8 w-full"
                            :variant="plan.popular ? 'default' : 'outline'"
                            as-child
                        >
                            <a href="#commencer">
                                {{
                                    plan.popular
                                        ? 'Essai gratuit 14 jours'
                                        : 'Commencer'
                                }}
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <p
                v-if="props.table.plans.length === 0"
                v-reveal
                class="py-16 text-center text-muted-foreground"
            >
                Aucun plan n'est publié pour le moment.
            </p>
        </section>

        <!-- INCLUS -->
        <section class="mx-auto w-full max-w-6xl px-6 py-12">
            <h2 v-reveal class="text-center text-2xl font-bold tracking-tight">
                Tous les plans incluent
            </h2>
            <ItemGroup class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Item
                    v-for="(item, index) in includes"
                    :key="item.label"
                    v-reveal="{ delay: index * 60 }"
                    variant="outline"
                    class="bg-card/60"
                >
                    <ItemMedia variant="icon" class="text-primary">
                        <svg
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.9"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path v-for="d in item.icon" :key="d" :d="d" />
                        </svg>
                    </ItemMedia>
                    <ItemContent>
                        <ItemTitle>{{ item.label }}</ItemTitle>
                    </ItemContent>
                </Item>
            </ItemGroup>
        </section>

        <!-- FAQ -->
        <section
            v-if="props.table.faq.length > 0"
            class="mx-auto w-full max-w-3xl px-6 py-12"
        >
            <h2 v-reveal class="text-center text-2xl font-bold tracking-tight">
                Questions fréquentes
            </h2>
            <Accordion type="single" collapsible class="mt-8">
                <AccordionItem
                    v-for="(item, index) in props.table.faq"
                    :key="`${index}-${item.question}`"
                    v-reveal="{ delay: index * 60 }"
                    :value="`faq-${index}`"
                >
                    <AccordionTrigger class="text-sm font-semibold">
                        {{ item.question }}
                    </AccordionTrigger>
                    <AccordionContent>
                        <p class="text-muted-foreground">
                            {{ item.answer }}
                        </p>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </section>

        <!-- CTA -->
        <section
            id="commencer"
            class="mx-auto w-full max-w-6xl scroll-mt-20 px-6 pt-6 pb-24"
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
                    Essayez la plateforme,<br />sans engagement.
                </h2>
                <p class="relative mx-auto mt-4 max-w-lg text-muted-foreground">
                    14 jours d'essai sur le plan Croissance. Landing pages,
                    passerelle API et connecteurs inclus dès le premier jour.
                </p>
                <div class="relative mt-8 flex flex-wrap justify-center gap-3">
                    <Button as-child>
                        <a href="#commencer">Créer mon compte</a>
                    </Button>
                    <Button variant="outline" as-child>
                        <a :href="landingUrl">Revoir la landing</a>
                    </Button>
                </div>
            </div>
        </section>
    </FrontLayout>
</template>
