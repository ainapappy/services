<script setup lang="ts">
// Formulaire de creation / edition d'un connecteur : identite (nom, type,
// url de base) puis champs de credentials ADAPTES au type choisi (mot de
// passe d'application WordPress, cle/secret Caren et Erpen, JSON libre
// Custom). Les credentials ne sont jamais rapatries : en edition, les
// champs vides signifient « conserver l'existant » (placeholders bulles).
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { EyeIcon, EyeOffIcon, ShieldCheckIcon } from '@lucide/vue';
import type { BackConnectorsFormPageProps, ConnectorType } from '@/types';
import { index, store, update } from '@/routes/admin/connectors';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<BackConnectorsFormPageProps>();

/* ------------------------------------------------------------------ */
/* Etat edite                                                          */
/* ------------------------------------------------------------------ */

const existing = computed(() => props.connector ?? null);
const isEdit = computed(() => existing.value !== null);

const typeOptions: Array<{
    value: ConnectorType;
    label: string;
    description: string;
}> = [
    {
        value: 'wordpress',
        label: 'WordPress',
        description: 'API REST native : mot de passe d’application.',
    },
    {
        value: 'caren',
        label: 'Caren',
        description: 'Clé API et secret fournis par la console Caren.',
    },
    {
        value: 'erpen',
        label: 'Erpen',
        description: 'Clé API et secret fournis par la console Erpen.',
    },
    {
        value: 'custom',
        label: 'Custom',
        description: 'Service libre : objet JSON de credentials.',
    },
];

/**
 * Donnees du formulaire. Les champs de credentials ne partent PAS tels
 * quels : la soumission les transforme en objet `credentials` dont la
 * forme depend du type (voir collectCredentials).
 */
type ConnectorFormData = {
    name: string;
    type: ConnectorType;
    base_url: string;
    username: string;
    password: string;
    api_key: string;
    secret: string;
    credentials_json: string;
};

function toFormData(value: typeof existing.value): ConnectorFormData {
    return {
        name: value?.name ?? '',
        type: value?.type ?? 'wordpress',
        base_url: value?.baseUrl ?? '',
        /* Les secrets ne sont jamais pre-remplis : le serveur ne les
           renvoie pas, des champs vides = conserver l'existant. */
        username: '',
        password: '',
        api_key: '',
        secret: '',
        credentials_json: '',
    };
}

const form = useForm<ConnectorFormData>(toFormData(existing.value));

/* Navigation creation -> edition (meme composant reutilise par Inertia) :
   on resynchronise le formulaire quand la page cible change. */
watch(
    () => props.connector?.id,
    () => {
        const next = toFormData(existing.value);
        form.name = next.name;
        form.type = next.type;
        form.base_url = next.base_url;
        form.username = '';
        form.password = '';
        form.api_key = '';
        form.secret = '';
        form.credentials_json = '';
        form.clearErrors();
    },
);

/* Changement de type : les erreurs de credentials ne s'appliquent plus
   et les champs secrets repassent masques. */
watch(
    () => form.type,
    () => {
        showPassword.value = false;
        showApiKey.value = false;
        showSecret.value = false;
        form.clearErrors(
            'username',
            'password',
            'api_key',
            'secret',
            'credentials_json',
        );
    },
);

/* ------------------------------------------------------------------ */
/* Affichage / masquage des champs secrets                              */
/* ------------------------------------------------------------------ */

const showPassword = ref(false);
const showApiKey = ref(false);
const showSecret = ref(false);

/* ------------------------------------------------------------------ */
/* Aides contextuelles                                                  */
/* ------------------------------------------------------------------ */

/* Les erreurs de validation imbriquees (ex. `credentials.username`)
   sortent des cles du formulaire local : lecture via un objet plat de
   chaines, comme le renvoie le sac d'erreurs Laravel. */
const serverErrors = computed(
    () => form.errors as Partial<Record<string, string>>,
);

/** Erreur serveur sur un champ credentials (cle imbriquee ou plate). */
function credentialError(field: string): string | undefined {
    return (
        serverErrors.value[`credentials.${field}`] ?? serverErrors.value[field]
    );
}

const keepHint = 'Laissez vide pour conserver les credentials actuels.';

const usernameHelp = computed(() =>
    isEdit.value
        ? keepHint
        : 'Identifiant utilisé pour authentifier l’API REST du site.',
);

const passwordHelp = computed(() =>
    isEdit.value
        ? keepHint
        : 'Généré dans WordPress : Utilisateurs → Profil → Mots de passe d’application.',
);

const apiKeyHelp = computed(() =>
    isEdit.value ? keepHint : 'Clé fournie par la console du service.',
);

const secretHelp = computed(() =>
    isEdit.value ? keepHint : 'Secret associé à la clé API.',
);

const jsonHelp = computed(() =>
    isEdit.value
        ? 'Laissez vide pour conserver les credentials actuels, ou collez un nouvel objet JSON pour les remplacer.'
        : 'Objet JSON libre transmis tel quel au connecteur.',
);

const jsonPlaceholder = computed(() =>
    isEdit.value ? '••••••••' : '{ "token": "..." }',
);

/* ------------------------------------------------------------------ */
/* Collecte des credentials : valide et faconne la charge utile         */
/* ------------------------------------------------------------------ */

function isPlainObject(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/** Valide la saisie JSON libre (Custom) et renvoie l'objet, ou null. */
function parseCustomJson(raw: string): Record<string, unknown> | null {
    let parsed: unknown;

    try {
        parsed = JSON.parse(raw);
    } catch {
        form.setError(
            'credentials_json',
            'JSON invalide : vérifiez la syntaxe (guillemets, virgules).',
        );
        return null;
    }

    if (!isPlainObject(parsed)) {
        form.setError(
            'credentials_json',
            'Saisissez un objet JSON, par exemple { "token": "..." }.',
        );
        return null;
    }

    return parsed;
}

type CredentialsOutcome =
    | { action: 'send'; credentials: Record<string, unknown> }
    | { action: 'omit' }
    | { action: 'invalid' };

/**
 * Construit l'objet `credentials` envoye au backend, selon le type :
 * - creation : tous les champs du type sont requis (erreur inline sinon) ;
 * - edition : champs vides = { action: 'omit' } (conserver l'existant),
 *   champs renseignes = remplacement, champ par champ.
 * Retourne { action: 'invalid' } si la validation client echoue.
 */
function collectCredentials(): CredentialsOutcome {
    if (form.type === 'custom') {
        const raw = form.credentials_json.trim();

        if (raw === '') {
            if (isEdit.value) {
                return { action: 'omit' };
            }

            form.setError(
                'credentials_json',
                'Saisissez les credentials au format JSON.',
            );
            return { action: 'invalid' };
        }

        const parsed = parseCustomJson(form.credentials_json);

        return parsed === null
            ? { action: 'invalid' }
            : { action: 'send', credentials: parsed };
    }

    const username = form.username.trim();
    const password = form.password;
    const apiKey = form.api_key.trim();
    const secret = form.secret;

    if (form.type === 'wordpress') {
        if (isEdit.value && username === '' && password === '') {
            return { action: 'omit' };
        }

        if (!isEdit.value && (username === '' || password === '')) {
            if (username === '') {
                form.setError('username', 'Le nom d’utilisateur est requis.');
            }

            if (password === '') {
                form.setError(
                    'password',
                    'Le mot de passe d’application est requis.',
                );
            }

            return { action: 'invalid' };
        }

        const credentials: Record<string, unknown> = {};

        if (username !== '') {
            credentials.username = username;
        }

        if (password !== '') {
            credentials.password = password;
        }

        return { action: 'send', credentials };
    }

    /* caren / erpen : meme contrat cle + secret. */
    if (isEdit.value && apiKey === '' && secret === '') {
        return { action: 'omit' };
    }

    if (!isEdit.value && (apiKey === '' || secret === '')) {
        if (apiKey === '') {
            form.setError('api_key', 'La clé API est requise.');
        }

        if (secret === '') {
            form.setError('secret', 'Le secret est requis.');
        }

        return { action: 'invalid' };
    }

    const credentials: Record<string, unknown> = {};

    if (apiKey !== '') {
        credentials.api_key = apiKey;
    }

    if (secret !== '') {
        credentials.secret = secret;
    }

    return { action: 'send', credentials };
}

/* ------------------------------------------------------------------ */
/* Soumission                                                           */
/* ------------------------------------------------------------------ */

function onSubmit(): void {
    const outcome = collectCredentials();

    if (outcome.action === 'invalid') {
        return;
    }

    form.transform((data) => {
        const payload: Record<string, unknown> = {
            name: data.name,
            type: data.type,
            base_url: data.base_url,
        };

        /* En edition, credentials omis = conserver l'existant. */
        if (outcome.action === 'send') {
            payload.credentials = outcome.credentials;
        }

        return payload;
    });

    if (isEdit.value && existing.value !== null) {
        void form.patch(update({ connector: existing.value.id }).url);
        return;
    }

    void form.post(store.url());
}
</script>

<template>
    <BackLayout>
        <Head
            :title="isEdit ? 'Modifier le connecteur' : 'Nouveau connecteur'"
        />

        <div class="flex flex-col gap-6">
            <p class="text-sm text-muted-foreground">
                <a
                    :href="index.url()"
                    class="underline-offset-4 hover:text-foreground hover:underline"
                >
                    Connecteurs
                </a>
                <span class="mx-1">/</span>
                <span class="text-foreground">
                    {{ isEdit ? existing?.name : 'Nouveau connecteur' }}
                </span>
            </p>

            <BackPageHeader
                :title="
                    isEdit ? 'Modifier le connecteur' : 'Nouveau connecteur'
                "
            />

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <!-- Identite -->
                <Card>
                    <CardHeader>
                        <CardTitle>Identité</CardTitle>
                        <CardDescription>
                            Nom affiché dans le back-office, type d'intégration
                            et adresse du service.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-6">
                        <Field>
                            <FieldLabel for="name">Nom</FieldLabel>
                            <Input
                                id="name"
                                name="name"
                                v-model="form.name"
                                required
                                autocomplete="off"
                                placeholder="Ex. Blog vitrine"
                            />
                            <FieldDescription>
                                Ex. Blog vitrine, Facturation Erpen.
                            </FieldDescription>
                            <FieldError
                                :errors="
                                    form.errors.name
                                        ? [form.errors.name]
                                        : undefined
                                "
                            />
                        </Field>

                        <Field>
                            <FieldLabel for="type">Type</FieldLabel>
                            <Select
                                :model-value="form.type"
                                @update:model-value="
                                    (value) =>
                                        (form.type = value as ConnectorType)
                                "
                            >
                                <SelectTrigger id="type" class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in typeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldDescription>
                                Les champs de credentials s'adaptent au type
                                choisi :
                                {{
                                    typeOptions.find(
                                        (option) => option.value === form.type,
                                    )?.description
                                }}
                            </FieldDescription>
                            <FieldError
                                :errors="
                                    form.errors.type
                                        ? [form.errors.type]
                                        : undefined
                                "
                            />
                        </Field>

                        <Field>
                            <FieldLabel for="base_url">URL de base</FieldLabel>
                            <Input
                                id="base_url"
                                name="base_url"
                                type="url"
                                v-model="form.base_url"
                                required
                                placeholder="https://exemple.com"
                            />
                            <FieldDescription>
                                Le test de connexion pingue cette adresse.
                            </FieldDescription>
                            <FieldError
                                :errors="
                                    form.errors.base_url
                                        ? [form.errors.base_url]
                                        : undefined
                                "
                            />
                        </Field>
                    </CardContent>
                </Card>

                <!-- Credentials : champs adaptes au type -->
                <Card>
                    <CardHeader>
                        <CardTitle>Credentials</CardTitle>
                        <CardDescription>
                            {{
                                isEdit
                                    ? 'Les credentials actuels ne sont jamais réaffichés.'
                                    : 'Champs adaptés au type de connecteur.'
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-6">
                        <Alert>
                            <ShieldCheckIcon />
                            <AlertDescription>
                                Les credentials sont chiffrés au repos et jamais
                                réaffichés : le serveur ne les renvoie jamais au
                                navigateur.

                                <template v-if="isEdit">
                                    Laissez les champs vides pour conserver les
                                    credentials actuels, ou saisissez de
                                    nouvelles valeurs pour les remplacer.
                                </template>
                            </AlertDescription>
                        </Alert>

                        <!-- WordPress : nom d'utilisateur + mot de passe
                             d'application -->
                        <template v-if="form.type === 'wordpress'">
                            <Field>
                                <FieldLabel for="username">
                                    Nom d'utilisateur
                                </FieldLabel>
                                <Input
                                    id="username"
                                    name="username"
                                    v-model="form.username"
                                    :required="!isEdit"
                                    autocomplete="off"
                                    :placeholder="
                                        isEdit ? '••••••••' : 'Ex. admin'
                                    "
                                />
                                <FieldDescription>
                                    {{ usernameHelp }}
                                </FieldDescription>
                                <FieldError
                                    :errors="
                                        credentialError('username')
                                            ? [credentialError('username')!]
                                            : undefined
                                    "
                                />
                            </Field>

                            <Field>
                                <FieldLabel for="password">
                                    Mot de passe d'application
                                </FieldLabel>
                                <div class="relative">
                                    <Input
                                        id="password"
                                        name="password"
                                        v-model="form.password"
                                        :type="
                                            showPassword ? 'text' : 'password'
                                        "
                                        class="pr-10"
                                        :required="!isEdit"
                                        autocomplete="new-password"
                                        :placeholder="
                                            isEdit ? '••••••••' : undefined
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-muted-foreground outline-none hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"
                                        :aria-pressed="showPassword"
                                        :aria-label="
                                            showPassword
                                                ? 'Masquer le mot de passe'
                                                : 'Afficher le mot de passe'
                                        "
                                        @click="showPassword = !showPassword"
                                    >
                                        <EyeOffIcon
                                            v-if="showPassword"
                                            class="size-4"
                                        />
                                        <EyeIcon v-else class="size-4" />
                                    </button>
                                </div>
                                <FieldDescription>
                                    {{ passwordHelp }}
                                </FieldDescription>
                                <FieldError
                                    :errors="
                                        credentialError('password')
                                            ? [credentialError('password')!]
                                            : undefined
                                    "
                                />
                            </Field>
                        </template>

                        <!-- Caren / Erpen : cle API + secret -->
                        <template
                            v-else-if="
                                form.type === 'caren' || form.type === 'erpen'
                            "
                        >
                            <Field>
                                <FieldLabel for="api_key">Clé API</FieldLabel>
                                <div class="relative">
                                    <Input
                                        id="api_key"
                                        name="api_key"
                                        v-model="form.api_key"
                                        :type="showApiKey ? 'text' : 'password'"
                                        class="pr-10"
                                        :required="!isEdit"
                                        autocomplete="off"
                                        :placeholder="
                                            isEdit ? '••••••••' : undefined
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-muted-foreground outline-none hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"
                                        :aria-pressed="showApiKey"
                                        :aria-label="
                                            showApiKey
                                                ? 'Masquer la clé API'
                                                : 'Afficher la clé API'
                                        "
                                        @click="showApiKey = !showApiKey"
                                    >
                                        <EyeOffIcon
                                            v-if="showApiKey"
                                            class="size-4"
                                        />
                                        <EyeIcon v-else class="size-4" />
                                    </button>
                                </div>
                                <FieldDescription>
                                    {{ apiKeyHelp }}
                                </FieldDescription>
                                <FieldError
                                    :errors="
                                        credentialError('api_key')
                                            ? [credentialError('api_key')!]
                                            : undefined
                                    "
                                />
                            </Field>

                            <Field>
                                <FieldLabel for="secret">Secret</FieldLabel>
                                <div class="relative">
                                    <Input
                                        id="secret"
                                        name="secret"
                                        v-model="form.secret"
                                        :type="showSecret ? 'text' : 'password'"
                                        class="pr-10"
                                        :required="!isEdit"
                                        autocomplete="new-password"
                                        :placeholder="
                                            isEdit ? '••••••••' : undefined
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-muted-foreground outline-none hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"
                                        :aria-pressed="showSecret"
                                        :aria-label="
                                            showSecret
                                                ? 'Masquer le secret'
                                                : 'Afficher le secret'
                                        "
                                        @click="showSecret = !showSecret"
                                    >
                                        <EyeOffIcon
                                            v-if="showSecret"
                                            class="size-4"
                                        />
                                        <EyeIcon v-else class="size-4" />
                                    </button>
                                </div>
                                <FieldDescription>
                                    {{ secretHelp }}
                                </FieldDescription>
                                <FieldError
                                    :errors="
                                        credentialError('secret')
                                            ? [credentialError('secret')!]
                                            : undefined
                                    "
                                />
                            </Field>
                        </template>

                        <!-- Custom : JSON libre valide cote client -->
                        <template v-else>
                            <Field>
                                <FieldLabel for="credentials_json">
                                    Credentials (JSON)
                                </FieldLabel>
                                <Textarea
                                    id="credentials_json"
                                    name="credentials_json"
                                    v-model="form.credentials_json"
                                    class="min-h-24 font-mono text-sm"
                                    spellcheck="false"
                                    :placeholder="jsonPlaceholder"
                                />
                                <FieldDescription>
                                    {{ jsonHelp }}
                                </FieldDescription>
                                <FieldError
                                    :errors="
                                        (serverErrors.credentials ??
                                        serverErrors.credentials_json)
                                            ? [
                                                  serverErrors.credentials ??
                                                      serverErrors.credentials_json,
                                              ]
                                            : undefined
                                    "
                                />
                            </Field>
                        </template>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-2">
                    <Button as-child variant="outline">
                        <a :href="index.url()">Annuler</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="size-4" />
                        {{
                            isEdit
                                ? 'Enregistrer les modifications'
                                : 'Créer le connecteur'
                        }}
                    </Button>
                </div>
            </form>
        </div>
    </BackLayout>
</template>
