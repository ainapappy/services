<script setup lang="ts">
// Formulaire de creation / edition d'un plan tarifaire : identite (nom,
// slug, accroche), prix mensuel et annuel saisis en EUROS (convertis en
// centimes entiers a la soumission), repeateur de features, mise en avant,
// activation et ordre d'affichage. Carte unique shadcn par sections.
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { BackPricingPlanFormPageProps } from '@/types';
import { index, store, update } from '@/routes/admin/pricing/plans';
import { ArrowDownIcon, ArrowUpIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';

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
            <BackPageHeader
                :title="
                    isEdit
                        ? 'Modifier le plan tarifaire'
                        : 'Nouveau plan tarifaire'
                "
            />

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <Card>
                    <!-- Identite -->
                    <CardHeader>
                        <CardTitle>Identité</CardTitle>
                        <CardDescription>
                            Nom affiché sur la page tarifs et adresse interne.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-5">
                        <Field>
                            <FieldLabel for="name">Nom</FieldLabel>
                            <Input
                                id="name"
                                name="name"
                                v-model="form.name"
                                required
                                placeholder="Ex. Croissance"
                                @input="onNameInput"
                            />
                            <FieldDescription>
                                Ex. Découverte, Croissance, Scale.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.name]" />
                        </Field>

                        <Field>
                            <FieldLabel for="slug">Slug</FieldLabel>
                            <Input
                                id="slug"
                                name="slug"
                                v-model="form.slug"
                                required
                                class="font-mono"
                                placeholder="croissance"
                                @input="onSlugInput"
                            />
                            <FieldDescription>
                                Généré depuis le nom, modifiable.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.slug]" />
                        </Field>

                        <Field>
                            <FieldLabel for="tagline">Accroche</FieldLabel>
                            <Input
                                id="tagline"
                                name="tagline"
                                v-model="form.tagline"
                                required
                                placeholder="Pour les apps en production"
                            />
                            <FieldDescription>
                                Courte phrase affichée sous le nom du plan.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.tagline]" />
                        </Field>
                    </CardContent>

                    <Separator />

                    <!-- Prix -->
                    <CardContent class="flex flex-col gap-5">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                Prix
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Montants saisis en euros, stockés en centimes.
                                Le prix annuel est le montant par mois avec
                                engagement annuel.
                            </p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <Field>
                                <FieldLabel for="price_monthly">
                                    Prix mensuel (€)
                                </FieldLabel>
                                <Input
                                    id="price_monthly"
                                    name="price_monthly"
                                    v-model="priceMonthlyEuros"
                                    type="text"
                                    inputmode="decimal"
                                    required
                                    placeholder="29"
                                />
                                <FieldDescription>
                                    Montant facturé chaque mois, en euros. 0 =
                                    gratuit.
                                </FieldDescription>
                                <FieldError
                                    :errors="[form.errors.price_monthly]"
                                />
                            </Field>

                            <Field>
                                <FieldLabel for="price_yearly">
                                    Prix annuel (€ / mois)
                                </FieldLabel>
                                <Input
                                    id="price_yearly"
                                    name="price_yearly"
                                    v-model="priceYearlyEuros"
                                    type="text"
                                    inputmode="decimal"
                                    required
                                    placeholder="23"
                                />
                                <FieldDescription>
                                    Montant par mois avec engagement annuel, en
                                    euros.
                                </FieldDescription>
                                <FieldError
                                    :errors="[form.errors.price_yearly]"
                                />
                            </Field>

                            <Field>
                                <FieldLabel for="currency">Devise</FieldLabel>
                                <Input
                                    id="currency"
                                    name="currency"
                                    model-value="EUR"
                                    readonly
                                />
                                <FieldDescription>
                                    Devise figée pour cette phase : EUR.
                                </FieldDescription>
                            </Field>

                            <Field>
                                <FieldLabel for="sort">
                                    Ordre d'affichage
                                </FieldLabel>
                                <Input
                                    id="sort"
                                    name="sort"
                                    v-model="form.sort"
                                    type="number"
                                    min="0"
                                    step="1"
                                    required
                                />
                                <FieldDescription>
                                    Les plans sont listés du plus petit au plus
                                    grand.
                                </FieldDescription>
                                <FieldError :errors="[form.errors.sort]" />
                            </Field>
                        </div>
                    </CardContent>

                    <Separator />

                    <!-- Inclus -->
                    <CardContent class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                Inclus
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Liste des prestations affichées dans le plan,
                                dans l'ordre.
                            </p>
                        </div>

                        <p
                            v-if="form.features.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Aucune ligne : ajoutez au moins une prestation
                            incluse.
                        </p>

                        <div
                            v-for="(_, featureIndex) in form.features"
                            :key="featureIndex"
                            class="flex items-start gap-2"
                        >
                            <Field class="flex-1">
                                <FieldLabel
                                    class="sr-only"
                                    :for="`feature-${featureIndex}`"
                                >
                                    Ligne {{ featureIndex + 1 }}
                                </FieldLabel>
                                <Input
                                    :id="`feature-${featureIndex}`"
                                    :name="`features[${featureIndex}]`"
                                    v-model="form.features[featureIndex]"
                                    required
                                    placeholder="Ex. 50 000 appels API / mois"
                                />
                                <FieldError
                                    :errors="[
                                        form.errors[`features.${featureIndex}`],
                                    ]"
                                />
                            </Field>

                            <div class="flex items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :disabled="featureIndex === 0"
                                    :aria-label="`Monter la ligne ${featureIndex + 1}`"
                                    @click="moveFeature(featureIndex, -1)"
                                >
                                    <ArrowUpIcon />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :disabled="
                                        featureIndex ===
                                        form.features.length - 1
                                    "
                                    :aria-label="`Descendre la ligne ${featureIndex + 1}`"
                                    @click="moveFeature(featureIndex, 1)"
                                >
                                    <ArrowDownIcon />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive hover:text-destructive"
                                    :aria-label="`Supprimer la ligne ${featureIndex + 1}`"
                                    @click="removeFeature(featureIndex)"
                                >
                                    <Trash2Icon />
                                </Button>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <div>
                                <Button variant="outline" @click="addFeature">
                                    <PlusIcon />
                                    Ajouter une ligne
                                </Button>
                            </div>
                            <FieldError :errors="[form.errors.features]" />
                        </div>
                    </CardContent>

                    <Separator />

                    <!-- Mise en avant et activation -->
                    <CardContent class="flex flex-col gap-5">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                Mise en avant et activation
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Visibilité du plan côté vitrine.
                            </p>
                        </div>

                        <Field orientation="horizontal">
                            <Switch id="popular" v-model="form.popular" />
                            <FieldContent>
                                <FieldLabel for="popular">
                                    Mettre en avant ce plan
                                </FieldLabel>
                                <FieldDescription>
                                    « Populaire » : bordure dégradée et badge
                                    sur la page tarifs.
                                </FieldDescription>
                            </FieldContent>
                        </Field>

                        <Field orientation="horizontal">
                            <Switch id="active" v-model="form.active" />
                            <FieldContent>
                                <FieldLabel for="active">
                                    Plan actif
                                </FieldLabel>
                                <FieldDescription>
                                    Visible côté vitrine.
                                </FieldDescription>
                            </FieldContent>
                        </Field>

                        <Alert v-if="!form.active" variant="warning">
                            <AlertDescription>
                                Ce plan sera masqué sur la page tarifs publique
                                tant qu'il reste inactif.
                            </AlertDescription>
                        </Alert>
                    </CardContent>

                    <CardFooter class="justify-end gap-3 border-t pt-6">
                        <Button variant="outline" as-child>
                            <Link :href="index.url()">Annuler</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" class="size-4" />
                            {{
                                isEdit
                                    ? 'Enregistrer les modifications'
                                    : 'Créer le plan'
                            }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </section>
    </BackLayout>
</template>
