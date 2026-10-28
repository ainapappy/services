<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { BackVerifyEmailPageProps } from '@/types';
import { send } from '@/routes/verification';
import { logout } from '@/routes';
import BackAuthLayout from '../layouts/BackAuthLayout.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';

defineProps<BackVerifyEmailPageProps>();
</script>

<template>
    <BackAuthLayout
        title="Vérification de l'adresse e-mail"
        description="Cliquez sur le lien que nous venons de vous envoyer pour activer votre accès au back-office."
    >
        <Head title="Vérification de l'adresse e-mail" />

        <Alert
            v-if="status === 'verification-link-sent'"
            variant="success"
            role="status"
        >
            <AlertDescription>
                Un nouveau lien de vérification vient de partir vers votre
                adresse e-mail.
            </AlertDescription>
        </Alert>

        <div class="flex flex-col items-center gap-4">
            <Form
                v-bind="send.form()"
                v-slot="{ errors, processing }"
                class="flex w-full flex-col items-center gap-2"
            >
                <Button
                    type="submit"
                    variant="secondary"
                    class="w-full"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" class="size-4" />
                    Renvoyer le lien de vérification
                </Button>

                <FieldError
                    :errors="errors.email ? [errors.email] : undefined"
                />
            </Form>

            <Link
                :href="logout.url()"
                method="post"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
            >
                Se déconnecter
            </Link>
        </div>
    </BackAuthLayout>
</template>
