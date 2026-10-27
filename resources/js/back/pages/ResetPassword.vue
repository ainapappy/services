<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import type { BackResetPasswordPageProps } from '@/types';
import { update } from '@/routes/password';
import BackAuthLayout from '../layouts/BackAuthLayout.vue';
import BackButton from '../components/BackButton.vue';
import BackField from '../components/BackField.vue';
import BackInput from '../components/BackInput.vue';

const props = defineProps<BackResetPasswordPageProps>();
</script>

<template>
    <BackAuthLayout
        title="Nouveau mot de passe"
        description="Choisissez un nouveau mot de passe pour votre compte."
    >
        <Head title="Nouveau mot de passe" />

        <Form
            v-bind="update.form()"
            :transform="
                (data) => ({ ...data, token: props.token, email: props.email })
            "
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
            <BackField
                label="Adresse e-mail"
                field-id="email-display"
                help="Compte concerné par la réinitialisation."
            >
                <BackInput
                    id="email-display"
                    type="email"
                    :value="email"
                    readonly
                />
            </BackField>

            <BackField
                label="Nouveau mot de passe"
                field-id="password"
                :error="errors.password"
            >
                <BackInput
                    id="password"
                    type="password"
                    name="password"
                    required
                    v-focus
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                />
            </BackField>

            <BackField
                label="Confirmation du mot de passe"
                field-id="password_confirmation"
                :error="errors.password_confirmation"
            >
                <BackInput
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                />
            </BackField>

            <BackButton type="submit" :loading="processing" class="w-full">
                Réinitialiser le mot de passe
            </BackButton>
        </Form>
    </BackAuthLayout>
</template>
