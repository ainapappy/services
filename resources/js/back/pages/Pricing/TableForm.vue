<script setup lang="ts">
// Formulaire de creation / edition d'une table de prix : contenu (titre,
// slug, sous-titre), selection et ordre des plans (le pivot porte le tri),
// repeateur de FAQ et publication. Aucun import hors arbre back/.
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { BackPricingTableFormPageProps, FaqItem } from '@/types';
import { index, publish, store, update } from '@/routes/admin/pricing/tables';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';
import BackCard from '../../components/BackCard.vue';
import BackField from '../../components/BackField.vue';
import BackInput from '../../components/BackInput.vue';
import BackStatus from '../../components/BackStatus.vue';

const props = defineProps<BackPricingTableFormPageProps>();

/* ------------------------------------------------------------------ */
/* Etat edite                                                          */
/* ------------------------------------------------------------------ */

const existing = computed(() => props.table ?? null);
const isEdit = computed(() => existing.value !== null);
const isPublished = computed(
    () =>
        existing.value?.publishedAt !== undefined &&
        existing.value?.publishedAt !== null,
);

/** Donnees du formulaire, telles qu'envoyees au backend (snake_case). */
type PricingTableFormData = {
    title: string;
    slug: string;
    subtitle: string;
    faq: FaqItem[];
    plan_ids: number[];
};

function toFormData(value: typeof existing.value): PricingTableFormData {
    return {
        title: value?.title ?? '',
        slug: value?.slug ?? '',
        subtitle: value?.subtitle ?? '',
        faq: value ? value.faq.map((item) => ({ ...item })) : [],
        plan_ids: value ? [...value.planIds] : [],
    };
}

const form = useForm<PricingTableFormData>(toFormData(existing.value));

/* Navigation creation -> edition (meme composant reutilise par Inertia) :
   on resynchronise le formulaire quand la page cible change. */
watch(
    () => props.table?.id,
    () => {
        const next = toFormData(existing.value);
        form.title = next.title;
        form.slug = next.slug;
        form.subtitle = next.subtitle;
        form.faq = next.faq;
        form.plan_ids = next.plan_ids;
        form.clearErrors();
        slugTouched.value = existing.value !== null;
    },
);

/* ------------------------------------------------------------------ */
/* Slug : auto-genere depuis le titre tant qu'il n'est pas edite       */
/* ------------------------------------------------------------------ */

const slugTouched = ref(existing.value !== null);

const slugify = (value: string): string =>
    value
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

function onTitleInput(): void {
    if (!slugTouched.value) {
        form.slug = slugify(form.title);
    }
}

function onSlugInput(): void {
    slugTouched.value = form.slug.length > 0;
}

/* ------------------------------------------------------------------ */
/* Selection et ordre des plans (l'ordre = le sort du pivot)           */
/* ------------------------------------------------------------------ */

function isSelected(planId: number): boolean {
    return form.plan_ids.includes(planId);
}

function planPosition(planId: number): number | null {
    const index = form.plan_ids.indexOf(planId);
    return index === -1 ? null : index + 1;
}

function togglePlan(planId: number): void {
    const index = form.plan_ids.indexOf(planId);

    if (index === -1) {
        form.plan_ids.push(planId);
        return;
    }

    form.plan_ids.splice(index, 1);
}

function movePlan(planId: number, offset: -1 | 1): void {
    const from = form.plan_ids.indexOf(planId);
    const target = from + offset;

    if (from === -1 || target < 0 || target >= form.plan_ids.length) {
        return;
    }

    const [moved] = form.plan_ids.splice(from, 1);
    form.plan_ids.splice(target, 0, moved);
}

/* ------------------------------------------------------------------ */
/* Repeateur de FAQ                                                    */
/* ------------------------------------------------------------------ */

function addQuestion(): void {
    form.faq.push({ question: '', answer: '' });
}

function removeQuestion(faqIndex: number): void {
    form.faq.splice(faqIndex, 1);
}

function moveQuestion(faqIndex: number, offset: -1 | 1): void {
    const target = faqIndex + offset;

    if (target < 0 || target >= form.faq.length) {
        return;
    }

    const [item] = form.faq.splice(faqIndex, 1);
    form.faq.splice(target, 0, item);
}

/* ------------------------------------------------------------------ */
/* Publication                                                         */
/* ------------------------------------------------------------------ */

const publishing = ref(false);

/* La route publish est une bascule : même appel pour publier et dépublier.
   Le backend redirige vers l'index actualise. */
function onTogglePublish(): void {
    if (existing.value === null || publishing.value) {
        return;
    }

    publishing.value = true;
    router.patch(
        publish({ pricingTable: existing.value.id }).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                publishing.value = false;
            },
        },
    );
}

/* ------------------------------------------------------------------ */
/* Soumission                                                          */
/* ------------------------------------------------------------------ */

function onSubmit(): void {
    if (isEdit.value && existing.value !== null) {
        void form.patch(update({ pricingTable: existing.value.id }).url);
        return;
    }

    void form.post(store.url());
}

const publishedAtLabel = computed(() => {
    const value = existing.value?.publishedAt;

    if (value === null || value === undefined) {
        return '';
    }

    return new Date(value).toLocaleString('fr-FR', {
        dateStyle: 'long',
        timeStyle: 'short',
    });
});
</script>

<template>
    <BackLayout>
        <Head
            :title="isEdit ? 'Modifier la table' : 'Nouvelle table de prix'"
        />

        <section class="flex flex-col gap-6">
            <header class="flex flex-col gap-1">
                <p class="text-sm text-[var(--color-muted)]">
                    <a :href="index.url()" class="back-link">
                        Tables de prix
                    </a>
                    <span class="mx-1">/</span>
                    <span>{{
                        isEdit ? existing?.title : 'Nouvelle table'
                    }}</span>
                </p>
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    {{
                        isEdit
                            ? 'Modifier la table de prix'
                            : 'Nouvelle table de prix'
                    }}
                </h1>
            </header>

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <!-- Contenu -->
                <BackCard
                    title="Contenu"
                    description="Titre et sous-titre affichés en haut de la page tarifs."
                >
                    <div class="flex flex-col gap-5">
                        <BackField
                            label="Titre"
                            field-id="title"
                            :error="form.errors.title"
                            help="Grand titre de la page tarifs."
                        >
                            <BackInput
                                id="title"
                                name="title"
                                v-model="form.title"
                                required
                                placeholder="Ex. Un tarif clair, qui grandit avec vous."
                                @input="onTitleInput"
                            />
                        </BackField>

                        <BackField
                            label="Slug"
                            field-id="slug"
                            :error="form.errors.slug"
                            help="Généré depuis le titre, modifiable."
                        >
                            <BackInput
                                id="slug"
                                name="slug"
                                v-model="form.slug"
                                required
                                placeholder="tarifs"
                                @input="onSlugInput"
                            />
                        </BackField>

                        <BackField
                            label="Sous-titre"
                            field-id="subtitle"
                            :error="form.errors.subtitle"
                            help="Phrase d'accroche affichée sous le titre."
                        >
                            <textarea
                                id="subtitle"
                                name="subtitle"
                                v-model="form.subtitle"
                                rows="3"
                                required
                                class="back-input"
                                placeholder="Landing pages et tables de prix incluses partout."
                            ></textarea>
                        </BackField>
                    </div>
                </BackCard>

                <!-- Plans -->
                <BackCard
                    title="Plans"
                    description="Cochez les plans à afficher et ordonnez-les : l'ordre choisi ici est celui de la page tarifs."
                >
                    <div class="flex flex-col gap-4">
                        <p
                            v-if="props.plans.length === 0"
                            class="text-sm text-[var(--color-muted)]"
                        >
                            Aucun plan disponible : créez d'abord des plans
                            tarifaires.
                        </p>

                        <p
                            v-else-if="form.plan_ids.length === 0"
                            class="text-sm text-[var(--color-muted)]"
                        >
                            Aucun plan sélectionné.
                        </p>

                        <ul class="flex flex-col gap-2">
                            <li
                                v-for="plan in props.plans"
                                :key="plan.id"
                                class="flex items-center gap-3 rounded-lg border border-[rgba(23,23,28,0.09)] px-4 py-3"
                            >
                                <label
                                    class="flex flex-1 items-center gap-2.5 text-sm text-[var(--color-ink)]"
                                    :for="`plan-${plan.id}`"
                                >
                                    <input
                                        :id="`plan-${plan.id}`"
                                        type="checkbox"
                                        class="back-checkbox"
                                        :checked="isSelected(plan.id)"
                                        @change="togglePlan(plan.id)"
                                    />
                                    <span>{{ plan.name }}</span>
                                    <BackBadge v-if="!plan.active" class="ml-1">
                                        Inactif
                                    </BackBadge>
                                </label>

                                <template v-if="planPosition(plan.id) !== null">
                                    <span
                                        class="text-xs text-[var(--color-muted)] tabular-nums"
                                    >
                                        N°{{ planPosition(plan.id) }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <BackButton
                                            variant="secondary"
                                            :disabled="
                                                planPosition(plan.id) === 1
                                            "
                                            :aria-label="`Monter le plan ${plan.name}`"
                                            @click="movePlan(plan.id, -1)"
                                        >
                                            ↑
                                        </BackButton>
                                        <BackButton
                                            variant="secondary"
                                            :disabled="
                                                planPosition(plan.id) ===
                                                form.plan_ids.length
                                            "
                                            :aria-label="`Descendre le plan ${plan.name}`"
                                            @click="movePlan(plan.id, 1)"
                                        >
                                            ↓
                                        </BackButton>
                                    </div>
                                </template>
                            </li>
                        </ul>

                        <p v-if="form.errors.plan_ids" class="back-error">
                            {{ form.errors.plan_ids }}
                        </p>
                    </div>
                </BackCard>

                <!-- FAQ -->
                <BackCard
                    title="FAQ"
                    description="Questions fréquentes affichées en accordéon sous les plans, dans l'ordre de la liste."
                >
                    <div class="flex flex-col gap-4">
                        <p
                            v-if="form.faq.length === 0"
                            class="text-sm text-[var(--color-muted)]"
                        >
                            Aucune question pour le moment.
                        </p>

                        <div
                            v-for="(_, faqIndex) in form.faq"
                            :key="faqIndex"
                            class="rounded-lg border border-[rgba(23,23,28,0.09)] p-4"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                                >
                                    Question {{ faqIndex + 1 }}
                                </p>
                                <div class="flex items-center gap-1">
                                    <BackButton
                                        variant="secondary"
                                        :disabled="faqIndex === 0"
                                        :aria-label="`Monter la question ${faqIndex + 1}`"
                                        @click="moveQuestion(faqIndex, -1)"
                                    >
                                        ↑
                                    </BackButton>
                                    <BackButton
                                        variant="secondary"
                                        :disabled="
                                            faqIndex === form.faq.length - 1
                                        "
                                        :aria-label="`Descendre la question ${faqIndex + 1}`"
                                        @click="moveQuestion(faqIndex, 1)"
                                    >
                                        ↓
                                    </BackButton>
                                    <BackButton
                                        variant="danger"
                                        :aria-label="`Supprimer la question ${faqIndex + 1}`"
                                        @click="removeQuestion(faqIndex)"
                                    >
                                        Supprimer
                                    </BackButton>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-col gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label
                                        class="back-label"
                                        :for="`faq-${faqIndex}-question`"
                                    >
                                        Question
                                    </label>
                                    <BackInput
                                        :id="`faq-${faqIndex}-question`"
                                        :name="`faq[${faqIndex}][question]`"
                                        v-model="form.faq[faqIndex].question"
                                        required
                                    />
                                    <p
                                        v-if="
                                            form.errors[
                                                `faq.${faqIndex}.question`
                                            ]
                                        "
                                        class="back-error"
                                    >
                                        {{
                                            form.errors[
                                                `faq.${faqIndex}.question`
                                            ]
                                        }}
                                    </p>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label
                                        class="back-label"
                                        :for="`faq-${faqIndex}-answer`"
                                    >
                                        Réponse
                                    </label>
                                    <textarea
                                        :id="`faq-${faqIndex}-answer`"
                                        :name="`faq[${faqIndex}][answer]`"
                                        v-model="form.faq[faqIndex].answer"
                                        rows="3"
                                        class="back-input"
                                        required
                                    ></textarea>
                                    <p
                                        v-if="
                                            form.errors[
                                                `faq.${faqIndex}.answer`
                                            ]
                                        "
                                        class="back-error"
                                    >
                                        {{
                                            form.errors[
                                                `faq.${faqIndex}.answer`
                                            ]
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <div>
                                <BackButton
                                    variant="secondary"
                                    @click="addQuestion"
                                >
                                    Ajouter une question
                                </BackButton>
                            </div>
                            <p v-if="form.errors.faq" class="back-error">
                                {{ form.errors.faq }}
                            </p>
                        </div>
                    </div>
                </BackCard>

                <!-- Publication -->
                <BackCard
                    title="Publication"
                    description="État de diffusion de la table sur la page tarifs."
                >
                    <div
                        class="flex flex-wrap items-center gap-4 border-t border-[rgba(23,23,28,0.09)] pt-5"
                    >
                        <template v-if="isEdit">
                            <BackBadge
                                :variant="isPublished ? 'success' : 'muted'"
                            >
                                {{ isPublished ? 'Publiée' : 'Brouillon' }}
                            </BackBadge>
                            <span
                                v-if="isPublished"
                                class="text-sm text-[var(--color-muted)]"
                            >
                                Depuis le {{ publishedAtLabel }}
                            </span>

                            <BackButton
                                variant="secondary"
                                :loading="publishing"
                                @click="onTogglePublish"
                            >
                                {{ isPublished ? 'Dépublier' : 'Publier' }}
                            </BackButton>
                        </template>
                        <BackStatus v-else tone="info">
                            La table pourra être publiée après enregistrement.
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
                                : 'Créer la table'
                        }}
                    </BackButton>
                </div>
            </form>
        </section>
    </BackLayout>
</template>
