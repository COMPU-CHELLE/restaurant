import { GeographyManager } from '@/components/geography-manager';

type Props = {
    items: any;
    filters: { search?: string | null; country?: string | null };
    countries: { codmov: number; name: string; iso2: string }[];
};

export default function Zonas({ items, filters, countries }: Props) {
    return <GeographyManager
        title="Zonas"
        base="/soporte/zonas"
        items={items}
        filters={filters}
        fields={[
            { key: 'code', label: 'Código', required: true, maxLength: 30 },
            { key: 'name', label: 'Nombre', required: true, maxLength: 255 },
            { key: 'codcoi', label: 'País', required: true, type: 'select', options: countries.map(country => ({ value: String(country.codmov), label: `${country.iso2} — ${country.name}` })) },
            { key: 'factus_id', label: 'ID Factus', maxLength: 30 },
            { key: 'state', label: 'Estado', required: true, type: 'select', options: [{ value: '1', label: 'Activo' }, { value: '0', label: 'Inactivo' }] },
        ]}
        columns={[
            { label: 'Código', render: row => row.code },
            { label: 'Nombre', render: row => row.name },
            { label: 'País', render: row => row.country ? `${row.country.iso2} — ${row.country.name}` : 'Sin país' },
            { label: 'ID Factus', render: row => row.factus_id },
            { label: 'Subzonas', render: row => row.subzones_count },
            { label: 'Estado', render: row => Number(row.state) === 1 ? 'Activo' : 'Inactivo' },
        ]}
    />;
}

Zonas.layout = { breadcrumbs: [{ title: 'Soporte', href: '/soporte/zonas' }, { title: 'Zonas', href: '/soporte/zonas' }] };
