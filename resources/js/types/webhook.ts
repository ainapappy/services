/**
 * Types du module Webhooks : endpoints sortants administres depuis le
 * back-office et journal des deliveries (tentatives, reponses, erreurs).
 *
 * Aucun type client ne porte de champ `secret` : le serveur conserve le
 * secret chiffre (il sert a signer les deliveries) et ne le renvoie jamais
 * apres sa creation ou sa regeneration (il transite uniquement par la prop
 * flash `secret` des pages d'index et de formulaire).
 */

/**
 * Evenements du domaine diffusables vers les endpoints, dans l'ordre du
 * registre backend (liste extensible cote serveur).
 */
export const WEBHOOK_EVENTS = [
    'landing.published',
    'pricing.published',
    'connector.status_changed',
] as const;

/** Nom canonique d'un evenement webhook connu du registre. */
export type WebhookEventName = (typeof WEBHOOK_EVENTS)[number];

/** Libelles francais des evenements canoniques. */
export const WEBHOOK_EVENT_LABELS: Record<WebhookEventName, string> = {
    'landing.published': 'Landing publiée',
    'pricing.published': 'Pricing publié',
    'connector.status_changed': 'Statut de connecteur modifié',
};

/** Descriptions francaises des evenements (cases a cocher du formulaire). */
export const WEBHOOK_EVENT_DESCRIPTIONS: Record<WebhookEventName, string> = {
    'landing.published':
        'Une landing page vient d’être publiée sur la vitrine.',
    'pricing.published':
        'Une table de prix vient d’être publiée sur la page tarifs.',
    'connector.status_changed':
        'Le statut d’un connecteur a changé (connecté, erreur…).',
};

/** Libelle francais d'un evenement, brut si inconnu du registre client. */
export function webhookEventLabel(event: string): string {
    return (WEBHOOK_EVENT_LABELS as Record<string, string>)[event] ?? event;
}

/** Statut d'une delivery, tel que journalise par le domaine. */
export type WebhookDeliveryStatus = 'pending' | 'success' | 'failed';

/** Libelles francais des statuts de delivery. */
export const WEBHOOK_DELIVERY_STATUS_LABELS: Record<
    WebhookDeliveryStatus,
    string
> = {
    pending: 'En attente',
    success: 'Succès',
    failed: 'Échec',
};

/** Endpoint sortant tel que liste dans le back-office. */
export type WebhookEndpoint = {
    id: number;
    url: string;
    /** Noms canoniques des evenements souscrits. */
    events: string[];
    active: boolean;
    /** Nombre de deliveries journalisees pour cet endpoint. */
    deliveriesCount: number;
    createdAt: string;
};

/** Endpoint tel que charge dans le formulaire d'edition (sans secret). */
export type WebhookEndpointForm = {
    id: number;
    url: string;
    events: string[];
    active: boolean;
};

/** Delivery telle que listee dans le journal. */
export type WebhookDelivery = {
    id: number;
    /** Nom canonique de l'evenement diffuse. */
    event: string;
    status: WebhookDeliveryStatus;
    attempts: number;
    responseCode: number | null;
    durationMs: number | null;
    createdAt: string;
    /** URL de l'endpoint vise, denormalisee pour l'affichage. */
    endpointUrl: string;
    deliveredAt: string | null;
};

/** Delivery telle que chargee dans la page de detail (payload inclus). */
export type WebhookDeliveryDetail = {
    id: number;
    event: string;
    status: WebhookDeliveryStatus;
    attempts: number;
    /** Charge utile JSON transmise a l'endpoint. */
    payload: unknown;
    responseCode: number | null;
    /** Message d'echec sur : sans secret ni donnee sensible. */
    error: string | null;
    durationMs: number | null;
    createdAt: string;
    deliveredAt: string | null;
    endpointUrl: string;
};

/** Filtres du journal des deliveries, tels que recus en props de page. */
export type WebhookDeliveryFilters = {
    status?: string | null;
    event?: string | null;
    endpointId?: number | null;
};

/**
 * Contexte d'un secret fraichement affiche : creation d'un endpoint ou
 * regeneration du secret (message d'avertissement different). Le contexte
 * traverse la redirection du formulaire vers la liste via le stockage de
 * session, le flash `secret` ne portant que la valeur elle-meme.
 */
export type WebhookSecretOrigin = 'creation' | 'regeneration';

export const WEBHOOK_SECRET_ORIGIN_KEY = 'webhooks.secret-origin';

/** Endpoint reduit a l'identite, pour le filtre du journal. */
export type WebhookEndpointOption = {
    id: number;
    url: string;
};
