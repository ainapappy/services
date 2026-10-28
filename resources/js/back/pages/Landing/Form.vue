<script setup lang="ts">
// Formulaire de creation / edition d'une landing page : contenu (titre,
// slug, heros), repeateur de CTA, SEO, accueil et publication.
// Aucun import hors arbre back/.
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { BackLandingFormPageProps, Cta } from '@/types';
import { index, publish, store, update } from '@/routes/admin/landing';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';
import BackCard from '../../components/BackCard.vue';
import BackField from '../../components/BackField.vue';
import BackInput from '../../components/BackInput.vue';
import BackStatus from '../../components/BackStatus.vue';

const props = defineProps<BackLandingFormPageProps>();

/* ------------------------------------------------------------------ */
/* Etat edite                                                          */
/* ------------------------------------------------------------------ */

const existing = computed(() => props.landingPage ?? null);
const isEdit = computed(() => existing.value !== null);
const isPublished = computed(
    () =>
        existing.value?.publishedAt !== undefined &&
        existing.value?.publishedAt !== null,
);

/** Donnees du formulaire, telles qu'envoyees au backend (snake_case). */
type LandingPageFormData = {
    title: string;
    slug: string;
    hero_title: string;
    hero_subtitle: string;
    ctas: Cta[];
    seo: {
        title: string;
        description: string;
    };
    is_home: boolean;
};

function toFormData(value: typeof existing.value): LandingPageFormData {
    return {
        title: value?.title ?? '',
        slug: value?.slug ?? '',
        hero_title: value?.heroTitle ?? '',
        hero_subtitle: value?.heroSubtitle ?? '',
        ctas: value ? value.ctas.map((cta) => ({ ...cta })) : [],
        seo: {
            title: value?.seo.title ?? '',
            description: value?.seo.description ?? '',
        },
        is_home: value?.isHome ?? false,
    };
}

const form = useForm<LandingPageFormData>(toFormData(existing.value));

/* Navigation creation -> edition (meme composant reutilise par Inertia) :
   on resynchronise le formulaire quand la page cible change. */
watch(
    () => props.landingPage?.id,
    () => {
        const next = toFormData(existing.value);
        form.title = next.title;
        form.slug = next.slug;
        form.hero_title = next.hero_title;
        form.hero_subtitle = next.hero_subtitle;
        form.ctas = next.ctas;
        form.seo = next.seo;
        form.is_home = next.is_home;
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
/* Repeateur de CTA                                                    */
/* ------------------------------------------------------------------ */

/* Le backend accepte au maximum 8 CTA (regle ctas.max). */
const MAX_CTAS = 8;

const hasMaxCtas = computed(() => form.ctas.length >= MAX_CTAS);

function addCta(): void {
    if (form.ctas.length >= MAX_CTAS) {
        return;
    }

    form.ctas.push({ label: '', url: '', style: 'primary' });
}

function removeCta(ctaIndex: number): void {
    form.ctas.splice(ctaIndex, 1);
}

function moveCta(ctaIndex: number, offset: -1 | 1): void {
    const target = ctaIndex + offset;

    if (target < 0 || target >= form.ctas.length) {
        return;
    }

    const [cta] = form.ctas.splice(ctaIndex, 1);
    form.ctas.splice(target, 0, cta);
}

/* ------------------------------------------------------------------ */
/* Publication                                                         */
/* ------------------------------------------------------------------ */

const publishing = ref(false);

/* La route publish est une bascule : même appel pour publier et dépublier. */
function onTogglePublish(): void {
    if (existing.value === null || publishing.value) {
        return;
    }

    publishing.value = true;
    router.patch(
        publish({ landingPage: existing.value.id }).url,
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
        void form.patch(update({ landingPage: existing.value.id }).url);
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
            :title="
                isEdit ? 'Modifier la landing page' : 'Nouvelle landing page'
            "
        />

        <section class="flex flex-col gap-6">
            <header class="flex flex-col gap-1">
                <p class="text-sm text-[var(--color-muted)]">
                    <a :href="index.url()" class="back-link">Pages landing</a>
                    <span class="mx-1">/</span>
                    <span>{{
                        isEdit ? existing?.title : 'Nouvelle page'
                    }}</span>
                </p>
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    {{
                        isEdit
                            ? 'Modifier la landing page'
                            : 'Nouvelle landing page'
                    }}
                </h1>
            </header>

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <!-- Contenu -->
                <BackCard
                    title="Contenu"
                    description="Titre interne, adresse publique et bloc héros."
                >
                    <div class="flex flex-col gap-5">
                        <BackField
                            label="Titre"
                            field-id="title"
                            :error="form.errors.title"
                            help="Nom interne de la page, visible uniquement en back-office."
                        >
                            <BackInput
                                id="title"
                                name="title"
                                v-model="form.title"
                                required
                                placeholder="Ex. Passerelle de services"
                                @input="onTitleInput"
                            />
                        </BackField>

                        <BackField
                            label="Slug"
                            field-id="slug"
                            :error="form.errors.slug"
                            help="Généré depuis le titre, modifiable. L'adresse publique sera /votre-slug."
                        >
                            <BackInput
                                id="slug"
                                name="slug"
                                v-model="form.slug"
                                required
                                placeholder="passerelle-de-services"
                                @input="onSlugInput"
                            />
                        </BackField>

                        <BackField
                            label="Titre du héros"
                            field-id="hero_title"
                            :error="form.errors.hero_title"
                            help="Grand titre affiché en haut de la page vitrine."
                        >
                            <BackInput
                                id="hero_title"
                                name="hero_title"
                                v-model="form.hero_title"
                                required
                                placeholder="Une passerelle. Tous vos services."
                            />
                        </BackField>

                        <BackField
                            label="Sous-titre du héros"
                            field-id="hero_subtitle"
                            :error="form.errors.hero_subtitle"
                            help="Phrase d'accroche affichée sous le titre."
                        >
                            <textarea
                                id="hero_subtitle"
                                name="hero_subtitle"
                                v-model="form.hero_subtitle"
                                required
                                rows="3"
                                class="back-input"
                                placeholder="Décrivez votre plateforme en une ou deux phrases."
                            ></textarea>
                        </BackField>
                    </div>
                </BackCard>

                <!-- CTA -->
                <BackCard
                    title="Boutons d'appel à l'action"
                    description="Boutons affichés sous le héros, dans l'ordre de la liste."
                >
                    <div class="flex flex-col gap-4">
                        <p
                            v-if="form.ctas.length === 0"
                            class="text-sm text-[var(--color-muted)]"
                        >
                            Aucun bouton pour le moment.
                        </p>

                        <div
                            v-for="(_, ctaIndex) in form.ctas"
                            :key="ctaIndex"
                            class="rounded-lg border border-[rgba(23,23,28,0.09)] p-4"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                                >
                                    Bouton {{ ctaIndex + 1 }}
                                </p>
                                <div class="flex items-center gap-1">
                                    <BackButton
                                        variant="secondary"
                                        :disabled="ctaIndex === 0"
                                        :aria-label="`Monter le bouton ${ctaIndex + 1}`"
                                        @click="moveCta(ctaIndex, -1)"
                                    >
                                        ↑
                                    </BackButton>
                                    <BackButton
                                        variant="secondary"
                                        :disabled="
                                            ctaIndex === form.ctas.length - 1
                                        "
                                        :aria-label="`Descendre le bouton ${ctaIndex + 1}`"
                                        @click="moveCta(ctaIndex, 1)"
                                    >
                                        ↓
                                    </BackButton>
                                    <BackButton
                                        variant="danger"
                                        :aria-label="`Supprimer le bouton ${ctaIndex + 1}`"
                                        @click="removeCta(ctaIndex)"
                                    >
                                        Supprimer
                                    </BackButton>
                                </div>
                            </div>

                            <div
                                class="mt-3 grid gap-4 sm:grid-cols-[1fr_1fr_auto]"
                            >
                                <div class="flex flex-col gap-1.5">
                                    <label
                                        class="back-label"
                                        :for="`cta-${ctaIndex}-label`"
                                    >
                                        Libellé
                                    </label>
                                    <BackInput
                                        :id="`cta-${ctaIndex}-label`"
                                        :name="`ctas[${ctaIndex}][label]`"
                                        v-model="form.ctas[ctaIndex].label"
                                        required
                                    />
                                    <p
                                        v-if="
                                            form.errors[
                                                `ctas.${ctaIndex}.label`
                                            ]
                                        "
                                        class="back-error"
                                    >
                                        {{
                                            form.errors[
                                                `ctas.${ctaIndex}.label`
                                            ]
                                        }}
                                    </p>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label
                                        class="back-label"
                                        :for="`cta-${ctaIndex}-url`"
                                    >
                                        Lien
                                    </label>
                                    <BackInput
                                        :id="`cta-${ctaIndex}-url`"
                                        :name="`ctas[${ctaIndex}][url]`"
                                        v-model="form.ctas[ctaIndex].url"
                                        required
                                        placeholder="Lien absolu ou /chemin interne"
                                    />
                                    <p
                                        v-if="
                                            form.errors[`ctas.${ctaIndex}.url`]
                                        "
                                        class="back-error"
                                    >
                                        {{
                                            form.errors[`ctas.${ctaIndex}.url`]
                                        }}
                                    </p>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label
                                        class="back-label"
                                        :for="`cta-${ctaIndex}-style`"
                                    >
                                        Style
                                    </label>
                                    <select
                                        :id="`cta-${ctaIndex}-style`"
                                        :name="`ctas[${ctaIndex}][style]`"
                                        v-model="form.ctas[ctaIndex].style"
                                        class="back-input"
                                    >
                                        <option value="primary">
                                            Principal
                                        </option>
                                        <option value="secondary">
                                            Secondaire
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            form.errors[
                                                `ctas.${ctaIndex}.style`
                                            ]
                                        "
                                        class="back-error"
                                    >
                                        {{
                                            form.errors[
                                                `ctas.${ctaIndex}.style`
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
                                    :disabled="hasMaxCtas"
                                    @click="addCta"
                                >
                                    Ajouter un bouton
                                </BackButton>
                            </div>
                            <p v-if="hasMaxCtas" class="back-help">
                                Maximum {{ MAX_CTAS }} boutons atteint.
                            </p>
                            <p v-if="form.errors.ctas" class="back-error">
                                {{ form.errors.ctas }}
                            </p>
                        </div>
                    </div>
                </BackCard>

                <!-- SEO -->
                <BackCard
                    title="Référencement"
                    description="Titre et description utilisés par les moteurs de recherche."
                >
                    <div class="flex flex-col gap-5">
                        <BackField
                            label="Titre SEO"
                            field-id="seo-title"
                            :error="form.errors['seo.title']"
                            :help="`${form.seo.title.length}/60 caractères recommandés`"
                        >
                            <BackInput
                                id="seo-title"
                                name="seo[title]"
                                v-model="form.seo.title"
                                :maxlength="120"
                            />
                        </BackField>

                        <BackField
                            label="Description SEO"
                            field-id="seo-description"
                            :error="form.errors['seo.description']"
                            :help="`${form.seo.description.length}/160 caractères recommandés`"
                        >
                            <textarea
                                id="seo-description"
                                name="seo[description]"
                                v-model="form.seo.description"
                                rows="3"
                                :maxlength="320"
                                class="back-input"
                            ></textarea>
                        </BackField>
                    </div>
                </BackCard>

                <!-- Accueil + publication -->
                <BackCard
                    title="Accueil et publication"
                    description="Emplacement de la page dans le site et état de diffusion."
                >
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-3">
                            <label
                                class="flex items-center gap-2.5 text-sm text-[var(--color-ink)]"
                            >
                                <input
                                    id="is_home"
                                    name="is_home"
                                    type="checkbox"
                                    v-model="form.is_home"
                                    class="back-checkbox"
                                />
                                <span>Définir comme page d'accueil</span>
                            </label>

                            <BackStatus
                                v-if="form.is_home && !isEdit"
                                tone="warning"
                            >
                                Cette page deviendra l'accueil du site : elle
                                sera servie sur la racine « / » à la place de la
                                page actuellement définie.
                            </BackStatus>
                            <BackStatus v-else-if="form.is_home" tone="warning">
                                Cette page deviendra l'accueil du site : la page
                                actuellement définie perdra ce statut.
                            </BackStatus>
                        </div>

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
                                    v-if="isPublished"
                                    variant="secondary"
                                    :loading="publishing"
                                    @click="onTogglePublish"
                                >
                                    Dépublier
                                </BackButton>
                                <BackButton
                                    v-else
                                    variant="secondary"
                                    :loading="publishing"
                                    @click="onTogglePublish"
                                >
                                    Publier
                                </BackButton>
                            </template>
                            <BackStatus v-else tone="info">
                                La page pourra être publiée après
                                enregistrement.
                            </BackStatus>
                        </div>
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
                                : 'Créer la page'
                        }}
                    </BackButton>
                </div>
            </form>
        </section>
    </BackLayout>
</template>
