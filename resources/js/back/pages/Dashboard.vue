<script setup lang="ts">
// Tableau de bord : statistique des modules actifs, liens rapides vers les
// ecrans Gateway / Connectors / Webhooks et etat du registre. Construit
// uniquement depuis les props partagees (aucune donnee serveur dediee).
import { computed, type Component } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { BackDashboardPageProps } from '@/types';
import { index as connectorsIndex } from '@/routes/admin/connectors';
import { index as gatewayKeysIndex } from '@/routes/admin/gateway/keys';
import { index as webhooksEndpointsIndex } from '@/routes/admin/webhooks/endpoints';
import BackLayout from '../layouts/BackLayout.vue';
import BackPageHeader from '../components/BackPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ActivityIcon,
    ArrowUpRightIcon,
    BoxesIcon,
    CableIcon,
    CreditCardIcon,
    KeyRoundIcon,
    LayoutTemplateIcon,
    WebhookIcon,
} from '@lucide/vue';

defineProps<BackDashboardPageProps>();

const page = usePage<BackDashboardPageProps>();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

const modules = computed(() => page.props.modules);
const activeModulesCount = computed(
    () => modules.value.filter((module) => module.enabled).length,
);

/* Catalogue local slug -> libelle + icone : les props partagees ne
   transportent que slug et enabled. */
const moduleCatalog: Record<string, { label: string; icon: Component }> = {
    landing: { label: 'Landing', icon: LayoutTemplateIcon },
    pricing: { label: 'Pricing', icon: CreditCardIcon },
    gateway: { label: 'Gateway', icon: KeyRoundIcon },
    connectors: { label: 'Connectors', icon: CableIcon },
    webhooks: { label: 'Webhooks', icon: WebhookIcon },
    monitoring: { label: 'Monitoring', icon: ActivityIcon },
};

/* Lignes de la carte « Etat des modules » : tout le registre, dans l'ordre. */
const moduleRows = computed(() =>
    modules.value.map((module) => ({
        slug: module.slug,
        enabled: module.enabled,
        label: moduleCatalog[module.slug]?.label ?? module.slug,
        icon: moduleCatalog[module.slug]?.icon ?? null,
    })),
);

/* Liens rapides : l'index du module SI active, et admin pour Gateway et
   Webhooks (ecrans interdits aux autres roles) ; sinon repere « A venir »
   non cliquable. Meme arbitrage que la navigation de BackLayout. */
const quickLinks = computed(() => {
    const isEnabled = (slug: string): boolean =>
        modules.value.find((module) => module.slug === slug)?.enabled ?? false;

    const hrefs: Record<string, string> = {};

    if (isEnabled('gateway') && isAdmin.value) {
        hrefs.gateway = gatewayKeysIndex.url();
    }

    if (isEnabled('connectors')) {
        hrefs.connectors = connectorsIndex.url();
    }

    if (isEnabled('webhooks') && isAdmin.value) {
        hrefs.webhooks = webhooksEndpointsIndex.url();
    }

    return [
        { slug: 'gateway', label: 'Gateway', hint: 'Clés d’API signées' },
        {
            slug: 'connectors',
            label: 'Connectors',
            hint: 'Connexions aux services tiers',
        },
        {
            slug: 'webhooks',
            label: 'Webhooks',
            hint: 'Livraisons sortantes et tentatives',
        },
    ].map((link) => ({ ...link, href: hrefs[link.slug] ?? null }));
});
</script>

<template>
    <BackLayout>
        <Head title="Tableau de bord" />

        <section class="flex flex-col gap-6">
            <BackPageHeader
                title="Tableau de bord"
                description="Vue d’ensemble de la plateforme Services."
            />

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <!-- Modules actifs : seule statistique reellement calculee. -->
                <Card>
                    <CardHeader>
                        <CardDescription>Modules actifs</CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{ activeModulesCount }} / {{ modules.length }}
                        </CardTitle>
                        <CardAction>
                            <div
                                class="flex size-9 items-center justify-center rounded-md bg-muted"
                            >
                                <BoxesIcon
                                    class="size-5 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </div>
                        </CardAction>
                    </CardHeader>
                    <CardFooter>
                        <p class="text-xs text-muted-foreground">
                            Modules activés dans le registre
                        </p>
                    </CardFooter>
                </Card>

                <!-- Liens rapides Gateway / Connectors / Webhooks. -->
                <Card v-for="link in quickLinks" :key="link.slug">
                    <CardHeader>
                        <CardDescription>{{ link.label }}</CardDescription>
                        <CardTitle class="text-3xl" aria-hidden="true">
                            —
                        </CardTitle>
                        <CardAction>
                            <Button
                                v-if="link.href !== null"
                                as-child
                                variant="ghost"
                                size="icon"
                            >
                                <Link :href="link.href">
                                    <ArrowUpRightIcon />
                                    <span class="sr-only">
                                        Ouvrir {{ link.label }}
                                    </span>
                                </Link>
                            </Button>
                            <Badge v-else variant="secondary"> À venir </Badge>
                        </CardAction>
                    </CardHeader>
                    <CardFooter>
                        <p class="text-xs text-muted-foreground">
                            {{ link.hint }}
                        </p>
                    </CardFooter>
                </Card>
            </div>

            <!-- Etat des modules : registre complet et activation courante. -->
            <Card>
                <CardHeader>
                    <CardTitle>État des modules</CardTitle>
                    <CardDescription>
                        État d’activation de chaque domaine de la plateforme.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="divide-y">
                        <li
                            v-for="module in moduleRows"
                            :key="module.slug"
                            class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex size-9 items-center justify-center rounded-md bg-muted"
                                >
                                    <component
                                        :is="module.icon"
                                        v-if="module.icon !== null"
                                        class="size-5 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </div>
                                <span class="text-sm font-medium">
                                    {{ module.label }}
                                </span>
                            </div>
                            <Badge
                                :variant="
                                    module.enabled ? 'success' : 'secondary'
                                "
                            >
                                {{ module.enabled ? 'Actif' : 'Inactif' }}
                            </Badge>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </section>
    </BackLayout>
</template>
