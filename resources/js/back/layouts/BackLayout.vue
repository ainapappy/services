<script setup lang="ts">
// Coquille back-office shadcn : sidebar collapsible (kit ui/sidebar, etat
// persiste en cookie, Sheet en mobile), header avec breadcrumb + bascule
// de theme + menu utilisateur, toaster des flashs Inertia.
// Aucun import hors vue / @inertiajs / @lucide / types / ui / composables
// / routes : les arbres front/ et back/ restent etanches.
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, type Component } from 'vue';
import type { AppSharedProps } from '@/types';
import { dashboard } from '@/routes/admin';
import { index as connectorsIndex } from '@/routes/admin/connectors';
import { index as gatewayKeysIndex } from '@/routes/admin/gateway/keys';
import { index as landingIndex } from '@/routes/admin/landing';
import { index as modulesIndex } from '@/routes/admin/modules';
import { dashboard as monitoringDashboard } from '@/routes/admin/monitoring';
import { index as pricingPlansIndex } from '@/routes/admin/pricing/plans';
import { index as webhooksEndpointsIndex } from '@/routes/admin/webhooks/endpoints';
import { edit as profileEdit } from '@/routes/admin/profile';
import { logout } from '@/routes';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Toaster } from '@/components/ui/sonner';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    ActivityIcon,
    BoxesIcon,
    CableIcon,
    CircleUserRoundIcon,
    CreditCardIcon,
    KeyRoundIcon,
    LayoutDashboardIcon,
    LayoutTemplateIcon,
    LogOutIcon,
    WebhookIcon,
} from '@lucide/vue';
import ThemeToggle from '../components/ThemeToggle.vue';

const page = usePage<AppSharedProps>();
const user = computed(() => page.props.auth.user);

const roleLabels: Record<string, string> = {
    admin: 'Administrateur',
    editor: 'Éditeur',
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

/* Navigation principale de la section « Général ». L'entrée Modules reste
   reservee aux administrateurs. */
const mainLinks = computed(() => {
    const links = [
        {
            label: 'Tableau de bord',
            href: dashboard.url(),
            base: '/admin',
            icon: LayoutDashboardIcon,
        },
    ];

    if (user.value?.role === 'admin') {
        links.push({
            label: 'Modules',
            href: modulesIndex.url(),
            base: '/admin/modules',
            icon: BoxesIcon,
        });
    }

    return links;
});

/* Catalogue des modules metier, dans l'ordre du registre, avec leur
   icone. Un module active expose un vrai lien vers son ecran
   d'administration ; les autres restent grisés avec un repere « a
   venir ». */
const moduleCatalog: Array<{
    slug: string;
    label: string;
    icon: Component;
}> = [
    { slug: 'landing', label: 'Landing', icon: LayoutTemplateIcon },
    { slug: 'pricing', label: 'Pricing', icon: CreditCardIcon },
    { slug: 'gateway', label: 'Gateway', icon: KeyRoundIcon },
    { slug: 'connectors', label: 'Connectors', icon: CableIcon },
    { slug: 'webhooks', label: 'Webhooks', icon: WebhookIcon },
    { slug: 'monitoring', label: 'Monitoring', icon: ActivityIcon },
];

const landingEnabled = computed(
    () =>
        page.props.modules.find((module) => module.slug === 'landing')
            ?.enabled ?? false,
);

const pricingEnabled = computed(
    () =>
        page.props.modules.find((module) => module.slug === 'pricing')
            ?.enabled ?? false,
);

const gatewayEnabled = computed(
    () =>
        page.props.modules.find((module) => module.slug === 'gateway')
            ?.enabled ?? false,
);

const connectorsEnabled = computed(
    () =>
        page.props.modules.find((module) => module.slug === 'connectors')
            ?.enabled ?? false,
);

const webhooksEnabled = computed(
    () =>
        page.props.modules.find((module) => module.slug === 'webhooks')
            ?.enabled ?? false,
);

const monitoringEnabled = computed(
    () =>
        page.props.modules.find((module) => module.slug === 'monitoring')
            ?.enabled ?? false,
);

/* Liens des modules metier reellement actives : une entree sans lien reste
   grise (« a venir »). Pricing pointe vers la liste des plans ; les tables
   de prix sont atteignables depuis cet ecran. La gestion des cles Gateway
   et des endpoints Webhooks est reservee aux administrateurs (403 pour les
   autres roles), tandis que le CRUD Connectors est ouvert aux
   administrateurs ET aux editeurs. */
const enabledModuleHrefs = computed<Record<string, string>>(() => {
    const hrefs: Record<string, string> = {};

    if (landingEnabled.value) {
        hrefs.landing = landingIndex.url();
    }

    if (pricingEnabled.value) {
        hrefs.pricing = pricingPlansIndex.url();
    }

    if (gatewayEnabled.value && user.value?.role === 'admin') {
        hrefs.gateway = gatewayKeysIndex.url();
    }

    if (connectorsEnabled.value) {
        hrefs.connectors = connectorsIndex.url();
    }

    if (webhooksEnabled.value && user.value?.role === 'admin') {
        hrefs.webhooks = webhooksEndpointsIndex.url();
    }

    if (monitoringEnabled.value && user.value?.role === 'admin') {
        hrefs.monitoring = monitoringDashboard.url();
    }

    return hrefs;
});

const moduleNav = computed(() =>
    moduleCatalog
        /* Les entrees Gateway, Webhooks et Monitoring ne sont pas affichees
           du tout aux non-admins : leurs ecrans d'administration leur
           sont interdits cote serveur. */
        .filter(
            (module) =>
                (module.slug !== 'gateway' &&
                    module.slug !== 'webhooks' &&
                    module.slug !== 'monitoring') ||
                user.value?.role === 'admin',
        )
        .map((module) => ({
            ...module,
            href: enabledModuleHrefs.value[module.slug] ?? null,
        })),
);

const isActive = (base: string): boolean =>
    base === '/admin' ? page.url === '/admin' : page.url.startsWith(base);

const sectionTitle = computed(() => {
    if (page.url.startsWith('/admin/landing')) {
        return 'Landing';
    }

    if (page.url.startsWith('/admin/pricing')) {
        return 'Pricing';
    }

    if (page.url.startsWith('/admin/gateway')) {
        return 'Gateway';
    }

    if (page.url.startsWith('/admin/connectors')) {
        return 'Connectors';
    }

    if (page.url.startsWith('/admin/webhooks')) {
        return 'Webhooks';
    }

    if (page.url.startsWith('/admin/monitoring')) {
        return 'Monitoring';
    }

    if (page.url.startsWith('/admin/modules')) {
        return 'Modules';
    }

    if (page.url.startsWith('/admin/profile')) {
        return 'Profil';
    }

    return 'Tableau de bord';
});

/* Logout : spinner le temps du POST, puis redirection cote serveur. */
const loggingOut = ref(false);

function onLogout(): void {
    if (loggingOut.value) {
        return;
    }

    loggingOut.value = true;
    router.post(
        logout.url(),
        {},
        {
            onFinish: () => {
                loggingOut.value = false;
            },
        },
    );
}
</script>

<template>
    <SidebarProvider>
        <Toaster position="bottom-right" />

        <Sidebar collapsible="icon">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            size="lg"
                            :tooltip="'Tableau de bord'"
                        >
                            <Link :href="dashboard.url()">
                                <span
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                                >
                                    <AppLogoIcon class="size-5" />
                                </span>
                                <span
                                    class="flex flex-col leading-tight group-data-[collapsible=icon]:hidden"
                                >
                                    <span class="text-sm font-semibold">
                                        Services
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        Administration
                                    </span>
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupLabel>Général</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem
                                v-for="link in mainLinks"
                                :key="link.href"
                            >
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive(link.base)"
                                    :tooltip="link.label"
                                >
                                    <Link
                                        :href="link.href"
                                        :aria-current="
                                            isActive(link.base)
                                                ? 'page'
                                                : undefined
                                        "
                                    >
                                        <component :is="link.icon" />
                                        <span>{{ link.label }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>

                <SidebarGroup>
                    <SidebarGroupLabel>Modules</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem
                                v-for="module in moduleNav"
                                :key="module.slug"
                            >
                                <SidebarMenuButton
                                    v-if="module.href !== null"
                                    as-child
                                    :is-active="
                                        isActive(`/admin/${module.slug}`)
                                    "
                                    :tooltip="module.label"
                                >
                                    <Link
                                        :href="module.href"
                                        :aria-current="
                                            isActive(`/admin/${module.slug}`)
                                                ? 'page'
                                                : undefined
                                        "
                                    >
                                        <component :is="module.icon" />
                                        <span>{{ module.label }}</span>
                                    </Link>
                                </SidebarMenuButton>
                                <SidebarMenuButton
                                    v-else
                                    disabled
                                    :tooltip="`${module.label} — à venir`"
                                >
                                    <component :is="module.icon" />
                                    <span>{{ module.label }}</span>
                                    <SidebarMenuBadge
                                        class="pointer-events-none"
                                    >
                                        À venir
                                    </SidebarMenuBadge>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <p
                    class="px-2 pb-1 text-xs text-muted-foreground group-data-[collapsible=icon]:hidden"
                >
                    Back-office Services
                </p>
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>

        <SidebarInset>
            <header
                class="sticky top-0 z-10 flex h-14 shrink-0 items-center gap-2 border-b bg-background px-4"
            >
                <SidebarTrigger class="-ml-1" />
                <Separator
                    orientation="vertical"
                    class="mr-2 data-[orientation=vertical]:h-4"
                />

                <Breadcrumb>
                    <BreadcrumbList>
                        <slot name="breadcrumb">
                            <template v-if="page.url === '/admin'">
                                <BreadcrumbItem>
                                    <BreadcrumbPage>
                                        Tableau de bord
                                    </BreadcrumbPage>
                                </BreadcrumbItem>
                            </template>
                            <template v-else>
                                <BreadcrumbItem>
                                    <BreadcrumbLink as-child>
                                        <Link :href="dashboard.url()">
                                            Administration
                                        </Link>
                                    </BreadcrumbLink>
                                </BreadcrumbItem>
                                <BreadcrumbSeparator />
                                <BreadcrumbItem>
                                    <BreadcrumbPage>
                                        {{ sectionTitle }}
                                    </BreadcrumbPage>
                                </BreadcrumbItem>
                            </template>
                        </slot>
                    </BreadcrumbList>
                </Breadcrumb>

                <div v-if="user" class="ml-auto flex items-center gap-1.5">
                    <ThemeToggle />

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-md p-1.5 outline-hidden transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring"
                                aria-haspopup="menu"
                            >
                                <Avatar class="size-7">
                                    <AvatarFallback
                                        class="bg-primary text-xs font-semibold text-primary-foreground"
                                    >
                                        {{ initials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span
                                    class="hidden flex-col items-start leading-tight sm:flex"
                                >
                                    <span class="text-sm font-medium">
                                        {{ user.name }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ roleLabels[user.role] }}
                                    </span>
                                </span>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-60">
                            <DropdownMenuLabel class="flex flex-col">
                                <span class="truncate font-medium">
                                    {{ user.name }}
                                </span>
                                <span
                                    class="truncate text-xs font-normal text-muted-foreground"
                                >
                                    {{ user.email }}
                                </span>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link :href="profileEdit.url()">
                                    <CircleUserRoundIcon />
                                    Mon profil
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                :disabled="loggingOut"
                                @select="onLogout"
                            >
                                <Spinner v-if="loggingOut" class="size-4" />
                                <LogOutIcon v-else />
                                Se déconnecter
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 md:px-6 lg:px-8">
                <div class="mx-auto w-full max-w-5xl">
                    <slot />
                </div>
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>
