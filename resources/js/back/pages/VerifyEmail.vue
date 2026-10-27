<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import type { BackVerifyEmailPageProps } from '@/types';
import { send } from '@/routes/verification';
import { logout } from '@/routes';
import BackAuthLayout from '../layouts/BackAuthLayout.vue';
import BackButton from '../components/BackButton.vue';
import BackLink from '../components/BackLink.vue';
import BackStatus from '../components/BackStatus.vue';

defineProps<BackVerifyEmailPageProps>();
</script>

<template>
    <BackAuthLayout
        title="Vérification de l'adresse e-mail"
        description="Cliquez sur le lien que nous venons de vous envoyer pour activer votre accès au back-office."
    >
        <Head title="Vérification de l'adresse e-mail" />

        <BackStatus v-if="status === 'verification-link-sent'" tone="success">
            Un nouveau lien de vérification vient de partir vers votre adresse
            e-mail.
        </BackStatus>

        <div class="flex flex-col items-center gap-4">
            <Form
                v-bind="send.form()"
                v-slot="{ errors, processing }"
                class="flex w-full flex-col items-center gap-2"
            >
                <BackButton
                    type="submit"
                    variant="secondary"
                    :loading="processing"
                >
                    Renvoyer le lien de vérification
                </BackButton>

                <p v-if="errors.email" class="back-error">
                    {{ errors.email }}
                </p>
            </Form>

            <BackLink :href="logout.url()" method="post" class="text-sm">
                Se déconnecter
            </BackLink>
        </div>
    </BackAuthLayout>
</template>
