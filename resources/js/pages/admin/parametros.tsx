import { CatalogManager } from '@/components/catalog-manager';
import type { CatalogManagerProps } from '@/components/catalog-manager';

export default function Parametros(props: CatalogManagerProps) {
    // key: al cambiar de catálogo se reinicia el estado de los formularios.
    return <CatalogManager key={props.current} scope="admin" {...props} />;
}

Parametros.layout = {
    breadcrumbs: [
        { title: 'Administración', href: '/admin/parametros' },
        { title: 'Parámetros', href: '/admin/parametros' },
    ],
};
