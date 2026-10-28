<script setup lang="ts">
// Formulaire de creation / edition d'un moniteur : nom, type (connecteur,
// gateway, webhook) et champ de cible ADAPTE au type (select de connecteur
// pour `connector`, texte explicatif pour gateway et webhooks dont la
// cible est fixe), seuil de latence optionnel (au-dela : controle
// degrade) et bascule d'activation. Aucun import hors arbre back/.
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import type { BackMonitoringMonitorFormPageProps, MonitorType } from '@/types';
import { index, store, update } from '@/routes/admin/monitoring/monitors';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldContent,
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
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';

const props = defineProps<BackMonitoringMonitorFormPageProps>();

/* ------------------------------------------------------------------ */
/* Etat edite                                                          */
/* ------------------------------------------------------------------ */

const existing = computed(() => props.monitor ?? null);
const isEdit = computed(() => existing.value !== null);

const typeOptions: Array<{
    value: MonitorType;
    label: string;
    description: string;
}> = [
    {
        value: 'connector',
        label: 'Connecteur',
        description:
            'Surveille un connecteur via son test de connexion (credentials déjà enregistrés).',
    },
    {
        value: 'gateway',
        label: 'Gateway',
        description: 'Surveille la gateway via son endpoint interne de santé.',
    },
    {
        value: 'webhook',
        label: 'Webhook',
        description:
            'Surveille les webhooks via leurs deliveries récentes (échecs détectés).',
    },
];

type MonitorFormData = {
    name: string;
    type: MonitorType;
    connector_id: string;
    latency_threshold_ms: string;
    active: boolean;
};

function toFormData(value: typeof existing.value): MonitorFormData {
    return {
        name: value?.name ?? '',
        type: value?.type ?? 'connector',
        connector_id: value?.target.connectorId
            ? String(value.target.connectorId)
            : '',
        latency_threshold_ms:
            value?.latencyThresholdMs === null ||
            value?.latencyThresholdMs === undefined
                ? ''
                : String(value.latencyThresholdMs),
        active: value?.active ?? true,
    };
}

const form = useForm<MonitorFormData>(toFormData(existing.value));

/* Navigation creation -> edition (meme composant reutilise par Inertia) :
   on resynchronise le formulaire quand la page cible change. */
watch(
    () => props.monitor?.id,
    () => {
        const next = toFormData(existing.value);
        form.name = next.name;
        form.type = next.type;
        form.connector_id = next.connector_id;
        form.latency_threshold_ms = next.latency_threshold_ms;
        form.active = next.active;
        form.clearErrors();
    },
);

/* Changement de type : l'erreur de cible (client) ne s'applique plus. */
watch(
    () => form.type,
    () => {
        form.clearErrors('connector_id');
    },
);

/* ------------------------------------------------------------------ */
/* Erreurs de cible (cle imbriquee ou plate selon le backend)           */
/* ------------------------------------------------------------------ */

const serverErrors = computed(
    () => form.errors as Partial<Record<string, string>>,
);

function targetError(): string | undefined {
    return (
        form.errors.connector_id ??
        serverErrors.value['target.connectorId'] ??
        serverErrors.value.target ??
        serverErrors.value.target_connector_id
    );
}

/* ------------------------------------------------------------------ */
/* Soumission                                                           */
/* ------------------------------------------------------------------ */

/** Valide la cible client-side pour le type connecteur. */
function isTargetValid(): boolean {
    if (form.type !== 'connector') {
        return true;
    }

    if (form.connector_id === '') {
        form.setError(
            'connector_id',
            'Sélectionnez le connecteur à superviser.',
        );
        return false;
    }

    return true;
}

function onSubmit(): void {
    if (!isTargetValid()) {
        return;
    }

    form.transform((data) => {
        const payload: Record<string, unknown> = {
            name: data.name,
            type: data.type,
            active: data.active,
            latency_threshold_ms:
                data.latency_threshold_ms.trim() === ''
                    ? null
                    : Math.round(Number(data.latency_threshold_ms)),
        };

        /* Seuls les moniteurs de connecteurs portent une cible choisie :
           gateway et webhooks ont une cible fixe cote serveur. Le backend
           attend la cle camelCase `target.connectorId`. */
        if (data.type === 'connector') {
            payload.target = { connectorId: Number(data.connector_id) };
        }

        return payload;
    });

    if (isEdit.value && existing.value !== null) {
        void form.patch(update({ monitor: existing.value.id }).url);
        return;
    }

    void form.post(store.url());
}
</script>

<template>
    <BackLayout>
        <Head :title="isEdit ? 'Modifier le moniteur' : 'Nouveau moniteur'" />

        <section class="flex flex-col gap-6">
            <BackPageHeader
                :title="isEdit ? 'Modifier le moniteur' : 'Nouveau moniteur'"
            />

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <Card>
                    <!-- Identite et cible -->
                    <CardHeader>
                        <CardTitle>Moniteur</CardTitle>
                        <CardDescription>
                            Cible supervisée périodiquement par la commande de
                            contrôle (toutes les 5 minutes).
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-5">
                        <Field>
                            <FieldLabel for="name">Nom</FieldLabel>
                            <Input
                                id="name"
                                name="name"
                                v-model="form.name"
                                required
                                placeholder="Ex. Blog vitrine"
                            />
                            <FieldDescription>
                                Ex. Blog vitrine, Gateway production, Webhooks
                                sortants.
                            </FieldDescription>
                            <FieldError :errors="[form.errors.name]" />
                        </Field>

                        <Field>
                            <FieldLabel for="type">Type</FieldLabel>
                            <Select v-model="form.type">
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
                                Le champ de cible s'adapte au type choisi.
                            </FieldDescription>
                            <FieldDescription>
                                {{
                                    typeOptions.find(
                                        (option) => option.value === form.type,
                                    )?.description
                                }}
                            </FieldDescription>
                            <FieldError :errors="[form.errors.type]" />
                        </Field>

                        <!-- Cible adaptative : connecteur ->
                             select parmi les connecteurs existants -->
                        <Field v-if="form.type === 'connector'">
                            <FieldLabel for="connector_id">
                                Connecteur supervisé
                            </FieldLabel>
                            <Select v-model="form.connector_id">
                                <SelectTrigger id="connector_id" class="w-full">
                                    <SelectValue
                                        placeholder="Sélectionnez un connecteur…"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="connector in props.connectors"
                                        :key="connector.id"
                                        :value="String(connector.id)"
                                    >
                                        {{ connector.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldDescription>
                                Le moniteur réutilise les credentials
                                enregistrés pour tester la connexion.
                            </FieldDescription>
                            <FieldError :errors="[targetError()]" />
                        </Field>

                        <!-- Cible adaptative : gateway -> texte explicatif -->
                        <Alert v-else-if="form.type === 'gateway'">
                            <AlertDescription>
                                Cible fixe : la gateway est vérifiée via son
                                endpoint interne de santé (<code
                                    class="font-mono text-xs"
                                    >GET /api/v1/health</code
                                >). Aucune configuration supplémentaire n'est
                                nécessaire.
                            </AlertDescription>
                        </Alert>

                        <!-- Cible adaptative : webhooks -> texte explicatif -->
                        <Alert v-else>
                            <AlertDescription>
                                Cible fixe : la santé est déduite des deliveries
                                récentes des endpoints webhooks actifs (échecs
                                ou tentatives en souffrance). Aucune
                                configuration supplémentaire n'est nécessaire.
                            </AlertDescription>
                        </Alert>
                    </CardContent>

                    <Separator />

                    <!-- Regles de controle -->
                    <CardContent class="flex flex-col gap-5">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-base leading-none font-medium">
                                Règles de contrôle
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Fréquence, seuil de latence et activation.
                            </p>
                        </div>

                        <Field>
                            <FieldLabel for="latency_threshold_ms">
                                Seuil de latence (ms)
                            </FieldLabel>
                            <Input
                                id="latency_threshold_ms"
                                name="latency_threshold_ms"
                                type="number"
                                min="0"
                                step="1"
                                v-model="form.latency_threshold_ms"
                                placeholder="Ex. 2000"
                            />
                            <FieldDescription>
                                Au-dessus de ce seuil, le contrôle passe en
                                dégradé. Laissez vide pour ne pas tenir compte
                                de la latence.
                            </FieldDescription>
                            <FieldError
                                :errors="[form.errors.latency_threshold_ms]"
                            />
                        </Field>

                        <Field orientation="horizontal">
                            <Switch id="active" v-model:checked="form.active" />
                            <FieldContent>
                                <FieldLabel for="active">
                                    Surveillance active
                                </FieldLabel>
                                <FieldDescription>
                                    {{ form.active ? 'Actif' : 'Inactif' }} — Un
                                    moniteur inactif n'est plus contrôlé ; ses
                                    contrôles et alertes passés restent
                                    consultables dans l'historique.
                                </FieldDescription>
                                <FieldError :errors="[form.errors.active]" />
                            </FieldContent>
                        </Field>
                    </CardContent>

                    <!-- Actions -->
                    <CardFooter class="justify-end gap-3 border-t pt-6">
                        <Button variant="outline" as-child>
                            <a :href="index.url()">Annuler</a>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" class="size-4" />
                            {{
                                isEdit
                                    ? 'Enregistrer les modifications'
                                    : 'Créer le moniteur'
                            }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </section>
    </BackLayout>
</template>
