import type { User } from './auth';
import type { ApiKey } from './apiKey';
import type {
    ConnectorForm,
    ConnectorListItem,
    ConnectorTestResult,
} from './connector';
import type {
    LandingPageForm,
    LandingPageListItem,
    LandingShow,
} from './landing';
import type { Module, SharedModule } from './module';
import type {
    PricingPlan,
    PricingPlanOption,
    PricingShow,
    PricingTableForm,
    PricingTableListItem,
} from './pricing';
import type {
    WebhookDelivery,
    WebhookDeliveryDetail,
    WebhookDeliveryFilters,
    WebhookEndpoint,
    WebhookEndpointForm,
    WebhookEndpointOption,
} from './webhook';

/**
 * Props partagees par toutes les pages Inertia, telles que sérialisées par
 * le middleware HandleInertiaRequests (aligné sur le contrat backend).
 */
export type AppSharedProps = {
    auth: {
        user: User | null;
    };
    modules: SharedModule[];
};

/** Props de la page `front/Landing/Show` (page vitrine publique). */
export type FrontLandingShowPageProps = AppSharedProps & {
    landing: LandingShow;
};

/** Props de la page `back/Landing/Index` (liste des landing pages). */
export type BackLandingIndexPageProps = AppSharedProps & {
    landingPages: LandingPageListItem[];
};

/** Props de la page `back/Landing/Form` (création et édition). */
export type BackLandingFormPageProps = AppSharedProps & {
    landingPage?: LandingPageForm;
};

/**
 * Props de la page `back/Connectors/Index` (liste des connecteurs).
 * Le résultat du dernier test de connexion transite exclusivement par le
 * flash de session partagé sous `flash.testResult` : consommé à l'écran,
 * il n'existe dans aucun autre type.
 */
export type BackConnectorsIndexPageProps = AppSharedProps & {
    connectors: ConnectorListItem[];
    flash?: { testResult?: ConnectorTestResult | null };
};

/** Props de la page `back/Connectors/Form` (création et édition). */
export type BackConnectorsFormPageProps = AppSharedProps & {
    connector?: ConnectorForm;
};

/** Props de la page `front/Pricing/Show` (page tarifs publique). */
export type FrontPricingShowPageProps = AppSharedProps & {
    table: PricingShow;
};

/** Props de la page `back/Pricing/Plans` (liste des plans tarifaires). */
export type BackPricingPlansPageProps = AppSharedProps & {
    plans: PricingPlan[];
};

/** Props de la page `back/Pricing/PlanForm` (création et édition). */
export type BackPricingPlanFormPageProps = AppSharedProps & {
    plan?: PricingPlan;
};

/** Props de la page `back/Pricing/Tables` (liste des tables de prix). */
export type BackPricingTablesPageProps = AppSharedProps & {
    tables: PricingTableListItem[];
};

/**
 * Props de la page `back/Gateway/Keys` (gestion des clés d'API).
 * Le secret fraîchement créé transite exclusivement par le flash de
 * session partagé sous `flash.secret` par le middleware : il n'est
 * disponible qu'une seule fois et n'existe dans aucun autre type.
 */
export type BackGatewayKeysPageProps = AppSharedProps & {
    keys: ApiKey[];
    flash?: { secret?: string };
};

/** Props de la page `back/Pricing/TableForm` (création et édition). */
export type BackPricingTableFormPageProps = AppSharedProps & {
    table?: PricingTableForm;
    plans: PricingPlanOption[];
};

/**
 * Props de la page `back/Webhooks/Endpoints` (liste des endpoints sortants).
 * Le secret fraîchement créé transite exclusivement par le flash de session
 * partagé sous `flash.secret` par le middleware : il n'est disponible qu'une
 * seule fois et n'existe dans aucun autre type.
 */
export type BackWebhooksEndpointsPageProps = AppSharedProps & {
    endpoints: WebhookEndpoint[];
    flash?: { secret?: string };
};

/**
 * Props de la page `back/Webhooks/EndpointForm` (création et édition).
 * Le secret n'est jamais rapatrié : la régénération renvoie le nouveau
 * secret une seule fois, via le flash `flash.secret`.
 */
export type BackWebhooksEndpointFormPageProps = AppSharedProps & {
    endpoint?: WebhookEndpointForm;
    flash?: { secret?: string };
};

/**
 * Props de la page `back/Webhooks/Deliveries` (journal des deliveries).
 * La liste des evenements filtrables arrive sous `eventsDisponibles`
 * (contrat) ou `events` (controleur) selon la version du backend.
 */
export type BackWebhooksDeliveriesPageProps = AppSharedProps & {
    deliveries: WebhookDelivery[];
    filters: WebhookDeliveryFilters;
    endpoints: WebhookEndpointOption[];
    eventsDisponibles?: string[];
    events?: string[];
};

/** Props de la page `back/Webhooks/Delivery` (détail d'une delivery). */
export type BackWebhooksDeliveryPageProps = AppSharedProps & {
    delivery: WebhookDeliveryDetail;
};

/** Props de la page `back/Login` (écran de connexion Fortify). */
export type BackLoginPageProps = AppSharedProps & {
    canResetPassword: boolean;
    status?: string;
};

/** Props de la page `back/ForgotPassword` (demande de réinitialisation). */
export type BackForgotPasswordPageProps = AppSharedProps & {
    status?: string;
};

/** Props de la page `back/ResetPassword` (nouveau mot de passe). */
export type BackResetPasswordPageProps = AppSharedProps & {
    email: string;
    token: string;
    passwordRules: string;
};

/** Props de la page `back/VerifyEmail` (notice de vérification d'email). */
export type BackVerifyEmailPageProps = AppSharedProps & {
    status?: string;
};

/** Props de la page `back/Dashboard` (accueil du back-office). */
export type BackDashboardPageProps = AppSharedProps;

/** Props de la page `back/Modules` (gestion des modules de la plateforme). */
export type BackModulesPageProps = AppSharedProps & {
    modules: Module[];
};

/** Props de la page `back/Profile` (identité + mot de passe). */
export type BackProfilePageProps = AppSharedProps & {
    mustVerifyEmail: boolean;
    status?: string;
};
