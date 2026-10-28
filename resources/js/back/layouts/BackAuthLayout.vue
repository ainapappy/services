<script setup lang="ts">
// Mini-layout des ecrans d'authentification back-office (login, mot de
// passe oublie, reinitialisation, verification d'email) : page autonome
// centree hors sidebar, carte shadcn.
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background px-4 py-10"
    >
        <div class="flex items-center gap-2 text-sm">
            <span
                class="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground"
            >
                <AppLogoIcon class="size-5" />
            </span>
            <span class="font-semibold tracking-tight">Services</span>
            <span class="text-muted-foreground">/ Back-office</span>
        </div>

        <Card class="w-full max-w-sm">
            <CardHeader>
                <CardTitle class="text-lg tracking-tight">
                    {{ title }}
                </CardTitle>
                <CardDescription v-if="description">
                    {{ description }}
                </CardDescription>
            </CardHeader>
            <CardContent class="flex flex-col gap-5">
                <slot />
            </CardContent>
        </Card>
    </div>
</template>
