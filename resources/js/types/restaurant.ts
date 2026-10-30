export type MenuItem = {
    title: string;
    href: string;
    icon: string | null;
    /** Subitems anidados (por ejemplo, cada catálogo bajo Parámetros). */
    children?: MenuItem[];
};

export type MenuModule = {
    title: string;
    icon: string | null;
    items: MenuItem[];
};

export type SharedCompany = {
    id: number;
    name: string;
    code: string;
};

export type SharedRole = {
    code: string;
    name: string;
};
