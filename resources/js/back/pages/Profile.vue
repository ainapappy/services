<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { BackProfilePageProps } from '@/types';
import { update as updateProfile } from '@/routes/admin/profile';
import { update as updatePassword } from '@/routes/admin/password';
import { send } from '@/routes/verification';
import BackLayout from '../layouts/BackLayout.vue';
import BackButton from '../components/BackButton.vue';
import BackCard from '../components/BackCard.vue';
import BackField from '../components/BackField.vue';
import BackInput from '../components/BackInput.vue';
import BackStatus from '../components/BackStatus.vue';

defineProps<BackProfilePageProps>();

const page = usePage<BackProfilePageProps>();
const user = computed(() => page.props.auth.user);

const emailUnverified = computed(
    () =>
        page.props.mustVerifyEmail &&
        user.value !== null &&
        user.value.email_verified_at === null,
);
</script>

<template>
    <BackLayout>
        <Head title="Profil" />

        <section class="flex max-w-3xl flex-col gap-6">
            <header class="flex flex-col gap-1">
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    Profil
                </h1>
                <p class="text-sm text-[var(--color-muted)]">
                    Gérez votre identité et votre mot de passe.
                </p>
            </header>

            <BackStatus v-if="status" tone="success">{{ status }}</BackStatus>

            <div
                v-if="emailUnverified"
                class="back-status back-status--warning"
                role="alert"
            >
                <div class="flex flex-col gap-3">
                    <p>
                        Votre adresse e-mail n’est pas encore vérifiée. Cliquez
                        sur le lien reçu pour sécuriser votre accès.
                    </p>
                    <Form
                        v-bind="send.form()"
                        v-slot="{ processing }"
                        class="flex"
                    >
                        <BackButton
                            type="submit"
                            variant="secondary"
                            :loading="processing"
                        >
                            Renvoyer le lien de vérification
                        </BackButton>
                    </Form>
                </div>
            </div>

            <BackCard
                title="Informations personnelles"
                description="Nom et adresse e-mail affichés dans le back-office."
            >
                <Form
                    v-bind="updateProfile.form()"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-4"
                >
                    <BackField label="Nom" field-id="name" :error="errors.name">
                        <BackInput
                            id="name"
                            type="text"
                            name="name"
                            required
                            autocomplete="name"
                            :value="user?.name"
                        />
                    </BackField>

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
                            autocomplete="email"
                            :value="user?.email"
                        />
                    </BackField>

                    <div class="flex justify-end">
                        <BackButton type="submit" :loading="processing">
                            Enregistrer
                        </BackButton>
                    </div>
                </Form>
            </BackCard>

            <BackCard
                title="Mot de passe"
                description="La mise à jour demande votre mot de passe actuel."
            >
                <Form
                    v-bind="updatePassword.form()"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-4"
                >
                    <BackField
                        label="Mot de passe actuel"
                        field-id="current_password"
                        :error="errors.current_password"
                    >
                        <BackInput
                            id="current_password"
                            type="password"
                            name="current_password"
                            required
                            autocomplete="current-password"
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
                            autocomplete="new-password"
                        />
                    </BackField>

                    <BackField
                        label="Confirmation du nouveau mot de passe"
                        field-id="password_confirmation"
                        :error="errors.password_confirmation"
                    >
                        <BackInput
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                    </BackField>

                    <div class="flex justify-end">
                        <BackButton type="submit" :loading="processing">
                            Mettre à jour le mot de passe
                        </BackButton>
                    </div>
                </Form>
            </BackCard>
        </section>
    </BackLayout>
</template>
