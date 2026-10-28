import type { User } from './auth';
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

/** Props de la page `back/Pricing/TableForm` (création et édition). */
export type BackPricingTableFormPageProps = AppSharedProps & {
    table?: PricingTableForm;
    plans: PricingPlanOption[];
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
