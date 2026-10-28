<script setup lang="ts">
// Page tarifs publique : retranscription du prototype
// .knowledge/design/pricing.html (switch mensuel/annuel, plan populaire,
// FAQ accordéon, CTA final). Titre, plans et FAQ viennent des props ; la
// bascule des prix est purement locale (les deux prix sont dans les props,
// aucune requête réseau). Le CTA final et la section « inclus » restent
// statiques comme dans le prototype.
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { home, pricing } from '@/routes';
import type { FrontPricingShowPageProps, PricingShowPlan } from '@/types';
import { vReveal } from '../../directives/reveal';
// Charte front indispensable : sans cet import, aucune regle front-* n'atteint
// la page (front.css est charge par les pages front, pas par le bundle global).
import '../../../../css/front.css';

const props = defineProps<FrontPricingShowPageProps>();

const appName = import.meta.env.VITE_APP_NAME || 'Services';

const landingUrl = home.url();
const pricingUrl = pricing.url();

/* ------------------------------------------------------------------ */
/* SEO                                                                 */
/* ------------------------------------------------------------------ */

const seoTitle = computed(() => props.table.title || 'Tarifs');

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
/* FAQ accordéon : un seul panneau ouvert à la fois                    */
/* ------------------------------------------------------------------ */

const openQuestion = ref<number | null>(null);

function toggleQuestion(questionIndex: number): void {
    openQuestion.value =
        openQuestion.value === questionIndex ? null : questionIndex;
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
    <div>
        <Head :title="seoTitle">
            <meta name="description" :content="props.table.subtitle" />
        </Head>

        <!-- NAV -->
        <header class="front-nav" :class="{ 'front-nav--scrolled': scrolled }">
            <div class="front-nav-inner front-container">
                <a class="front-brand" :href="landingUrl">
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
                    <a :href="`${landingUrl}#modules`">Modules</a>
                    <a :href="`${landingUrl}#stats`">Chiffres</a>
                    <a :href="pricingUrl" class="is-active">Tarifs</a>
                </nav>
                <div class="front-nav-cta">
                    <a
                        class="front-cta front-cta-secondary front-cta--sm"
                        :href="landingUrl"
                    >
                        Accueil
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

            <!-- HERO + SWITCH -->
            <section class="front-hero front-hero--pricing">
                <div
                    class="front-glow front-glow--top"
                    aria-hidden="true"
                ></div>
                <div class="front-container">
                    <p v-reveal class="front-kicker">Tarifs</p>
                    <h1 v-reveal="{ delay: 60 }" class="front-hero-title">
                        {{ props.table.title }}
                    </h1>
                    <p v-reveal="{ delay: 140 }" class="front-lead">
                        {{ props.table.subtitle }}
                    </p>
                    <div v-reveal="{ delay: 220 }" class="front-switch-wrap">
                        <div
                            class="front-switch"
                            :class="{ 'front-switch--annual': annual }"
                            role="radiogroup"
                            aria-label="Périodicité de facturation"
                        >
                            <span
                                class="front-switch-thumb"
                                aria-hidden="true"
                            ></span>
                            <label
                                class="front-switch-option"
                                :class="{ on: !annual }"
                            >
                                <input
                                    class="sr-only"
                                    type="radio"
                                    name="billing"
                                    value="monthly"
                                    :checked="!annual"
                                    @change="setBilling(false)"
                                />
                                Mensuel
                            </label>
                            <label
                                class="front-switch-option"
                                :class="{ on: annual }"
                            >
                                <input
                                    class="sr-only"
                                    type="radio"
                                    name="billing"
                                    value="yearly"
                                    :checked="annual"
                                    @change="setBilling(true)"
                                />
                                Annuel
                                <span class="front-badge-save">-20 %</span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PLANS -->
            <section class="front-plans">
                <div class="front-container">
                    <div class="front-plans-grid">
                        <article
                            v-for="(plan, index) in props.table.plans"
                            :key="plan.slug"
                            v-reveal="{ delay: index * 100 }"
                            class="front-plan"
                            :class="{
                                'front-plan--popular': plan.popular,
                            }"
                        >
                            <span v-if="plan.popular" class="front-tag-popular">
                                Populaire
                            </span>
                            <h3>{{ plan.name }}</h3>
                            <p v-if="plan.tagline" class="front-plan-desc">
                                {{ plan.tagline }}
                            </p>
                            <div class="front-price">
                                <span class="cur">€</span>
                                <span
                                    class="front-price-amount"
                                    :class="{
                                        'front-price-amount--swap': swapping,
                                    }"
                                    >{{ displayAmount(plan) }}</span
                                >
                                <span class="front-price-per">/ mois</span>
                            </div>
                            <p
                                class="front-bill-note"
                                :class="{
                                    'front-bill-note--swap': swapping,
                                }"
                            >
                                {{ billNote(plan) }}
                            </p>
                            <ul class="front-plan-features">
                                <li
                                    v-for="feature in plan.features"
                                    :key="feature"
                                >
                                    {{ feature }}
                                </li>
                            </ul>
                            <a
                                class="front-cta front-plan-cta"
                                :class="
                                    plan.popular
                                        ? 'front-cta-primary'
                                        : 'front-cta-secondary'
                                "
                                href="#commencer"
                            >
                                {{
                                    plan.popular
                                        ? 'Essai gratuit 14 jours'
                                        : 'Commencer'
                                }}
                            </a>
                        </article>
                    </div>

                    <p
                        v-if="props.table.plans.length === 0"
                        v-reveal
                        class="front-plans-empty"
                    >
                        Aucun plan n'est publié pour le moment.
                    </p>
                </div>
            </section>

            <!-- INCLUS -->
            <section class="front-includes">
                <div class="front-container">
                    <h2 v-reveal>Tous les plans incluent</h2>
                    <div class="front-includes-grid">
                        <div
                            v-for="(item, index) in includes"
                            :key="item.label"
                            v-reveal="{ delay: index * 60 }"
                            class="front-inc"
                        >
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
                            {{ item.label }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section v-if="props.table.faq.length > 0" class="front-faq">
                <h2 v-reveal>Questions fréquentes</h2>
                <div
                    v-for="(item, index) in props.table.faq"
                    :key="`${index}-${item.question}`"
                    v-reveal="{ delay: index * 60 }"
                    class="front-faq-item"
                    :class="{
                        'front-faq-item--open': openQuestion === index,
                    }"
                >
                    <button
                        :id="`faq-button-${index}`"
                        type="button"
                        class="front-faq-q"
                        :aria-expanded="openQuestion === index"
                        :aria-controls="`faq-panel-${index}`"
                        @click="toggleQuestion(index)"
                    >
                        {{ item.question }}
                    </button>
                    <div
                        v-show="openQuestion === index"
                        :id="`faq-panel-${index}`"
                        class="front-faq-a"
                        role="region"
                        :aria-labelledby="`faq-button-${index}`"
                    >
                        <p>{{ item.answer }}</p>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section id="commencer" class="front-cta-section">
                <div class="front-container">
                    <div v-reveal class="front-cta-panel">
                        <h2>Essayez la plateforme,<br />sans engagement.</h2>
                        <p>
                            14 jours d'essai sur le plan Croissance. Landing
                            pages, passerelle API et connecteurs inclus dès le
                            premier jour.
                        </p>
                        <div class="front-cta-actions">
                            <a
                                class="front-cta front-cta-light"
                                href="#commencer"
                            >
                                Créer mon compte
                            </a>
                            <a
                                class="front-cta front-cta-outline-light"
                                :href="landingUrl"
                            >
                                Revoir la landing
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- FOOTER -->
        <footer class="front-footer">
            <div class="front-container front-foot">
                <a class="front-brand" :href="landingUrl">
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
                    <a :href="landingUrl">Landing</a>
                    <a :href="pricingUrl">Tarifs</a>
                    <a :href="landingUrl">Accueil</a>
                </nav>
                <p class="front-copy">
                    © {{ new Date().getFullYear() }} {{ appName }} — Tous droits
                    réservés.
                </p>
            </div>
        </footer>
    </div>
</template>
