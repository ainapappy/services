/**
 * Types du module API Gateway : clés d'accès administrées depuis le
 * back-office et exposées sur les endpoints `/api/v1/*`.
 *
 * Aucun type client ne porte de champ `secret` : le serveur ne conserve que
 * le hash de la clé et ne renvoie jamais le secret après sa création (il
 * transite uniquement par la prop flash `secret` de la page d'index).
 */

/** Abilities reconnues par le middleware `api.key`, dans l'ordre du registre. */
export const API_KEY_ABILITIES = [
    'services:read',
    'landings:read',
    'connectors:read',
] as const;

/** Ability reconnue par le domaine Gateway. */
export type ApiKeyAbility = (typeof API_KEY_ABILITIES)[number];

/**
 * Statut d'une clé, calculé côté client : `revoked` l'emporte sur
 * `expired`, et l'expiration se déduit de `expiresAt` (jamais renvoyée
 * telle quelle par le serveur).
 */
export type ApiKeyStatus = 'active' | 'revoked' | 'expired';

/** Clé d'API telle que listée dans le back-office (préfixe affichable). */
export type ApiKey = {
    id: number;
    name: string;
    /** 8 premiers caractères du secret, seuls affichables. */
    prefix: string;
    abilities: string[];
    lastUsedAt: string | null;
    revokedAt: string | null;
    expiresAt: string | null;
    createdAt: string;
};

/**
 * Charge utile de création d'une clé, telle qu'envoyée au backend
 * (champs snake_case attendus par la requête de validation).
 */
export type ApiKeyForm = {
    name: string;
    abilities: string[];
    expires_at: string | null;
};
