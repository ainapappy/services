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
import BackLayout from '../../layouts/BackLayout.vue';
import BackButton from '../../components/BackButton.vue';
import BackCard from '../../components/BackCard.vue';
import BackField from '../../components/BackField.vue';
import BackInput from '../../components/BackInput.vue';
import BackStatus from '../../components/BackStatus.vue';
import BackToggle from '../../components/BackToggle.vue';

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
            <header class="flex flex-col gap-1">
                <p class="text-sm text-[var(--color-muted)]">
                    <a :href="index.url()" class="back-link"> Moniteurs </a>
                    <span class="mx-1">/</span>
                    <span>{{
                        isEdit ? existing?.name : 'Nouveau moniteur'
                    }}</span>
                </p>
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    {{ isEdit ? 'Modifier le moniteur' : 'Nouveau moniteur' }}
                </h1>
            </header>

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <!-- Identite et cible -->
                <BackCard
                    title="Moniteur"
                    description="Cible supervisée périodiquement par la commande de contrôle (toutes les 5 minutes)."
                >
                    <div class="flex flex-col gap-5">
                        <BackField
                            label="Nom"
                            field-id="name"
                            :error="form.errors.name"
                            help="Ex. Blog vitrine, Gateway production, Webhooks sortants."
                        >
                            <BackInput
                                id="name"
                                name="name"
                                v-model="form.name"
                                required
                                placeholder="Ex. Blog vitrine"
                            />
                        </BackField>

                        <BackField
                            label="Type"
                            field-id="type"
                            :error="form.errors.type"
                            help="Le champ de cible s'adapte au type choisi."
                        >
                            <select
                                id="type"
                                name="type"
                                v-model="form.type"
                                class="back-input back-select"
                            >
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <p class="back-help mt-1">
                                {{
                                    typeOptions.find(
                                        (option) => option.value === form.type,
                                    )?.description
                                }}
                            </p>
                        </BackField>

                        <!-- Cible adaptative : connecteur ->
                             select parmi les connecteurs existants -->
                        <BackField
                            v-if="form.type === 'connector'"
                            label="Connecteur supervisé"
                            field-id="connector_id"
                            :error="targetError()"
                            help="Le moniteur réutilise les credentials enregistrés pour tester la connexion."
                        >
                            <select
                                id="connector_id"
                                name="connector_id"
                                v-model="form.connector_id"
                                class="back-input back-select"
                            >
                                <option value="">
                                    Sélectionnez un connecteur…
                                </option>
                                <option
                                    v-for="connector in props.connectors"
                                    :key="connector.id"
                                    :value="String(connector.id)"
                                >
                                    {{ connector.name }}
                                </option>
                            </select>
                        </BackField>

                        <!-- Cible adaptative : gateway -> texte explicatif -->
                        <BackStatus v-else-if="form.type === 'gateway'">
                            Cible fixe : la gateway est vérifiée via son
                            endpoint interne de santé (<code
                                class="font-mono text-xs"
                                >GET /api/v1/health</code
                            >). Aucune configuration supplémentaire n'est
                            nécessaire.
                        </BackStatus>

                        <!-- Cible adaptative : webhooks -> texte explicatif -->
                        <BackStatus v-else>
                            Cible fixe : la santé est déduite des deliveries
                            récentes des endpoints webhooks actifs (échecs ou
                            tentatives en souffrance). Aucune configuration
                            supplémentaire n'est nécessaire.
                        </BackStatus>
                    </div>
                </BackCard>

                <!-- Regles de controle -->
                <BackCard
                    title="Règles de contrôle"
                    description="Fréquence, seuil de latence et activation."
                >
                    <div class="flex flex-col gap-5">
                        <BackField
                            label="Seuil de latence (ms)"
                            field-id="latency_threshold_ms"
                            :error="form.errors.latency_threshold_ms"
                            help="Au-dessus de ce seuil, le contrôle passe en dégradé. Laissez vide pour ne pas tenir compte de la latence."
                        >
                            <BackInput
                                id="latency_threshold_ms"
                                name="latency_threshold_ms"
                                type="number"
                                min="0"
                                step="1"
                                v-model="form.latency_threshold_ms"
                                placeholder="Ex. 2000"
                            />
                        </BackField>

                        <div class="flex items-center gap-3">
                            <BackToggle
                                :checked="form.active"
                                label="Moniteur actif"
                                @toggle="form.active = !form.active"
                            />
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-medium text-[var(--color-ink)]"
                                >
                                    {{ form.active ? 'Actif' : 'Inactif' }}
                                </span>
                                <span class="text-xs text-[var(--color-muted)]">
                                    Un moniteur inactif n'est plus contrôlé ;
                                    ses contrôles et alertes passés restent
                                    consultables dans l'historique.
                                </span>
                            </div>
                            <p v-if="form.errors.active" class="back-error">
                                {{ form.errors.active }}
                            </p>
                        </div>
                    </div>
                </BackCard>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a :href="index.url()" class="back-btn back-btn--secondary">
                        Annuler
                    </a>
                    <BackButton type="submit" :loading="form.processing">
                        {{
                            isEdit
                                ? 'Enregistrer les modifications'
                                : 'Créer le moniteur'
                        }}
                    </BackButton>
                </div>
            </form>
        </section>
    </BackLayout>
</template>
