<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { BackModulesPageProps, Module } from '@/types';
import { toggle } from '@/routes/admin/modules';
import BackLayout from '../layouts/BackLayout.vue';
import BackBadge from '../components/BackBadge.vue';
import BackToggle from '../components/BackToggle.vue';

defineProps<BackModulesPageProps>();

const page = usePage<BackModulesPageProps>();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

/* Un seul bascule à la fois : les autres interrupteurs sont neutralisés
   pendant l'aller-retour Inertia (le serveur renvoie l'index actualisé). */
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
            <header class="flex flex-col gap-1">
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    Modules
                </h1>
                <p class="text-sm text-[var(--color-muted)]">
                    Activez ou désactivez chaque domaine de la plateforme. Un
                    module désactivé rend immédiatement inaccessibles ses écrans
                    et ses routes.
                </p>
            </header>

            <div class="back-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Modules de la plateforme et état d'activation
                    </caption>
                    <thead>
                        <tr
                            class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            <th scope="col" class="px-6 py-3">Module</th>
                            <th scope="col" class="px-6 py-3">État</th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Activation
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="module in modules"
                            :key="module.slug"
                            class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                        >
                            <td class="px-6 py-4">
                                <p class="font-medium text-[var(--color-ink)]">
                                    {{ module.label }}
                                </p>
                                <p class="mt-0.5 text-[var(--color-muted)]">
                                    {{ module.description }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="
                                        module.enabled ? 'success' : 'muted'
                                    "
                                >
                                    {{ module.enabled ? 'Actif' : 'Inactif' }}
                                </BackBadge>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <BackToggle
                                    :checked="module.enabled"
                                    :label="`${module.enabled ? 'Désactiver' : 'Activer'} le module ${module.label}`"
                                    :disabled="
                                        !isAdmin ||
                                        (pendingSlug !== null &&
                                            pendingSlug !== module.slug)
                                    "
                                    @toggle="onToggle(module)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </BackLayout>
</template>
