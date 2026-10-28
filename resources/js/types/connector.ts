/**
 * Types du module Connectors : intégrations externes (WordPress, Caren,
 * Erpen, services libres) administrées depuis le back-office.
 *
 * Aucun type client ne porte de champ `credentials` ni `secret` : les
 * secrets sont chiffrés au repos côté serveur et jamais renvoyés au
 * navigateur. Les valeurs saisies dans le formulaire ne transitent que
 * dans la charge utile de création / mise à jour, construite à la
 * soumission (voir back/pages/Connectors/Form.vue).
 */

/** Types de connecteurs reconnus par le domaine. */
export type ConnectorType = 'wordpress' | 'caren' | 'erpen' | 'custom';

/**
 * État du connecteur, mis à jour par le dernier test de connexion :
 * `connected` (jointoire atteinte), `error` (échec, message sûr)
 * ou `unknown` (jamais testé).
 */
export type ConnectorStatus = 'connected' | 'error' | 'unknown';

/** Connecteur tel que listé dans le tableau back-office. */
export type ConnectorListItem = {
    id: number;
    name: string;
    type: ConnectorType;
    baseUrl: string;
    status: ConnectorStatus;
    /** Dernier test réussi ou échoué, horodatage ISO. */
    lastTestedAt: string | null;
    /** Message d'échec sûr : sans secret ni stack trace. */
    lastError: string | null;
    createdAt: string;
};

/**
 * Connecteur tel que chargé dans le formulaire d'édition : identité et
 * adresse uniquement, jamais les credentials (masqués par des
 * placeholders dans l'interface, conservés si les champs restent vides).
 */
export type ConnectorForm = {
    id: number;
    name: string;
    type: ConnectorType;
    baseUrl: string;
};

/**
 * Résultat d'un test de connexion, renvoyé en flash (`flash.testResult`)
 * après la redirection qui suit `POST /admin/connectors/{connector}/test`.
 * Le message d'échec est sûr : il ne contient ni secret ni stack trace.
 * `unknown` n'est pas un échec : c'est l'accusé de réception d'un test
 * parti en file d'attente (driver non sync), dont le résultat réel
 * apparaîtra dans la liste à la prochaine visite.
 */
export type ConnectorTestResult = {
    status: 'connected' | 'error' | 'unknown';
    latencyMs: number;
    message: string | null;
};
