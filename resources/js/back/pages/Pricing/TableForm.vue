<script setup lang="ts">
// Formulaire de creation / edition d'une table de prix : contenu (titre,
// slug, sous-titre), selection et ordre des plans (le pivot porte le tri),
// repeateur de FAQ et publication. Carte unique shadcn par sections.
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { BackPricingTableFormPageProps, FaqItem } from '@/types';
import { index, publish, store, update } from '@/routes/admin/pricing/tables';
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
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';

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
            <BackPageHeader
                :title="
                    isEdit
                        ? 'Modifier la table de prix'
                        : 'Nouvelle table de prix'
                "
            />

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <Card>
                    <!-- Contenu -->
                    <CardHeader>
                        <CardTitle>Contenu</CardTitle>
                        <CardDescription>
                            Titre et sous-titre affichés en haut de la page
                            tarifs.
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
                                placeholder="Ex. Un tarif clair, qui grandit avec vous."
                                @input="onTitleInput"
                            />
                            <FieldDescription>
                                Grand titre de la page tarifs.
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
                                placeholder="tarifs"
                                @input="onSlugInput"
                            />
                            <FieldDescription>
                                Généré depuis le titre, modifiable.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.slug]" />
                        </Field>

                        <Field>
                            <FieldLabel for="subtitle">Sous-titre</FieldLabel>
                            <Textarea
                                id="subtitle"
                                name="subtitle"
                                v-model="form.subtitle"
                                :rows="3"
                                required
                                placeholder="Landing pages et tables de prix incluses partout."
                            />
                            <FieldDescription>
                                Phrase d'accroche affichée sous le titre.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.subtitle]" />
                        </Field>
                    </CardContent>

                    <Separator />

                    <!-- Plans -->
                    <CardContent class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                Plans
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Cochez les plans à afficher et ordonnez-les :
                                l'ordre choisi ici est celui de la page tarifs.
                            </p>
                        </div>

                        <p
                            v-if="props.plans.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Aucun plan disponible : créez d'abord des plans
                            tarifaires.
                        </p>

                        <p
                            v-else-if="form.plan_ids.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Aucun plan sélectionné.
                        </p>

                        <ul class="flex flex-col gap-2">
                            <li
                                v-for="plan in props.plans"
                                :key="plan.id"
                                class="flex items-center gap-3 rounded-md border px-4 py-3"
                            >
                                <label
                                    class="flex flex-1 items-center gap-2.5 text-sm"
                                    :for="`plan-${plan.id}`"
                                >
                                    <Checkbox
                                        :id="`plan-${plan.id}`"
                                        :model-value="isSelected(plan.id)"
                                        @update:model-value="
                                            togglePlan(plan.id)
                                        "
                                    />
                                    <span class="font-medium">
                                        {{ plan.name }}
                                    </span>
                                    <Badge
                                        v-if="!plan.active"
                                        variant="secondary"
                                    >
                                        Inactif
                                    </Badge>
                                </label>

                                <template v-if="planPosition(plan.id) !== null">
                                    <span
                                        class="text-xs text-muted-foreground tabular-nums"
                                    >
                                        N°{{ planPosition(plan.id) }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            :disabled="
                                                planPosition(plan.id) === 1
                                            "
                                            :aria-label="`Monter le plan ${plan.name}`"
                                            @click="movePlan(plan.id, -1)"
                                        >
                                            <ArrowUpIcon />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            :disabled="
                                                planPosition(plan.id) ===
                                                form.plan_ids.length
                                            "
                                            :aria-label="`Descendre le plan ${plan.name}`"
                                            @click="movePlan(plan.id, 1)"
                                        >
                                            <ArrowDownIcon />
                                        </Button>
                                    </div>
                                </template>
                            </li>
                        </ul>

                        <FieldError :errors="[form.errors.plan_ids]" />
                    </CardContent>

                    <Separator />

                    <!-- FAQ -->
                    <CardContent class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                FAQ
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Questions fréquentes affichées en accordéon sous
                                les plans, dans l'ordre de la liste.
                            </p>
                        </div>

                        <p
                            v-if="form.faq.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Aucune question pour le moment.
                        </p>

                        <Card
                            v-for="(_, faqIndex) in form.faq"
                            :key="faqIndex"
                            class="gap-4 rounded-md p-4"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Question {{ faqIndex + 1 }}
                                </p>
                                <div class="flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        :disabled="faqIndex === 0"
                                        :aria-label="`Monter la question ${faqIndex + 1}`"
                                        @click="moveQuestion(faqIndex, -1)"
                                    >
                                        <ArrowUpIcon />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        :disabled="
                                            faqIndex === form.faq.length - 1
                                        "
                                        :aria-label="`Descendre la question ${faqIndex + 1}`"
                                        @click="moveQuestion(faqIndex, 1)"
                                    >
                                        <ArrowDownIcon />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        :aria-label="`Supprimer la question ${faqIndex + 1}`"
                                        @click="removeQuestion(faqIndex)"
                                    >
                                        <Trash2Icon />
                                    </Button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4">
                                <Field>
                                    <FieldLabel
                                        :for="`faq-${faqIndex}-question`"
                                    >
                                        Question
                                    </FieldLabel>
                                    <Input
                                        :id="`faq-${faqIndex}-question`"
                                        :name="`faq[${faqIndex}][question]`"
                                        v-model="form.faq[faqIndex].question"
                                        required
                                    />
                                    <FieldError
                                        :errors="[
                                            form.errors[
                                                `faq.${faqIndex}.question`
                                            ],
                                        ]"
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel :for="`faq-${faqIndex}-answer`">
                                        Réponse
                                    </FieldLabel>
                                    <Textarea
                                        :id="`faq-${faqIndex}-answer`"
                                        :name="`faq[${faqIndex}][answer]`"
                                        v-model="form.faq[faqIndex].answer"
                                        :rows="3"
                                        required
                                    />
                                    <FieldError
                                        :errors="[
                                            form.errors[
                                                `faq.${faqIndex}.answer`
                                            ],
                                        ]"
                                    />
                                </Field>
                            </div>
                        </Card>

                        <div class="flex flex-col gap-2">
                            <div>
                                <Button variant="outline" @click="addQuestion">
                                    <PlusIcon />
                                    Ajouter une question
                                </Button>
                            </div>
                            <FieldError :errors="[form.errors.faq]" />
                        </div>
                    </CardContent>

                    <Separator />

                    <!-- Publication -->
                    <CardContent class="flex flex-col gap-5">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                Publication
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                État de diffusion de la table sur la page
                                tarifs.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-4">
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
                                    variant="outline"
                                    :disabled="publishing"
                                    @click="onTogglePublish"
                                >
                                    <Spinner v-if="publishing" class="size-4" />
                                    {{ isPublished ? 'Dépublier' : 'Publier' }}
                                </Button>
                            </template>
                            <Alert v-else class="sm:max-w-sm">
                                <AlertDescription>
                                    La table pourra être publiée après
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
                                    : 'Créer la table'
                            }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </section>
    </BackLayout>
</template>
