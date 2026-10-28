<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { BackLoginPageProps } from '@/types';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import BackAuthLayout from '../layouts/BackAuthLayout.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

defineProps<BackLoginPageProps>();
</script>

<template>
    <BackAuthLayout
        title="Connexion"
        description="Accédez au back-office Services."
    >
        <Head title="Connexion" />

        <Alert v-if="status" variant="success" role="status">
            <AlertDescription>{{ status }}</AlertDescription>
        </Alert>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
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

            <Field>
                <FieldLabel for="password">Mot de passe</FieldLabel>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :autofocus="false"
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <FieldError
                    :errors="errors.password ? [errors.password] : undefined"
                />
            </Field>

            <Link
                v-if="canResetPassword"
                :href="request.url()"
                class="self-end text-xs text-muted-foreground underline-offset-4 hover:underline"
            >
                Mot de passe oublié ?
            </Link>

            <Field orientation="horizontal">
                <Checkbox id="remember" name="remember" />
                <FieldLabel for="remember" class="font-normal">
                    Se souvenir de moi
                </FieldLabel>
            </Field>

            <Button type="submit" class="mt-1 w-full" :disabled="processing">
                <Spinner v-if="processing" class="size-4" />
                Se connecter
            </Button>
        </Form>
    </BackAuthLayout>
</template>
