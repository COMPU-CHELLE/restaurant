import { GeographyManager } from '@/components/geography-manager';

type Props = {
    items: any;
    filters: { search?: string | null };
};

export default function Paises({ items, filters }: Props) {
    return <GeographyManager
        title="Países"
        base="/soporte/paises"
        items={items}
        filters={filters}
        fields={[
            { key: 'iso2', label: 'ISO2', required: true, maxLength: 2 },
            { key: 'iso3', label: 'ISO3', required: true, maxLength: 3 },
            { key: 'iso_numeric', label: 'Código numérico ISO', required: true, maxLength: 3 },
            { key: 'name', label: 'Nombre', required: true, maxLength: 255 },
            { key: 'factus_id', label: 'ID Factus', maxLength: 30 },
            { key: 'region', label: 'Región', maxLength: 255 },
            { key: 'subregion', label: 'Subregión', maxLength: 255 },
            { key: 'fiscal_code', label: 'Código fiscal', maxLength: 30 },
            { key: 'state', label: 'Estado', required: true, type: 'select', options: [{ value: '1', label: 'Activo' }, { value: '0', label: 'Inactivo' }] },
        ]}
        columns={[
            { label: 'ISO2', render: row => row.iso2 },
            { label: 'ISO3', render: row => row.iso3 },
            { label: 'Nombre', render: row => row.name },
            { label: 'ID Factus', render: row => row.factus_id },
            { label: 'Código fiscal', render: row => row.fiscal_code },
            { label: 'Zonas', render: row => row.zones_count },
            { label: 'Estado', render: row => Number(row.state) === 1 ? 'Activo' : 'Inactivo' },
        ]}
    />;
}

Paises.layout = { breadcrumbs: [{ title: 'Soporte', href: '/soporte/paises' }, { title: 'Países', href: '/soporte/paises' }] };
