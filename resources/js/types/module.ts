/**
 * Un module métier de la plateforme, tel que décrit par le registre PHP
 * (`App\Core\Modules\ModuleRegistry`) et sérialisé par le back-office.
 */
export type Module = {
    slug: string;
    label: string;
    description: string;
    enabled: boolean;
};
