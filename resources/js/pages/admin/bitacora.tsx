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
            title="Bitácora de la empresa"
            description="Cambios y accesos de los usuarios de esta empresa."
            url="/admin/bitacora"
            entries={entries}
            filters={filters}
            options={options}
            showCompany={false}
        />
    );
}

Bitacora.layout = {
    breadcrumbs: [
        { title: 'Administración', href: '/admin/bitacora' },
        { title: 'Bitácora', href: '/admin/bitacora' },
    ],
};
