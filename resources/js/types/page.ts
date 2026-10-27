import type { User } from './auth';
import type { Module } from './module';

/**
 * Props partagees par toutes les pages Inertia, telles que sérialisées par
 * le middleware HandleInertiaRequests (aligné sur le contrat backend).
 */
export type AppSharedProps = {
    auth: {
        user: User | null;
    };
};

/** Props de la page `front/Home` (vitrine publique). */
export type FrontHomePageProps = AppSharedProps;

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
