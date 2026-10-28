<script setup lang="ts">
// Liste des tables de prix : table shadcn (titre, plans, FAQ, statut,
// publication) avec menu d'actions par ligne : edition et suppression
// confirmee.
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { BackPricingTablesPageProps, PricingTableListItem } from '@/types';
import { create, destroy, edit } from '@/routes/admin/pricing/tables';
import {
    EllipsisVerticalIcon,
    PencilIcon,
    PlusIcon,
    TableIcon,
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

defineProps<BackPricingTablesPageProps>();

/* Suppression : confirmee par AlertDialog, puis retour a l'index actualise. */
const deletingId = ref<number | null>(null);

function onDestroy(table: PricingTableListItem): void {
    if (deletingId.value !== null) {
        return;
    }

    deletingId.value = table.id;
    router.delete(destroy({ pricingTable: table.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

function formatPublishedAt(value: string | null): string {
    if (value === null) {
        return '—';
    }

    return new Date(value).toLocaleString('fr-FR', {
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <BackLayout>
        <Head title="Tables de prix" />

        <section class="flex flex-col gap-6">
            <BackPageHeader
                title="Tables de prix"
                description="Composez les tables publiées sur la page tarifs : plans associés, FAQ et publication."
            >
                <template #actions>
                    <Button as-child>
                        <Link :href="create.url()">
                            <PlusIcon />
                            Nouvelle table
                        </Link>
                    </Button>
                </template>
            </BackPageHeader>

            <!-- Etat vide -->
            <Empty v-if="tables.length === 0" class="rounded-xl border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <TableIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucune table</EmptyTitle>
                    <EmptyDescription>
                        Créez votre première table de prix : elle sera rendue
                        sur la page /pricing dès sa publication.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child>
                        <Link :href="create.url()">
                            <PlusIcon />
                            Nouvelle table
                        </Link>
                    </Button>
                </EmptyContent>
            </Empty>

            <!-- Tableau -->
            <Card v-else class="overflow-hidden py-0">
                <Table>
                    <TableCaption class="sr-only">
                        Tables de prix : contenu, statut de publication et
                        actions
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="h-12 px-4">Table</TableHead>
                            <TableHead class="h-12 px-4">Plans</TableHead>
                            <TableHead class="h-12 px-4">FAQ</TableHead>
                            <TableHead class="h-12 px-4">Statut</TableHead>
                            <TableHead class="h-12 px-4">Publication</TableHead>
                            <TableHead class="h-12 px-4 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="table in tables" :key="table.id">
                            <TableCell class="px-4 py-3">
                                <p class="font-medium">{{ table.title }}</p>
                                <p
                                    class="mt-0.5 font-mono text-sm text-muted-foreground"
                                >
                                    /{{ table.slug }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-3 tabular-nums">
                                {{ table.plansCount }}
                            </TableCell>
                            <TableCell class="px-4 py-3 tabular-nums">
                                {{ table.faqCount }}
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                <Badge
                                    :variant="
                                        table.publishedAt !== null
                                            ? 'success'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        table.publishedAt !== null
                                            ? 'Publiée'
                                            : 'Brouillon'
                                    }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                {{ formatPublishedAt(table.publishedAt) }}
                            </TableCell>
                            <TableCell class="px-4 py-3 text-right">
                                <div class="flex justify-end">
                                    <AlertDialog>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    :aria-label="`Actions pour la table ${table.title}`"
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
                                                                pricingTable:
                                                                    table.id,
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
                                                    Supprimer la table ?
                                                </AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Supprimer la table «
                                                    {{ table.title }} » ? Cette
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
                                                        @click="
                                                            onDestroy(table)
                                                        "
                                                    >
                                                        <Spinner
                                                            v-if="
                                                                deletingId ===
                                                                table.id
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
