<script setup lang="ts">
// Profil : identite et mot de passe en onglets, banniere de renvoi du lien
// de verification quand l'email n'est pas confirme (mecanique Fortify).
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { BackProfilePageProps } from '@/types';
import { update as updateProfile } from '@/routes/admin/profile';
import { update as updatePassword } from '@/routes/admin/password';
import { send } from '@/routes/verification';
import BackLayout from '../layouts/BackLayout.vue';
import BackPageHeader from '../components/BackPageHeader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

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
            <BackPageHeader
                title="Profil"
                description="Gérez votre identité et votre mot de passe."
            />

            <!-- Flash de statut apres mise a jour (succes). -->
            <Alert v-if="status" variant="success" role="status">
                <AlertTitle>{{ status }}</AlertTitle>
            </Alert>

            <!-- Email non verifie : renvoi du lien imbrique dans la banniere. -->
            <Alert v-if="emailUnverified" variant="warning" role="alert">
                <AlertTitle>Adresse e-mail non vérifiée</AlertTitle>
                <AlertDescription class="flex flex-col gap-3">
                    <span>
                        Votre adresse e-mail n’est pas encore vérifiée. Cliquez
                        sur le lien reçu pour sécuriser votre accès.
                    </span>
                    <Form
                        v-bind="send.form()"
                        v-slot="{ processing }"
                        class="flex"
                    >
                        <Button
                            type="submit"
                            variant="secondary"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" class="size-4" />
                            Renvoyer le lien de vérification
                        </Button>
                    </Form>
                </AlertDescription>
            </Alert>

            <Tabs default-value="identite">
                <TabsList>
                    <TabsTrigger value="identite">Identité</TabsTrigger>
                    <TabsTrigger value="mot-de-passe">
                        Mot de passe
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="identite">
                    <Card>
                        <CardHeader>
                            <CardTitle>Informations personnelles</CardTitle>
                            <CardDescription>
                                Nom et adresse e-mail affichés dans le
                                back-office.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                v-bind="updateProfile.form()"
                                v-slot="{ errors, processing }"
                                class="flex flex-col gap-4"
                            >
                                <Field>
                                    <FieldLabel for="name">Nom</FieldLabel>
                                    <Input
                                        id="name"
                                        type="text"
                                        name="name"
                                        required
                                        autocomplete="name"
                                        :default-value="user?.name"
                                    />
                                    <FieldError
                                        :errors="
                                            errors.name ? [errors.name] : []
                                        "
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel for="email">
                                        Adresse e-mail
                                    </FieldLabel>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autocomplete="email"
                                        :default-value="user?.email"
                                    />
                                    <FieldError
                                        :errors="
                                            errors.email ? [errors.email] : []
                                        "
                                    />
                                </Field>

                                <div class="flex justify-end">
                                    <Button
                                        type="submit"
                                        :disabled="processing"
                                    >
                                        <Spinner
                                            v-if="processing"
                                            class="size-4"
                                        />
                                        Enregistrer
                                    </Button>
                                </div>
                            </Form>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="mot-de-passe">
                    <Card>
                        <CardHeader>
                            <CardTitle>Mot de passe</CardTitle>
                            <CardDescription>
                                La mise à jour demande votre mot de passe
                                actuel.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                v-bind="updatePassword.form()"
                                v-slot="{ errors, processing }"
                                class="flex flex-col gap-4"
                            >
                                <Field>
                                    <FieldLabel for="current_password">
                                        Mot de passe actuel
                                    </FieldLabel>
                                    <Input
                                        id="current_password"
                                        type="password"
                                        name="current_password"
                                        required
                                        autocomplete="current-password"
                                    />
                                    <FieldError
                                        :errors="
                                            errors.current_password
                                                ? [errors.current_password]
                                                : []
                                        "
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel for="password">
                                        Nouveau mot de passe
                                    </FieldLabel>
                                    <Input
                                        id="password"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <FieldError
                                        :errors="
                                            errors.password
                                                ? [errors.password]
                                                : []
                                        "
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel for="password_confirmation">
                                        Confirmation du nouveau mot de passe
                                    </FieldLabel>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <FieldError
                                        :errors="
                                            errors.password_confirmation
                                                ? [errors.password_confirmation]
                                                : []
                                        "
                                    />
                                </Field>

                                <div class="flex justify-end">
                                    <Button
                                        type="submit"
                                        :disabled="processing"
                                    >
                                        <Spinner
                                            v-if="processing"
                                            class="size-4"
                                        />
                                        Mettre à jour le mot de passe
                                    </Button>
                                </div>
                            </Form>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </section>
    </BackLayout>
</template>
