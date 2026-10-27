import type { User } from './auth';

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

/** Props de la page `back/Home` (tableau de bord admin). */
export type BackHomePageProps = AppSharedProps;
