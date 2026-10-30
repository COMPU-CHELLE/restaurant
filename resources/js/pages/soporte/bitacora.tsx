import { AuditLog } from '@/components/audit-log';
import type { AuditFilters, AuditRow } from '@/components/audit-log';
import type { Paginated } from '@/types/pagination';

type Props = {
    entries: Paginated<AuditRow>;
    filters: AuditFilters;
    options: {
        types: string[];
        modules: string[];
        companies: { id: number; code: string; name: string }[];
    };
};

export default function Bitacora({ entries, filters, options }: Props) {
    return (
        <AuditLog
            title="Bitácora global"
            description="Todo lo que ocurre en la plataforma: cambios, inicios de sesión e intentos fallidos."
            url="/soporte/bitacora"
            entries={entries}
            filters={filters}
            options={options}
            showCompany={true}
        />
    );
}

Bitacora.layout = {
    breadcrumbs: [
        { title: 'Soporte', href: '/soporte/bitacora' },
        { title: 'Bitácora', href: '/soporte/bitacora' },
    ],
};
