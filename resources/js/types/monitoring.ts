/**
 * Types du module Monitoring : moniteurs de santé (connecteurs, gateway,
 * webhooks), journal des contrôles et alertes.
 *
 * Aucun type client ne porte de donnée sensible : les messages d'erreur
 * (`message`) transportés jusqu'à l'interface sont sûrs, sans secret ni
 * stack trace, et la cible d'un moniteur n'est jamais qu'un résumé
 * lisible (`targetSummary`) ou un identifiant de connecteur.
 */

/** Ce que supervise un moniteur. */
export type MonitorType = 'connector' | 'gateway' | 'webhook';

/** Libellés français des types de moniteurs. */
export const MONITOR_TYPE_LABELS: Record<MonitorType, string> = {
    connector: 'Connecteur',
    gateway: 'Gateway',
    webhook: 'Webhook',
};

/** Issue d'un contrôle de santé journalisé par le domaine. */
export type MonitorStatus = 'up' | 'down' | 'degraded';

/** Libellés français des statuts de contrôle. */
export const MONITOR_STATUS_LABELS: Record<MonitorStatus, string> = {
    up: 'Opérationnel',
    degraded: 'Dégradé',
    down: 'Hors ligne',
};

/** Variante de badge de la charte associée à chaque statut. */
export const MONITOR_STATUS_VARIANTS: Record<
    MonitorStatus,
    'success' | 'warning' | 'danger'
> = {
    up: 'success',
    degraded: 'warning',
    down: 'danger',
};

/** Libellé français d'un statut, « Jamais contrôlé » si aucun contrôle. */
export function monitorStatusLabel(status: MonitorStatus | null): string {
    return status === null ? 'Jamais contrôlé' : MONITOR_STATUS_LABELS[status];
}

/** Niveau de gravité d'une alerte. */
export type MonitorAlertLevel = 'warning' | 'critical';

/** Alerte active (non résolue) rattachée à un moniteur. */
export type MonitorAlert = {
    id: number;
    level: MonitorAlertLevel;
    /** Message sûr : sans secret ni donnée sensible. */
    message: string;
    createdAt: string;
};

/** Point de sparkline : latence mesurée, null si le contrôle a échoué. */
export type SparklinePoint = {
    latencyMs: number | null;
};

/**
 * Moniteur tel qu'affiché sur le dashboard : état courant, courte
 * histoire des latences (ordre chronologique, ~20 derniers contrôles)
 * et alertes actives à résoudre.
 */
export type MonitorCard = {
    id: number;
    name: string;
    type: MonitorType;
    active: boolean;
    latencyThresholdMs: number | null;
    /** Résumé lisible de la cible (nom du connecteur, URL, etc.). */
    targetSummary: string;
    /** État du dernier contrôle, null si le moniteur n'a jamais tourné. */
    status: MonitorStatus | null;
    latencyMs: number | null;
    checkedAt: string | null;
    /** Message d'erreur sûr du dernier contrôle. */
    message: string | null;
    sparkline: SparklinePoint[];
    alerts: MonitorAlert[];
};

/** Moniteur tel que listé dans la table de gestion. */
export type MonitorListItem = {
    id: number;
    name: string;
    type: MonitorType;
    active: boolean;
    latencyThresholdMs: number | null;
    targetSummary: string;
    lastStatus: MonitorStatus | null;
    lastCheckedAt: string | null;
};

/**
 * Moniteur tel que chargé dans le formulaire d'édition : identité et
 * configuration, avec la cible réduite à ses identifiants (jamais de
 * credential : le moniteur s'appuie sur les données existantes).
 */
export type MonitorForm = {
    id: number;
    name: string;
    type: MonitorType;
    active: boolean;
    latencyThresholdMs: number | null;
    target: {
        connectorId?: number;
    };
};

/** Contrôle tel que journalisé dans l'historique. */
export type CheckItem = {
    id: number;
    monitorName: string;
    type: MonitorType;
    status: MonitorStatus;
    latencyMs: number | null;
    /** Message sûr porté par le contrôle (erreur, note de dégradation). */
    message: string | null;
    checkedAt: string;
};

/** Filtres de l'historique des contrôles, tels que reçus en props. */
export type MonitoringHistoryFilters = {
    type?: string | null;
    status?: string | null;
    days?: number | null;
};

/** Connecteur réduit à l'identité, pour le choix de cible du formulaire. */
export type MonitorConnectorOption = {
    id: number;
    name: string;
};
