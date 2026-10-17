import type { Auth } from '@/types/auth';
import type { MenuModule, SharedCompany, SharedRole } from '@/types/restaurant';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            company: SharedCompany | null;
            role: SharedRole | null;
            companies: SharedCompany[];
            companiesCount: number;
            menu: MenuModule[];
            /** null = sin restricciones (ADMIN / SOPORTE) */
            permissions: string[] | null;
            [key: string]: unknown;
        };
    }
}
