<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { BackForgotPasswordPageProps } from '@/types';
import { email } from '@/routes/password';
import { login } from '@/routes';
import BackAuthLayout from '../layouts/BackAuthLayout.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

defineProps<BackForgotPasswordPageProps>();
</script>

<template>
    <BackAuthLayout
        title="Mot de passe oublié"
        description="Indiquez votre adresse e-mail : nous vous enverrons un lien de réinitialisation."
    >
        <Head title="Mot de passe oublié" />

        <Alert v-if="status" variant="success" role="status">
            <AlertDescription>{{ status }}</AlertDescription>
        </Alert>

        <Form
            v-bind="email.form()"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
            <Field>
                <FieldLabel for="email">Adresse e-mail</FieldLabel>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    v-focus
                    autocomplete="email"
                    placeholder="admin@example.com"
                />
                <FieldError
                    :errors="errors.email ? [errors.email] : undefined"
                />
            </Field>

            <Button type="submit" class="w-full" :disabled="processing">
                <Spinner v-if="processing" class="size-4" />
                Envoyer le lien de réinitialisation
            </Button>
        </Form>

        <Link
            :href="login.url()"
            class="self-center text-sm text-muted-foreground underline-offset-4 hover:underline"
        >
            Retour à la connexion
        </Link>
    </BackAuthLayout>
</template>
