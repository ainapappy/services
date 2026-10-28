<script setup lang="ts">
// Liste des landing pages : table shadcn (titre, slug, statut, accueil, CTA)
// avec menu d'actions par ligne : edition et suppression confirmee.
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { BackLandingIndexPageProps } from '@/types';
import { create, destroy, edit, publish } from '@/routes/admin/landing';
import {
    EllipsisVerticalIcon,
    LayoutTemplateIcon,
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

defineProps<BackLandingIndexPageProps>();

/* Suppression : confirmee par AlertDialog, puis retour a l'index actualise. */
const deletingId = ref<number | null>(null);

/* Publication depuis la liste : un seul aller-retour a la fois, le
   endpoint publish bascule publie <-> brouillon. */
const publishingId = ref<number | null>(null);

function onTogglePublish(id: number): void {
    if (publishingId.value !== null) {
        return;
    }

    publishingId.value = id;
    router.patch(
        publish({ landingPage: id }).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                publishingId.value = null;
            },
        },
    );
}

function onDestroy(id: number): void {
    if (deletingId.value !== null) {
        return;
    }

    deletingId.value = id;
    router.delete(destroy({ landingPage: id }).url, {
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
        <Head title="Landing" />

        <section class="flex flex-col gap-6">
            <BackPageHeader
                title="Pages landing"
                description="Créez et publiez les pages vitrine : héros, boutons d'appel à l'action et référencement."
            >
                <template #actions>
                    <Button as-child>
                        <Link :href="create.url()">
                            <PlusIcon />
                            Nouvelle page
                        </Link>
                    </Button>
                </template>
            </BackPageHeader>

            <!-- Etat vide -->
            <Empty v-if="landingPages.length === 0" class="rounded-xl border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <LayoutTemplateIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucune page</EmptyTitle>
                    <EmptyDescription>
                        Créez votre première landing page : elle sera rendue
                        côté vitrine dès sa publication.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child>
                        <Link :href="create.url()">
                            <PlusIcon />
                            Nouvelle page
                        </Link>
                    </Button>
                </EmptyContent>
            </Empty>

            <!-- Tableau -->
            <Card v-else class="overflow-hidden py-0">
                <Table>
                    <TableCaption class="sr-only">
                        Landing pages : titre, statut de publication et actions
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="h-12 px-4">Page</TableHead>
                            <TableHead class="h-12 px-4">Statut</TableHead>
                            <TableHead class="h-12 px-4">CTA</TableHead>
                            <TableHead class="h-12 px-4">Publication</TableHead>
                            <TableHead class="h-12 px-4 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="landingPage in landingPages"
                            :key="landingPage.id"
                        >
                            <TableCell class="px-4 py-3">
                                <p class="flex items-center gap-2 font-medium">
                                    {{ landingPage.title }}
                                    <Badge v-if="landingPage.isHome">
                                        Accueil
                                    </Badge>
                                </p>
                                <p
                                    class="mt-0.5 font-mono text-sm text-muted-foreground"
                                >
                                    /{{ landingPage.slug }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                <Badge
                                    :variant="
                                        landingPage.publishedAt !== null
                                            ? 'success'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        landingPage.publishedAt !== null
                                            ? 'Publiée'
                                            : 'Brouillon'
                                    }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-4 py-3 tabular-nums">
                                {{ landingPage.ctasCount }}
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <Switch
                                        :model-value="
                                            landingPage.publishedAt !== null
                                        "
                                        :disabled="publishingId !== null"
                                        :aria-label="`Publier ou dépublier la page ${landingPage.title}`"
                                        @update:model-value="
                                            onTogglePublish(landingPage.id)
                                        "
                                    />
                                    <Spinner
                                        v-if="publishingId === landingPage.id"
                                        class="size-4"
                                    />
                                    <span class="text-sm text-muted-foreground">
                                        {{
                                            formatPublishedAt(
                                                landingPage.publishedAt,
                                            )
                                        }}
                                    </span>
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
                                                    :aria-label="`Actions pour ${landingPage.title}`"
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
                                                                landingPage:
                                                                    landingPage.id,
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
                                                    Supprimer la page ?
                                                </AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Supprimer la page «
                                                    {{ landingPage.title }} » ?
                                                    Cette action est
                                                    irréversible.
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
                                                            onDestroy(
                                                                landingPage.id,
                                                            )
                                                        "
                                                    >
                                                        <Spinner
                                                            v-if="
                                                                deletingId ===
                                                                landingPage.id
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
