<script setup lang="ts">
// Formulaire de creation / edition d'un plan tarifaire : identite (nom,
// slug, accroche), prix mensuel et annuel saisis en EUROS (converts en
// centimes entiers), repeateur de features, mise en avant, activation et
// ordre d'affichage. Aucun import hors arbre back/.
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { BackPricingPlanFormPageProps } from '@/types';
import { index, store, update } from '@/routes/admin/pricing/plans';
import BackLayout from '../../layouts/BackLayout.vue';
import BackButton from '../../components/BackButton.vue';
import BackCard from '../../components/BackCard.vue';
import BackField from '../../components/BackField.vue';
import BackInput from '../../components/BackInput.vue';
import BackStatus from '../../components/BackStatus.vue';

const props = defineProps<BackPricingPlanFormPageProps>();

/* ------------------------------------------------------------------ */
/* Etat edite                                                          */
/* ------------------------------------------------------------------ */

const existing = computed(() => props.plan ?? null);
const isEdit = computed(() => existing.value !== null);

/** Donnees du formulaire, telles qu'envoyees au backend (snake_case). */
type PricingPlanFormData = {
    name: string;
    slug: string;
    tagline: string;
    price_monthly: number;
    price_yearly: number;
    currency: string;
    features: string[];
    popular: boolean;
    active: boolean;
    sort: number;
};

function toFormData(value: typeof existing.value): PricingPlanFormData {
    return {
        name: value?.name ?? '',
        slug: value?.slug ?? '',
        tagline: value?.tagline ?? '',
        price_monthly: value?.priceMonthly ?? 0,
        price_yearly: value?.priceYearly ?? 0,
        currency: value?.currency ?? 'EUR',
        features: value ? [...value.features] : [],
        popular: value?.popular ?? false,
        active: value?.active ?? true,
        sort: value?.sort ?? 0,
    };
}

const form = useForm<PricingPlanFormData>(toFormData(existing.value));

/* Prix saisis en euros (chaine libre : virgule ou point autorises). */
const priceMonthlyEuros = ref(centsToEurosInput(form.price_monthly));
const priceYearlyEuros = ref(centsToEurosInput(form.price_yearly));

/* Navigation creation -> edition (meme composant reutilise par Inertia) :
   on resynchronise le formulaire quand la page cible change. */
watch(
    () => props.plan?.id,
    () => {
        const next = toFormData(existing.value);
        form.name = next.name;
        form.slug = next.slug;
        form.tagline = next.tagline;
        form.price_monthly = next.price_monthly;
        form.price_yearly = next.price_yearly;
        form.currency = next.currency;
        form.features = next.features;
        form.popular = next.popular;
        form.active = next.active;
        form.sort = next.sort;
        priceMonthlyEuros.value = centsToEurosInput(next.price_monthly);
        priceYearlyEuros.value = centsToEurosInput(next.price_yearly);
        form.clearErrors();
        slugTouched.value = existing.value !== null;
    },
);

/* ------------------------------------------------------------------ */
/* Prix : conversion euros <-> centimes sans pertes de flottants       */
/* ------------------------------------------------------------------ */

/** Affichage d'un montant en centimes sous forme de saisie en euros. */
function centsToEurosInput(cents: number): string {
    const safe = Math.round(cents);
    if (safe % 100 === 0) {
        return String(Math.round(safe / 100));
    }

    return (safe / 100).toFixed(2);
}

/**
 * Convertit une saisie en euros vers des centimes (Math.round pour rester
 * en entiers). Retourne null si la saisie est invalide.
 */
function eurosToCents(value: string): number | null {
    const normalized = value.trim().replace(',', '.');
    if (normalized === '' || !/^\d+(\.\d{1,2})?$/.test(normalized)) {
        return null;
    }

    return Math.round(parseFloat(normalized) * 100);
}

/* ------------------------------------------------------------------ */
/* Slug : auto-genere depuis le nom tant qu'il n'est pas edite         */
/* ------------------------------------------------------------------ */

const slugTouched = ref(existing.value !== null);

const slugify = (value: string): string =>
    value
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

function onNameInput(): void {
    if (!slugTouched.value) {
        form.slug = slugify(form.name);
    }
}

function onSlugInput(): void {
    slugTouched.value = form.slug.length > 0;
}

/* ------------------------------------------------------------------ */
/* Repeateur de features                                               */
/* ------------------------------------------------------------------ */

function addFeature(): void {
    form.features.push('');
}

function removeFeature(featureIndex: number): void {
    form.features.splice(featureIndex, 1);
}

function moveFeature(featureIndex: number, offset: -1 | 1): void {
    const target = featureIndex + offset;

    if (target < 0 || target >= form.features.length) {
        return;
    }

    const [feature] = form.features.splice(featureIndex, 1);
    form.features.splice(target, 0, feature);
}

/* ------------------------------------------------------------------ */
/* Soumission                                                          */
/* ------------------------------------------------------------------ */

function onSubmit(): void {
    const monthly = eurosToCents(priceMonthlyEuros.value);
    if (monthly === null) {
        form.setError(
            'price_monthly',
            'Saisissez un montant positif, avec au plus deux décimales.',
        );
        return;
    }

    const yearly = eurosToCents(priceYearlyEuros.value);
    if (yearly === null) {
        form.setError(
            'price_yearly',
            'Saisissez un montant positif, avec au plus deux décimales.',
        );
        return;
    }

    form.price_monthly = monthly;
    form.price_yearly = yearly;
    form.sort = Number(form.sort) || 0;

    if (isEdit.value && existing.value !== null) {
        void form.patch(update({ plan: existing.value.id }).url);
        return;
    }

    void form.post(store.url());
}
</script>

<template>
    <BackLayout>
        <Head :title="isEdit ? 'Modifier le plan' : 'Nouveau plan tarifaire'" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-col gap-1">
                <p class="text-sm text-[var(--color-muted)]">
                    <a :href="index.url()" class="back-link">
                        Plans tarifaires
                    </a>
                    <span class="mx-1">/</span>
                    <span>{{ isEdit ? existing?.name : 'Nouveau plan' }}</span>
                </p>
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    {{
                        isEdit
                            ? 'Modifier le plan tarifaire'
                            : 'Nouveau plan tarifaire'
                    }}
                </h1>
            </header>

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <!-- Identite -->
                <BackCard
                    title="Identité"
                    description="Nom affiché sur la page tarifs et adresse interne."
                >
                    <div class="flex flex-col gap-5">
                        <BackField
                            label="Nom"
                            field-id="name"
                            :error="form.errors.name"
                            help="Ex. Découverte, Croissance, Scale."
                        >
                            <BackInput
                                id="name"
                                name="name"
                                v-model="form.name"
                                required
                                placeholder="Ex. Croissance"
                                @input="onNameInput"
                            />
                        </BackField>

                        <BackField
                            label="Slug"
                            field-id="slug"
                            :error="form.errors.slug"
                            help="Généré depuis le nom, modifiable."
                        >
                            <BackInput
                                id="slug"
                                name="slug"
                                v-model="form.slug"
                                required
                                placeholder="croissance"
                                @input="onSlugInput"
                            />
                        </BackField>

                        <BackField
                            label="Accroche"
                            field-id="tagline"
                            :error="form.errors.tagline"
                            help="Courte phrase affichée sous le nom du plan."
                        >
                            <BackInput
                                id="tagline"
                                name="tagline"
                                v-model="form.tagline"
                                required
                                placeholder="Pour les apps en production"
                            />
                        </BackField>
                    </div>
                </BackCard>

                <!-- Prix -->
                <BackCard
                    title="Prix"
                    description="Montants saisis en euros, stockés en centimes. Le prix annuel est le montant par mois avec engagement annuel."
                >
                    <div class="grid gap-5 sm:grid-cols-2">
                        <BackField
                            label="Prix mensuel (€)"
                            field-id="price_monthly"
                            :error="form.errors.price_monthly"
                            help="Montant facturé chaque mois, en euros. 0 = gratuit."
                        >
                            <BackInput
                                id="price_monthly"
                                name="price_monthly"
                                v-model="priceMonthlyEuros"
                                type="text"
                                inputmode="decimal"
                                required
                                placeholder="29"
                            />
                        </BackField>

                        <BackField
                            label="Prix annuel (€ / mois)"
                            field-id="price_yearly"
                            :error="form.errors.price_yearly"
                            help="Montant par mois avec engagement annuel, en euros."
                        >
                            <BackInput
                                id="price_yearly"
                                name="price_yearly"
                                v-model="priceYearlyEuros"
                                type="text"
                                inputmode="decimal"
                                required
                                placeholder="23"
                            />
                        </BackField>

                        <BackField
                            label="Devise"
                            field-id="currency"
                            help="Devise figée pour cette phase : EUR."
                        >
                            <BackInput
                                id="currency"
                                name="currency"
                                value="EUR"
                                readonly
                            />
                        </BackField>

                        <BackField
                            label="Ordre d'affichage"
                            field-id="sort"
                            :error="form.errors.sort"
                            help="Les plans sont listés du plus petit au plus grand."
                        >
                            <BackInput
                                id="sort"
                                name="sort"
                                v-model="form.sort"
                                type="number"
                                min="0"
                                step="1"
                                required
                            />
                        </BackField>
                    </div>
                </BackCard>

                <!-- Inclus -->
                <BackCard
                    title="Inclus"
                    description="Liste des prestations affichées dans le plan, dans l'ordre."
                >
                    <div class="flex flex-col gap-4">
                        <p
                            v-if="form.features.length === 0"
                            class="text-sm text-[var(--color-muted)]"
                        >
                            Aucune ligne : ajoutez au moins une prestation
                            incluse.
                        </p>

                        <div
                            v-for="(_, featureIndex) in form.features"
                            :key="featureIndex"
                            class="flex items-start gap-2"
                        >
                            <div class="flex-1">
                                <BackInput
                                    :id="`feature-${featureIndex}`"
                                    :name="`features[${featureIndex}]`"
                                    v-model="form.features[featureIndex]"
                                    required
                                    placeholder="Ex. 50 000 appels API / mois"
                                />
                                <p
                                    v-if="
                                        form.errors[`features.${featureIndex}`]
                                    "
                                    class="back-error mt-1"
                                >
                                    {{
                                        form.errors[`features.${featureIndex}`]
                                    }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1">
                                <BackButton
                                    variant="secondary"
                                    :disabled="featureIndex === 0"
                                    :aria-label="`Monter la ligne ${featureIndex + 1}`"
                                    @click="moveFeature(featureIndex, -1)"
                                >
                                    ↑
                                </BackButton>
                                <BackButton
                                    variant="secondary"
                                    :disabled="
                                        featureIndex ===
                                        form.features.length - 1
                                    "
                                    :aria-label="`Descendre la ligne ${featureIndex + 1}`"
                                    @click="moveFeature(featureIndex, 1)"
                                >
                                    ↓
                                </BackButton>
                                <BackButton
                                    variant="danger"
                                    :aria-label="`Supprimer la ligne ${featureIndex + 1}`"
                                    @click="removeFeature(featureIndex)"
                                >
                                    Supprimer
                                </BackButton>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <div>
                                <BackButton
                                    variant="secondary"
                                    @click="addFeature"
                                >
                                    Ajouter une ligne
                                </BackButton>
                            </div>
                            <p v-if="form.errors.features" class="back-error">
                                {{ form.errors.features }}
                            </p>
                        </div>
                    </div>
                </BackCard>

                <!-- Mise en avant et activation -->
                <BackCard
                    title="Mise en avant et activation"
                    description="Visibilité du plan côté vitrine."
                >
                    <div class="flex flex-col gap-5">
                        <label
                            class="flex items-center gap-2.5 text-sm text-[var(--color-ink)]"
                            for="popular"
                        >
                            <input
                                id="popular"
                                name="popular"
                                type="checkbox"
                                v-model="form.popular"
                                class="back-checkbox"
                            />
                            <span>
                                Mettre en avant ce plan (« Populaire » : bordure
                                dégradée et badge sur la page tarifs)
                            </span>
                        </label>

                        <label
                            class="flex items-center gap-2.5 text-sm text-[var(--color-ink)]"
                            for="active"
                        >
                            <input
                                id="active"
                                name="active"
                                type="checkbox"
                                v-model="form.active"
                                class="back-checkbox"
                            />
                            <span>Plan actif (visible côté vitrine)</span>
                        </label>

                        <BackStatus v-if="!form.active" tone="warning">
                            Ce plan sera masqué sur la page tarifs publique tant
                            qu'il reste inactif.
                        </BackStatus>
                    </div>
                </BackCard>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a :href="index.url()" class="back-btn back-btn--secondary">
                        Annuler
                    </a>
                    <BackButton type="submit" :loading="form.processing">
                        {{
                            isEdit
                                ? 'Enregistrer les modifications'
                                : 'Créer le plan'
                        }}
                    </BackButton>
                </div>
            </form>
        </section>
    </BackLayout>
</template>
