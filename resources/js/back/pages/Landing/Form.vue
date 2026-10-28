<script setup lang="ts">
// Formulaire de creation / edition d'une landing page : contenu (titre,
// slug, heros), repeateur de CTA, SEO, accueil et publication. Carte unique
// shadcn dont les sections sont separees par des separators.
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { BackLandingFormPageProps, Cta } from '@/types';
import { index, publish, store, update } from '@/routes/admin/landing';
import { ArrowDownIcon, ArrowUpIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';

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
            <BackPageHeader
                :title="
                    isEdit
                        ? 'Modifier la landing page'
                        : 'Nouvelle landing page'
                "
            />

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <Card>
                    <!-- Contenu -->
                    <CardHeader>
                        <CardTitle>Contenu</CardTitle>
                        <CardDescription>
                            Titre interne, adresse publique et bloc héros.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-5">
                        <Field>
                            <FieldLabel for="title">Titre</FieldLabel>
                            <Input
                                id="title"
                                name="title"
                                v-model="form.title"
                                required
                                placeholder="Ex. Passerelle de services"
                                @input="onTitleInput"
                            />
                            <FieldDescription>
                                Nom interne de la page, visible uniquement en
                                back-office.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.title]" />
                        </Field>

                        <Field>
                            <FieldLabel for="slug">Slug</FieldLabel>
                            <Input
                                id="slug"
                                name="slug"
                                v-model="form.slug"
                                required
                                class="font-mono"
                                placeholder="passerelle-de-services"
                                @input="onSlugInput"
                            />
                            <FieldDescription>
                                Généré depuis le titre, modifiable. L'adresse
                                publique sera /votre-slug.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.slug]" />
                        </Field>

                        <Field>
                            <FieldLabel for="hero_title">
                                Titre du héros
                            </FieldLabel>
                            <Input
                                id="hero_title"
                                name="hero_title"
                                v-model="form.hero_title"
                                required
                                placeholder="Une passerelle. Tous vos services."
                            />
                            <FieldDescription>
                                Grand titre affiché en haut de la page vitrine.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.hero_title]" />
                        </Field>

                        <Field>
                            <FieldLabel for="hero_subtitle">
                                Sous-titre du héros
                            </FieldLabel>
                            <Textarea
                                id="hero_subtitle"
                                name="hero_subtitle"
                                v-model="form.hero_subtitle"
                                required
                                :rows="3"
                                placeholder="Décrivez votre plateforme en une ou deux phrases."
                            />
                            <FieldDescription>
                                Phrase d'accroche affichée sous le titre.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.hero_subtitle]" />
                        </Field>
                    </CardContent>

                    <Separator />

                    <!-- CTA -->
                    <CardContent class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                Boutons d'appel à l'action
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Boutons affichés sous le héros, dans l'ordre de
                                la liste.
                            </p>
                        </div>

                        <p
                            v-if="form.ctas.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Aucun bouton pour le moment.
                        </p>

                        <Card
                            v-for="(_, ctaIndex) in form.ctas"
                            :key="ctaIndex"
                            class="gap-4 rounded-md p-4"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Bouton {{ ctaIndex + 1 }}
                                </p>
                                <div class="flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        :disabled="ctaIndex === 0"
                                        :aria-label="`Monter le bouton ${ctaIndex + 1}`"
                                        @click="moveCta(ctaIndex, -1)"
                                    >
                                        <ArrowUpIcon />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        :disabled="
                                            ctaIndex === form.ctas.length - 1
                                        "
                                        :aria-label="`Descendre le bouton ${ctaIndex + 1}`"
                                        @click="moveCta(ctaIndex, 1)"
                                    >
                                        <ArrowDownIcon />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        :aria-label="`Supprimer le bouton ${ctaIndex + 1}`"
                                        @click="removeCta(ctaIndex)"
                                    >
                                        <Trash2Icon />
                                    </Button>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-[1fr_1fr_auto]">
                                <Field>
                                    <FieldLabel :for="`cta-${ctaIndex}-label`">
                                        Libellé
                                    </FieldLabel>
                                    <Input
                                        :id="`cta-${ctaIndex}-label`"
                                        :name="`ctas[${ctaIndex}][label]`"
                                        v-model="form.ctas[ctaIndex].label"
                                        required
                                    />
                                    <FieldError
                                        :errors="[
                                            form.errors[
                                                `ctas.${ctaIndex}.label`
                                            ],
                                        ]"
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel :for="`cta-${ctaIndex}-url`">
                                        Lien
                                    </FieldLabel>
                                    <Input
                                        :id="`cta-${ctaIndex}-url`"
                                        :name="`ctas[${ctaIndex}][url]`"
                                        v-model="form.ctas[ctaIndex].url"
                                        required
                                        placeholder="Lien absolu ou /chemin interne"
                                    />
                                    <FieldError
                                        :errors="[
                                            form.errors[`ctas.${ctaIndex}.url`],
                                        ]"
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel :for="`cta-${ctaIndex}-style`">
                                        Style
                                    </FieldLabel>
                                    <Select v-model="form.ctas[ctaIndex].style">
                                        <SelectTrigger
                                            :id="`cta-${ctaIndex}-style`"
                                            class="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="primary">
                                                Principal
                                            </SelectItem>
                                            <SelectItem value="secondary">
                                                Secondaire
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <FieldError
                                        :errors="[
                                            form.errors[
                                                `ctas.${ctaIndex}.style`
                                            ],
                                        ]"
                                    />
                                </Field>
                            </div>
                        </Card>

                        <div class="flex flex-col gap-2">
                            <div>
                                <Button
                                    variant="outline"
                                    :disabled="hasMaxCtas"
                                    @click="addCta"
                                >
                                    <PlusIcon />
                                    Ajouter un bouton
                                </Button>
                            </div>
                            <p
                                v-if="hasMaxCtas"
                                class="text-sm text-muted-foreground"
                            >
                                Maximum {{ MAX_CTAS }} boutons atteint.
                            </p>
                            <FieldError :errors="[form.errors.ctas]" />
                        </div>
                    </CardContent>

                    <Separator />

                    <!-- SEO -->
                    <CardContent class="flex flex-col gap-5">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                Référencement
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Titre et description utilisés par les moteurs de
                                recherche.
                            </p>
                        </div>

                        <Field>
                            <FieldLabel for="seo-title">Titre SEO</FieldLabel>
                            <Input
                                id="seo-title"
                                name="seo[title]"
                                v-model="form.seo.title"
                                :maxlength="120"
                            />
                            <FieldDescription>
                                {{ form.seo.title.length }}/60 caractères
                                recommandés
                            </FieldDescription>
                            <FieldError :errors="[form.errors['seo.title']]" />
                        </Field>

                        <Field>
                            <FieldLabel for="seo-description">
                                Description SEO
                            </FieldLabel>
                            <Textarea
                                id="seo-description"
                                name="seo[description]"
                                v-model="form.seo.description"
                                :rows="3"
                                :maxlength="320"
                            />
                            <FieldDescription>
                                {{ form.seo.description.length }}/160 caractères
                                recommandés
                            </FieldDescription>
                            <FieldError
                                :errors="[form.errors['seo.description']]"
                            />
                        </Field>
                    </CardContent>

                    <Separator />

                    <!-- Accueil + publication -->
                    <CardContent class="flex flex-col gap-5">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                Accueil et publication
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Emplacement de la page dans le site et état de
                                diffusion.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3">
                            <Field orientation="horizontal">
                                <Switch id="is_home" v-model="form.is_home" />
                                <FieldContent>
                                    <FieldLabel for="is_home">
                                        Définir comme page d'accueil
                                    </FieldLabel>
                                </FieldContent>
                            </Field>

                            <Alert
                                v-if="form.is_home && !isEdit"
                                variant="warning"
                            >
                                <AlertDescription>
                                    Cette page deviendra l'accueil du site :
                                    elle sera servie sur la racine « / » à la
                                    place de la page actuellement définie.
                                </AlertDescription>
                            </Alert>
                            <Alert v-else-if="form.is_home" variant="warning">
                                <AlertDescription>
                                    Cette page deviendra l'accueil du site : la
                                    page actuellement définie perdra ce statut.
                                </AlertDescription>
                            </Alert>
                        </div>

                        <div
                            class="flex flex-wrap items-center gap-4 border-t pt-5"
                        >
                            <template v-if="isEdit">
                                <Badge
                                    :variant="
                                        isPublished ? 'success' : 'secondary'
                                    "
                                >
                                    {{ isPublished ? 'Publiée' : 'Brouillon' }}
                                </Badge>
                                <span
                                    v-if="isPublished"
                                    class="text-sm text-muted-foreground"
                                >
                                    Depuis le {{ publishedAtLabel }}
                                </span>

                                <Button
                                    v-if="isPublished"
                                    variant="outline"
                                    :disabled="publishing"
                                    @click="onTogglePublish"
                                >
                                    <Spinner v-if="publishing" class="size-4" />
                                    Dépublier
                                </Button>
                                <Button
                                    v-else
                                    variant="outline"
                                    :disabled="publishing"
                                    @click="onTogglePublish"
                                >
                                    <Spinner v-if="publishing" class="size-4" />
                                    Publier
                                </Button>
                            </template>
                            <Alert v-else class="sm:max-w-sm">
                                <AlertDescription>
                                    La page pourra être publiée après
                                    enregistrement.
                                </AlertDescription>
                            </Alert>
                        </div>
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
                                    : 'Créer la page'
                            }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </section>
    </BackLayout>
</template>
