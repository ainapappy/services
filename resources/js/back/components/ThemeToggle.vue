<script setup lang="ts">
// Bascule de theme (clair / sombre / systeme) : DropdownMenu appuyee sur
// useAppearance (localStorage + cookie + classe .dark sur <html>), la
// meme source que le script anti-flash du blade.
import { useAppearance } from '@/composables/useAppearance';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { MonitorIcon, MoonIcon, SunIcon } from '@lucide/vue';

const { appearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light', label: 'Clair', icon: SunIcon },
    { value: 'dark', label: 'Sombre', icon: MoonIcon },
    { value: 'system', label: 'Système', icon: MonitorIcon },
] as const;
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" aria-label="Changer de thème">
                <SunIcon
                    class="size-4 scale-100 rotate-0 transition-all dark:scale-0 dark:-rotate-90"
                />
                <MoonIcon
                    class="absolute size-4 scale-0 rotate-90 transition-all dark:scale-100 dark:rotate-0"
                />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="option in options"
                :key="option.value"
                :data-checked="appearance === option.value || undefined"
                @select="updateAppearance(option.value)"
            >
                <component :is="option.icon" />
                {{ option.label }}
                <span
                    v-if="appearance === option.value"
                    class="ml-auto text-xs text-muted-foreground"
                >
                    Actif
                </span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
