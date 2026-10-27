<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import type { BackLoginPageProps } from '@/types';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import BackAuthLayout from '../layouts/BackAuthLayout.vue';
import BackButton from '../components/BackButton.vue';
import BackField from '../components/BackField.vue';
import BackInput from '../components/BackInput.vue';
import BackLink from '../components/BackLink.vue';
import BackStatus from '../components/BackStatus.vue';

defineProps<BackLoginPageProps>();
</script>

<template>
    <BackAuthLayout
        title="Connexion"
        description="Accédez au back-office DevToolbox."
    >
        <Head title="Connexion" />

        <BackStatus v-if="status" tone="info">{{ status }}</BackStatus>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
            <BackField
                label="Adresse e-mail"
                field-id="email"
                :error="errors.email"
            >
                <BackInput
                    id="email"
                    type="email"
                    name="email"
                    required
                    v-focus
                    autocomplete="email"
                    placeholder="admin@example.com"
                />
            </BackField>

            <BackField
                label="Mot de passe"
                field-id="password"
                :error="errors.password"
            >
                <BackInput
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <BackLink
                    v-if="canResetPassword"
                    :href="request.url()"
                    class="self-end text-xs"
                >
                    Mot de passe oublié ?
                </BackLink>
            </BackField>

            <label
                class="flex items-center gap-2.5 text-sm text-[var(--color-ink)]"
            >
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    class="back-checkbox"
                />
                <span>Se souvenir de moi</span>
            </label>

            <BackButton type="submit" :loading="processing" class="mt-1 w-full">
                Se connecter
            </BackButton>
        </Form>
    </BackAuthLayout>
</template>
