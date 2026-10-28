<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import type { BackResetPasswordPageProps } from '@/types';
import { update } from '@/routes/password';
import BackAuthLayout from '../layouts/BackAuthLayout.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

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
            <Field>
                <FieldLabel for="email-display">Adresse e-mail</FieldLabel>
                <Input
                    id="email-display"
                    type="email"
                    :value="email"
                    readonly
                />
                <FieldDescription>
                    Compte concerné par la réinitialisation.
                </FieldDescription>
            </Field>

            <Field>
                <FieldLabel for="password">Nouveau mot de passe</FieldLabel>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :autofocus="true"
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                />
                <FieldError
                    :errors="errors.password ? [errors.password] : undefined"
                />
            </Field>

            <Field>
                <FieldLabel for="password_confirmation">
                    Confirmation du mot de passe
                </FieldLabel>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    :autofocus="false"
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                />
                <FieldError
                    :errors="
                        errors.password_confirmation
                            ? [errors.password_confirmation]
                            : undefined
                    "
                />
            </Field>

            <Button type="submit" class="w-full" :disabled="processing">
                <Spinner v-if="processing" class="size-4" />
                Réinitialiser le mot de passe
            </Button>
        </Form>
    </BackAuthLayout>
</template>
