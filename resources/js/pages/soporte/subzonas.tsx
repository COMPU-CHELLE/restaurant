import { GeographyManager } from '@/components/geography-manager';

type Props = {
    items: any;
    filters: { search?: string | null; zone?: string | null };
    zones: { codmov: number; name: string; code: string }[];
};

export default function Subzonas({ items, filters, zones }: Props) {
    return <GeographyManager
        title="Subzonas"
        base="/soporte/subzonas"
        items={items}
        filters={filters}
        fields={[
            { key: 'code', label: 'Código', required: true, maxLength: 40 },
            { key: 'name', label: 'Nombre', required: true, maxLength: 255 },
            { key: 'codzon', label: 'Zona', required: true, type: 'select', options: zones.map(zone => ({ value: String(zone.codmov), label: `${zone.code} — ${zone.name}` })) },
            { key: 'factus_id', label: 'ID Factus', maxLength: 30 },
            { key: 'factus_code', label: 'Código Factus', maxLength: 30 },
            { key: 'iso_numeric', label: 'Código ISO numérico', maxLength: 10 },
            { key: 'state', label: 'Estado', required: true, type: 'select', options: [{ value: '1', label: 'Activo' }, { value: '0', label: 'Inactivo' }] },
        ]}
        columns={[
            { label: 'Código', render: row => row.code },
            { label: 'Nombre', render: row => row.name },
            { label: 'Zona', render: row => row.zone ? `${row.zone.code} — ${row.zone.name}` : 'Sin zona' },
            { label: 'País', render: row => row.zone?.country ? `${row.zone.country.iso2} — ${row.zone.country.name}` : '—' },
            { label: 'ID Factus', render: row => row.factus_id },
            { label: 'Código Factus', render: row => row.factus_code },
            { label: 'Estado', render: row => Number(row.state) === 1 ? 'Activo' : 'Inactivo' },
        ]}
    />;
}

Subzonas.layout = { breadcrumbs: [{ title: 'Soporte', href: '/soporte/subzonas' }, { title: 'Subzonas', href: '/soporte/subzonas' }] };
