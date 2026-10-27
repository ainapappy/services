<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import type { BackForgotPasswordPageProps } from '@/types';
import { email } from '@/routes/password';
import { login } from '@/routes';
import BackAuthLayout from '../layouts/BackAuthLayout.vue';
import BackButton from '../components/BackButton.vue';
import BackField from '../components/BackField.vue';
import BackInput from '../components/BackInput.vue';
import BackLink from '../components/BackLink.vue';
import BackStatus from '../components/BackStatus.vue';

defineProps<BackForgotPasswordPageProps>();
</script>

<template>
    <BackAuthLayout
        title="Mot de passe oublié"
        description="Indiquez votre adresse e-mail : nous vous enverrons un lien de réinitialisation."
    >
        <Head title="Mot de passe oublié" />

        <BackStatus v-if="status" tone="success">{{ status }}</BackStatus>

        <Form
            v-bind="email.form()"
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

            <BackButton type="submit" :loading="processing" class="w-full">
                Envoyer le lien de réinitialisation
            </BackButton>
        </Form>

        <BackLink :href="login.url()" class="self-center text-sm">
            Retour à la connexion
        </BackLink>
    </BackAuthLayout>
</template>
