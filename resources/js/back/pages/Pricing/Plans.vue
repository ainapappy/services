<script setup lang="ts">
// Liste des plans tarifaires : table triée par `sort` avec prix (centimes
// affiches en euros), badges populaire / actif, bascule d'activation inline
// et menu d'actions par ligne (edition, suppression confirmee).
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { BackPricingPlansPageProps, PricingPlan } from '@/types';
import { create, destroy, edit, toggle } from '@/routes/admin/pricing/plans';
import {
    CreditCardIcon,
    EllipsisVerticalIcon,
    PencilIcon,
    PlusIcon,
    Trash2Icon,
} from '@lucide/vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';

const props = defineProps<BackPricingPlansPageProps>();

/* Affichage ordonne par `sort` (tri stable : les ex aequo gardent l'ordre
   recu du backend). */
const sortedPlans = computed(() =>
    [...props.plans].sort((a, b) => a.sort - b.sort),
);

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

/* ------------------------------------------------------------------ */
/* Activation                                                          */
/* ------------------------------------------------------------------ */

/* Une seule bascule a la fois : les autres interrupteurs sont neutralises
   pendant l'aller-retour Inertia (le serveur renvoie l'index actualise). */
const pendingId = ref<number | null>(null);

function onToggleActive(plan: PricingPlan): void {
    if (pendingId.value !== null) {
        return;
    }

    pendingId.value = plan.id;
    router.patch(
        toggle({ plan: plan.id }).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                pendingId.value = null;
            },
        },
    );
}

/* ------------------------------------------------------------------ */
/* Suppression                                                         */
/* ------------------------------------------------------------------ */

/* Confirmee par AlertDialog, puis retour a l'index actualise. */
const deletingId = ref<number | null>(null);

function onDestroy(plan: PricingPlan): void {
    if (deletingId.value !== null) {
        return;
    }

    deletingId.value = plan.id;
    router.delete(destroy({ plan: plan.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <BackLayout>
        <Head title="Plans tarifaires" />

        <section class="flex flex-col gap-6">
            <BackPageHeader
                title="Plans tarifaires"
                description="Définissez les offres affichées sur la page tarifs : prix mensuel et annuel en centimes, inclus et mise en avant."
            >
                <template #actions>
                    <Button as-child>
                        <Link :href="create.url()">
                            <PlusIcon />
                            Nouveau plan
                        </Link>
                    </Button>
                </template>
            </BackPageHeader>

            <!-- Etat vide -->
            <Empty v-if="sortedPlans.length === 0" class="rounded-xl border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <CreditCardIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucun plan</EmptyTitle>
                    <EmptyDescription>
                        Créez votre premier plan tarifaire : il pourra ensuite
                        être associé à une table de prix publiée côté vitrine.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child>
                        <Link :href="create.url()">
                            <PlusIcon />
                            Nouveau plan
                        </Link>
                    </Button>
                </EmptyContent>
            </Empty>

            <!-- Tableau -->
            <Card v-else class="overflow-hidden py-0">
                <Table>
                    <TableCaption class="sr-only">
                        Plans tarifaires : prix, ordre d'affichage, statut et
                        actions
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="h-12 px-4">Plan</TableHead>
                            <TableHead class="h-12 px-4">Prix</TableHead>
                            <TableHead class="h-12 px-4">Ordre</TableHead>
                            <TableHead class="h-12 px-4">État</TableHead>
                            <TableHead class="h-12 px-4 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="plan in sortedPlans" :key="plan.id">
                            <TableCell class="px-4 py-3">
                                <p class="flex items-center gap-2 font-medium">
                                    {{ plan.name }}
                                    <Badge v-if="plan.popular">
                                        Populaire
                                    </Badge>
                                </p>
                                <p
                                    class="mt-0.5 font-mono text-sm text-muted-foreground"
                                >
                                    /{{ plan.slug }}
                                </p>
                                <p
                                    v-if="plan.tagline"
                                    class="mt-0.5 text-sm text-muted-foreground"
                                >
                                    {{ plan.tagline }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                <p>
                                    {{ formatEuros(plan.priceMonthly) }} / mois
                                </p>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    {{ formatEuros(plan.priceYearly) }} / mois
                                    en annuel
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-3 tabular-nums">
                                {{ plan.sort }}
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <Badge
                                        :variant="
                                            plan.active
                                                ? 'success'
                                                : 'secondary'
                                        "
                                    >
                                        {{ plan.active ? 'Actif' : 'Inactif' }}
                                    </Badge>
                                    <Switch
                                        :model-value="plan.active"
                                        :aria-label="`${plan.active ? 'Désactiver' : 'Activer'} le plan ${plan.name}`"
                                        :disabled="
                                            pendingId !== null &&
                                            pendingId !== plan.id
                                        "
                                        @update:model-value="
                                            onToggleActive(plan)
                                        "
                                    />
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-3 text-right">
                                <div class="flex justify-end">
                                    <AlertDialog>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    :aria-label="`Actions pour le plan ${plan.name}`"
                                                >
                                                    <EllipsisVerticalIcon />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent
                                                align="end"
                                                class="w-40"
                                            >
                                                <DropdownMenuItem as-child>
                                                    <Link
                                                        :href="
                                                            edit({
                                                                plan: plan.id,
                                                            }).url
                                                        "
                                                    >
                                                        <PencilIcon />
                                                        Éditer
                                                    </Link>
                                                </DropdownMenuItem>
                                                <AlertDialogTrigger as-child>
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                    >
                                                        <Trash2Icon />
                                                        Supprimer
                                                    </DropdownMenuItem>
                                                </AlertDialogTrigger>
                                            </DropdownMenuContent>
                                        </DropdownMenu>

                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>
                                                    Supprimer le plan ?
                                                </AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Supprimer le plan «
                                                    {{ plan.name }} » ? Cette
                                                    action est irréversible.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>
                                                    Annuler
                                                </AlertDialogCancel>
                                                <AlertDialogAction as-child>
                                                    <Button
                                                        variant="destructive"
                                                        :disabled="
                                                            deletingId !== null
                                                        "
                                                        @click="onDestroy(plan)"
                                                    >
                                                        <Spinner
                                                            v-if="
                                                                deletingId ===
                                                                plan.id
                                                            "
                                                            class="size-4"
                                                        />
                                                        Supprimer
                                                    </Button>
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>
        </section>
    </BackLayout>
</template>
