/**
 * Types du module Pricing : plans tarifaires, tables de prix éditables et
 * formes sérialisées échangées avec le backend. Tous les prix sont
 * transportés en CENTIMES (entiers) ; la conversion euros <-> centimes est
 * du ressort de l'interface.
 */

/** Plan tarifaire, tel que listé dans le back-office et édité au formulaire. */
export type PricingPlan = {
    id: number;
    slug: string;
    name: string;
    tagline: string;
    priceMonthly: number;
    priceYearly: number;
    currency: string;
    features: string[];
    popular: boolean;
    active: boolean;
    sort: number;
};

/** Options compactes d'un plan (sélection dans le formulaire de table). */
export type PricingPlanOption = {
    id: number;
    name: string;
    active: boolean;
};

/** Ligne du tableau back-office (liste compacte des tables de prix). */
export type PricingTableListItem = {
    id: number;
    slug: string;
    title: string;
    subtitle: string;
    publishedAt: string | null;
    plansCount: number;
    faqCount: number;
};

/** Entrée de FAQ d'une table de prix. */
export type FaqItem = {
    question: string;
    answer: string;
};

/** Table de prix telle qu'éditée dans le formulaire back-office. */
export type PricingTableForm = {
    id: number;
    slug: string;
    title: string;
    subtitle: string;
    faq: FaqItem[];
    publishedAt: string | null;
    planIds: number[];
};

/** Plan tel qu'affiché côté front-office (page tarifs publique). */
export type PricingShowPlan = {
    slug: string;
    name: string;
    tagline: string;
    priceMonthly: number;
    priceYearly: number;
    currency: string;
    features: string[];
    popular: boolean;
};

/** Page tarifs publique rendue côté front-office. */
export type PricingShow = {
    slug: string;
    title: string;
    subtitle: string;
    plans: PricingShowPlan[];
    faq: FaqItem[];
};
