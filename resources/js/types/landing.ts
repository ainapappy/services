/**
 * Types du module Landing : contenu éditable d'une page (héros, CTA, SEO)
 * et formes sérialisées échangées avec le backend.
 */

/** Style visuel d'un bouton d'appel à l'action. */
export type CtaStyle = 'primary' | 'secondary';

/** Bouton d'appel à l'action d'une landing page. */
export type Cta = {
    label: string;
    url: string;
    style: CtaStyle;
};

/** Métadonnées de référencement d'une landing page. */
export type Seo = {
    title: string;
    description: string;
};

/** Ligne du tableau back-office (liste compacte des landing pages). */
export type LandingPageListItem = {
    id: number;
    slug: string;
    title: string;
    isHome: boolean;
    publishedAt: string | null;
    ctasCount: number;
};

/** Page landing telle qu'éditée dans le formulaire back-office. */
export type LandingPageForm = {
    id: number;
    slug: string;
    title: string;
    heroTitle: string;
    heroSubtitle: string;
    ctas: Cta[];
    seo: Seo;
    isHome: boolean;
    publishedAt: string | null;
};

/** Page publique rendue côté front-office. */
export type LandingShow = {
    slug: string;
    title: string;
    heroTitle: string;
    heroSubtitle: string;
    ctas: Cta[];
    seo: Seo;
};
