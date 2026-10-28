<script setup lang="ts">
// Coquille canonique de la vitrine : wrapper .dark (la vitrine est TOUJOURS
// sombre, quelle que soit la preference du systeme), header sticky et footer
// canoniques. SEUL importeur de front.css (les effets front). Les liens
// reprennent les cibles des anciennes navs de pages, via les routes
// Wayfinder (jamais d'URL en dur).
import '../../../css/front.css';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { home, pricing } from '@/routes';

// CTA du header surchargable : la 404 n'a pas d'ancre #commencer, elle
// pointe les tarifs a la place.
const props = withDefaults(
    defineProps<{
        ctaHref?: string;
    }>(),
    {
        ctaHref: '#commencer',
    },
);

const landingUrl = home.url();
const pricingUrl = pricing.url();
const year = new Date().getFullYear();
</script>

<template>
    <!-- isolate : cree le stacking context qui laisse les halos -z-10 se
         peindre au-dessus du fond sombre du wrapper. overflow-x-clip :
         masque les halos debordants sans casser le header sticky. -->
    <div
        class="dark isolate flex min-h-svh flex-col overflow-x-clip bg-background font-sans text-foreground"
    >
        <header
            class="sticky top-0 z-50 border-b border-border/60 bg-background/80 backdrop-blur"
        >
            <slot name="header">
                <div
                    class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-6"
                >
                    <a
                        :href="landingUrl"
                        class="flex items-center gap-2.5 text-foreground"
                    >
                        <AppLogoIcon class="size-6 text-foreground" />
                        <span class="font-semibold tracking-tight">
                            Services
                        </span>
                    </a>
                    <nav
                        class="hidden items-center gap-8 text-sm text-muted-foreground md:flex"
                        aria-label="Navigation principale"
                    >
                        <a
                            :href="`${landingUrl}#modules`"
                            class="transition-colors hover:text-foreground"
                            >Modules</a
                        >
                        <a
                            :href="`${landingUrl}#stats`"
                            class="transition-colors hover:text-foreground"
                            >Chiffres</a
                        >
                        <a
                            :href="pricingUrl"
                            class="transition-colors hover:text-foreground"
                            >Tarifs</a
                        >
                    </nav>
                    <Button as-child size="sm">
                        <a :href="props.ctaHref">Commencer</a>
                    </Button>
                </div>
            </slot>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-border/60">
            <div class="mx-auto w-full max-w-6xl px-6 py-12">
                <div
                    class="flex flex-col gap-10 md:flex-row md:items-start md:justify-between"
                >
                    <div class="max-w-xs space-y-4">
                        <a
                            :href="landingUrl"
                            class="flex items-center gap-2.5 text-foreground"
                        >
                            <AppLogoIcon class="size-5 text-foreground" />
                            <span class="font-semibold tracking-tight">
                                Services
                            </span>
                        </a>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            La passerelle qui expose, orchestre et supervise vos
                            services métier.
                        </p>
                    </div>
                    <nav
                        class="grid grid-cols-2 gap-10 text-sm sm:gap-16"
                        aria-label="Liens de pied de page"
                    >
                        <div class="space-y-3">
                            <p class="font-semibold text-foreground">Produit</p>
                            <a
                                :href="`${landingUrl}#modules`"
                                class="block text-muted-foreground transition-colors hover:text-foreground"
                                >Modules</a
                            >
                            <a
                                :href="`${landingUrl}#stats`"
                                class="block text-muted-foreground transition-colors hover:text-foreground"
                                >Chiffres</a
                            >
                        </div>
                        <div class="space-y-3">
                            <p class="font-semibold text-foreground">
                                Plateforme
                            </p>
                            <a
                                :href="pricingUrl"
                                class="block text-muted-foreground transition-colors hover:text-foreground"
                                >Tarifs</a
                            >
                            <a
                                :href="landingUrl"
                                class="block text-muted-foreground transition-colors hover:text-foreground"
                                >Accueil</a
                            >
                        </div>
                    </nav>
                </div>
                <p
                    class="mt-10 border-t border-border/60 pt-6 text-xs text-muted-foreground"
                >
                    © {{ year }} Services — Tous droits réservés.
                </p>
            </div>
        </footer>
    </div>
</template>
