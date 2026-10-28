<script setup lang="ts">
// Gestion des modules : table shadcn avec bascule d'activation inline, un
// seul aller-retour a la fois (pendingSlug), gate manage-modules cote
// serveur.
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { BackModulesPageProps, Module } from '@/types';
import { toggle } from '@/routes/admin/modules';
import BackLayout from '../layouts/BackLayout.vue';
import BackPageHeader from '../components/BackPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
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

defineProps<BackModulesPageProps>();

const page = usePage<BackModulesPageProps>();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

/* Un seul bascule a la fois : les autres interrupteurs sont neutralises
   pendant l'aller-retour Inertia (le serveur renvoie l'index actualise). */
const pendingSlug = ref<string | null>(null);

function onToggle(module: Module): void {
    if (pendingSlug.value !== null) {
        return;
    }

    pendingSlug.value = module.slug;
    router.visit(toggle({ slug: module.slug }).url, {
        method: 'patch',
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            pendingSlug.value = null;
        },
    });
}
</script>

<template>
    <BackLayout>
        <Head title="Modules" />

        <section class="flex flex-col gap-6">
            <BackPageHeader
                title="Modules"
                description="Activez ou désactivez chaque domaine de la plateforme. Un module désactivé rend immédiatement inaccessibles ses écrans et ses routes."
            />

            <Card>
                <CardContent>
                    <Table>
                        <TableCaption class="sr-only">
                            Modules de la plateforme et état d'activation
                        </TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Module</TableHead>
                                <TableHead>État</TableHead>
                                <TableHead class="text-right">
                                    Activation
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="module in modules"
                                :key="module.slug"
                            >
                                <TableCell class="py-4">
                                    <p class="font-medium">
                                        {{ module.label }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-sm text-muted-foreground"
                                    >
                                        {{ module.description }}
                                    </p>
                                </TableCell>
                                <TableCell class="py-4">
                                    <Badge
                                        :variant="
                                            module.enabled
                                                ? 'success'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            module.enabled ? 'Actif' : 'Inactif'
                                        }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Spinner
                                            v-if="pendingSlug === module.slug"
                                            class="size-4 text-muted-foreground"
                                        />
                                        <Switch
                                            :checked="module.enabled"
                                            :disabled="
                                                !isAdmin ||
                                                (pendingSlug !== null &&
                                                    pendingSlug !== module.slug)
                                            "
                                            :aria-label="`${module.enabled ? 'Désactiver' : 'Activer'} le module ${module.label}`"
                                            @update:checked="onToggle(module)"
                                        />
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </section>
    </BackLayout>
</template>
